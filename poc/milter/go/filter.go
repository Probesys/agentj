package main

import (
	"errors"
	"fmt"
	"io"
	"log"
	"net"
	"net/textproto"
	"sync"
	"time"

	"github.com/emersion/go-milter"
)

type filter struct {
	mu          sync.Mutex
	capacity    *capacity
	transformer mimeTransformer
	timeout     time.Duration
	tx          *transaction
	timer       *time.Timer
	headerBytes int64
}

func newFilter(capacity *capacity, timeout time.Duration) *filter {
	return &filter{capacity: capacity, timeout: timeout}
}

func (f *filter) Connect(string, string, uint16, net.IP, *milter.Modifier) (milter.Response, error) {
	return milter.RespContinue, nil
}

func (f *filter) Helo(string, *milter.Modifier) (milter.Response, error) {
	return milter.RespContinue, nil
}

func (f *filter) MailFrom(_ string, modifier *milter.Modifier) (milter.Response, error) {
	f.mu.Lock()
	defer f.mu.Unlock()
	// go-milter v0.4.1 does not clear headers after EOM on a reused connection.
	if modifier != nil {
		for name := range modifier.Headers {
			delete(modifier.Headers, name)
		}
	}
	if err := f.cleanupLocked(); err != nil {
		log.Printf("clean previous transaction: %v", err)
	}
	tx, err := f.capacity.begin()
	if err != nil {
		log.Printf("start transaction: %v", err)
		return milter.RespTempFail, nil
	}
	f.tx = tx
	f.headerBytes = 0
	f.timer = time.AfterFunc(f.timeout, func() {
		f.mu.Lock()
		defer f.mu.Unlock()
		if f.tx == tx {
			log.Printf("transaction timed out after %s", f.timeout)
			if err := f.cleanupLocked(); err != nil {
				log.Printf("clean timed-out transaction: %v", err)
			}
		}
	})
	return milter.RespContinue, nil
}

func (f *filter) RcptTo(string, *milter.Modifier) (milter.Response, error) {
	return milter.RespContinue, nil
}

func (f *filter) Header(name, value string, _ *milter.Modifier) (milter.Response, error) {
	f.mu.Lock()
	defer f.mu.Unlock()
	if f.tx == nil {
		return milter.RespTempFail, nil
	}
	f.headerBytes += int64(len(name) + len(value) + 4)
	if f.headerBytes > f.capacity.maxMessage {
		return f.failLocked(fmt.Errorf("account header %q: %w", name, errMessageTooLarge))
	}
	return milter.RespContinue, nil
}

func (f *filter) Headers(textproto.MIMEHeader, *milter.Modifier) (milter.Response, error) {
	f.mu.Lock()
	defer f.mu.Unlock()
	if f.tx == nil {
		return milter.RespTempFail, nil
	}
	return milter.RespContinue, nil
}

func (f *filter) BodyChunk(chunk []byte, _ *milter.Modifier) (milter.Response, error) {
	f.mu.Lock()
	defer f.mu.Unlock()
	if f.tx == nil {
		return milter.RespTempFail, nil
	}
	if f.headerBytes+f.tx.input.reserved+int64(len(chunk)) > f.capacity.maxMessage {
		return f.failLocked(fmt.Errorf("receive body chunk: %w", errMessageTooLarge))
	}
	if _, err := f.tx.input.Write(chunk); err != nil {
		return f.failLocked(fmt.Errorf("spool body chunk: %w", err))
	}
	return milter.RespContinue, nil
}

func (f *filter) Body(modifier *milter.Modifier) (milter.Response, error) {
	f.mu.Lock()
	defer f.mu.Unlock()
	if f.tx == nil {
		return milter.RespTempFail, nil
	}
	if err := f.tx.input.Sync(); err != nil {
		return f.failLocked(fmt.Errorf("sync input spool: %w", err))
	}
	if _, err := f.tx.input.Seek(0, io.SeekStart); err != nil {
		return f.failLocked(fmt.Errorf("rewind input spool: %w", err))
	}
	changed, err := f.transformer.hasRewrite(modifier.Headers, f.tx.input)
	if err != nil {
		return f.failLocked(fmt.Errorf("inspect message body: %w", err))
	}
	if !changed {
		if err := f.cleanupLocked(); err != nil {
			log.Printf("clean no-op transaction: %v", err)
			return milter.RespTempFail, nil
		}
		return milter.RespAccept, nil
	}
	if _, err := f.tx.input.Seek(0, io.SeekStart); err != nil {
		return f.failLocked(fmt.Errorf("rewind input spool after inspection: %w", err))
	}
	if err := f.tx.createOutput(); err != nil {
		return f.failLocked(err)
	}
	changed, err = f.transformer.rewrite(modifier.Headers, f.tx.input, f.tx.output)
	if err != nil {
		return f.failLocked(fmt.Errorf("transform message body: %w", err))
	}
	if !changed {
		return f.failLocked(errors.New("message changed during inspection but not during rewrite"))
	}
	if err := f.tx.output.Sync(); err != nil {
		return f.failLocked(fmt.Errorf("sync output spool: %w", err))
	}
	if _, err := f.tx.output.Seek(0, io.SeekStart); err != nil {
		return f.failLocked(fmt.Errorf("rewind output spool: %w", err))
	}
	buffer := make([]byte, milter.MaxBodyChunk)
	for {
		n, readErr := f.tx.output.Read(buffer)
		if n == len(buffer) && buffer[n-1] == '\r' {
			if _, err := f.tx.output.Seek(-1, io.SeekCurrent); err != nil {
				return f.failLocked(fmt.Errorf("preserve CRLF replacement boundary: %w", err))
			}
			n--
			readErr = nil
		}
		if n > 0 {
			if err := modifier.ReplaceBody(buffer[:n]); err != nil {
				return f.failLocked(fmt.Errorf("replace body chunk: %w", err))
			}
		}
		if readErr == io.EOF {
			break
		}
		if readErr != nil {
			return f.failLocked(fmt.Errorf("read output spool: %w", readErr))
		}
	}
	if err := f.cleanupLocked(); err != nil {
		log.Printf("clean transformed transaction: %v", err)
		return milter.RespTempFail, nil
	}
	return milter.RespAccept, nil
}

func (f *filter) Abort(*milter.Modifier) error {
	f.mu.Lock()
	defer f.mu.Unlock()
	if err := f.cleanupLocked(); err != nil {
		return fmt.Errorf("abort transaction cleanup: %w", err)
	}
	return nil
}

func (f *filter) failLocked(cause error) (milter.Response, error) {
	cleanupErr := f.cleanupLocked()
	if cleanupErr != nil {
		cause = errors.Join(cause, fmt.Errorf("cleanup failed transaction: %w", cleanupErr))
	}
	log.Printf("message tempfail: %v", cause)
	return milter.RespTempFail, nil
}

func (f *filter) cleanupLocked() error {
	if f.timer != nil {
		f.timer.Stop()
		f.timer = nil
	}
	tx := f.tx
	f.tx = nil
	f.headerBytes = 0
	if tx == nil {
		return nil
	}
	return tx.cleanup()
}
