package main

import (
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"sync"
)

var (
	errWorkersExhausted = errors.New("milter worker capacity exhausted")
	errSpoolExhausted   = errors.New("milter spool byte budget exhausted")
	errMessageTooLarge  = errors.New("message exceeds configured byte limit")
)

type byteBudget struct {
	mu   sync.Mutex
	used int64
	max  int64
}

func (b *byteBudget) reserve(size int64) bool {
	b.mu.Lock()
	defer b.mu.Unlock()
	if size < 0 || size > b.max-b.used {
		return false
	}
	b.used += size
	return true
}

func (b *byteBudget) release(size int64) {
	b.mu.Lock()
	b.used -= size
	b.mu.Unlock()
}

func (b *byteBudget) usage() int64 {
	b.mu.Lock()
	defer b.mu.Unlock()
	return b.used
}

type capacity struct {
	dir        string
	maxMessage int64
	workers    chan struct{}
	budget     byteBudget
}

func newCapacity(dir string, maxMessage, maxSpool int64, maxConcurrency int) (*capacity, error) {
	if err := os.MkdirAll(dir, 0o750); err != nil {
		return nil, fmt.Errorf("create spool directory %q: %w", dir, err)
	}
	entries, err := os.ReadDir(dir)
	if err != nil {
		return nil, fmt.Errorf("list spool directory %q: %w", dir, err)
	}
	for _, entry := range entries {
		if !entry.IsDir() && strings.HasPrefix(entry.Name(), "agentj-") {
			if err := os.Remove(filepath.Join(dir, entry.Name())); err != nil {
				return nil, fmt.Errorf("remove stale spool file %q: %w", entry.Name(), err)
			}
		}
	}

	return &capacity{
		dir:        dir,
		maxMessage: maxMessage,
		workers:    make(chan struct{}, maxConcurrency),
		budget:     byteBudget{max: maxSpool},
	}, nil
}

func (c *capacity) begin() (*transaction, error) {
	select {
	case c.workers <- struct{}{}:
	default:
		return nil, errWorkersExhausted
	}

	input, err := newAccountedFile(c.dir, "agentj-input-*", c.maxMessage, &c.budget)
	if err != nil {
		<-c.workers
		return nil, fmt.Errorf("create input spool: %w", err)
	}
	return &transaction{capacity: c, input: input}, nil
}

type transaction struct {
	capacity *capacity
	input    *accountedFile
	output   *accountedFile
	released bool
}

func (t *transaction) createOutput() error {
	output, err := newAccountedFile(t.capacity.dir, "agentj-output-*", t.capacity.maxMessage, &t.capacity.budget)
	if err != nil {
		return fmt.Errorf("create output spool: %w", err)
	}
	t.output = output
	return nil
}

func (t *transaction) cleanup() error {
	if t == nil || t.released {
		return nil
	}
	t.released = true
	var errs []error
	if t.output != nil {
		errs = append(errs, t.output.remove())
	}
	if t.input != nil {
		errs = append(errs, t.input.remove())
	}
	<-t.capacity.workers
	return errors.Join(errs...)
}

type accountedFile struct {
	*os.File
	budget   *byteBudget
	reserved int64
	limit    int64
}

func newAccountedFile(dir, pattern string, limit int64, budget *byteBudget) (*accountedFile, error) {
	file, err := os.CreateTemp(dir, pattern)
	if err != nil {
		return nil, err
	}
	return &accountedFile{File: file, budget: budget, limit: limit}, nil
}

func (f *accountedFile) Write(data []byte) (int, error) {
	if int64(len(data)) > f.limit-f.reserved {
		return 0, errMessageTooLarge
	}
	if !f.budget.reserve(int64(len(data))) {
		return 0, errSpoolExhausted
	}
	n, err := f.File.Write(data)
	f.reserved += int64(n)
	f.budget.release(int64(len(data) - n))
	return n, err
}

func (f *accountedFile) remove() error {
	var errs []error
	if err := f.Close(); err != nil && !errors.Is(err, os.ErrClosed) {
		errs = append(errs, fmt.Errorf("close %q: %w", f.Name(), err))
	}
	if err := os.Remove(f.Name()); err != nil && !errors.Is(err, os.ErrNotExist) {
		errs = append(errs, fmt.Errorf("remove %q: %w", f.Name(), err))
	}
	f.budget.release(f.reserved)
	f.reserved = 0
	return errors.Join(errs...)
}
