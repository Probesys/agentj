package main

import (
	"bytes"
	"io"
	"net"
	"net/mail"
	"net/textproto"
	"os"
	"path/filepath"
	"strings"
	"testing"
	"time"

	"github.com/emersion/go-milter"
)

func TestProtocolNoopSendsNoBodyReplacement(t *testing.T) {
	fixture, err := os.Open(filepath.Join(fixtureDir(), "no-url.eml"))
	if err != nil {
		t.Fatal(err)
	}
	defer fixture.Close()
	actions, final := runProtocolMessage(t, fixture, 1<<20)
	if final.Code != milter.ActAccept {
		t.Fatalf("final action = %c, want accept", final.Code)
	}
	if len(actions) != 0 {
		t.Fatalf("no-op emitted %d modification actions", len(actions))
	}
}

func TestProtocolReplacementIsChunkedAtEOM(t *testing.T) {
	body := "https://example.org/path\n" + strings.Repeat("x", 150000)
	raw := strings.NewReader("From: sender@example.net\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n" + body)
	actions, final := runProtocolMessage(t, raw, 1<<20)
	if final.Code != milter.ActAccept {
		t.Fatalf("final action = %c, want accept", final.Code)
	}
	var chunks int
	var replacement bytes.Buffer
	for _, action := range actions {
		if action.Code == milter.ActReplBody {
			chunks++
			replacement.Write(action.Body)
		}
	}
	if chunks < 3 {
		t.Fatalf("replacement chunks = %d, want at least 3", chunks)
	}
	if !bytes.Contains(replacement.Bytes(), []byte(wrapperPrefix)) {
		t.Fatal("replacement body does not contain wrapped URL")
	}
}

func TestProtocolAbortCleansSpool(t *testing.T) {
	dir := t.TempDir()
	capacity, err := newCapacity(dir, 1<<20, 2<<20, 1)
	if err != nil {
		t.Fatal(err)
	}
	address, stop := startTestServer(t, capacity)
	defer stop()
	client := protocolClient(address)
	session, err := client.Session()
	if err != nil {
		t.Fatal(err)
	}
	if _, err := session.Mail("sender@example.net", nil); err != nil {
		t.Fatal(err)
	}
	if _, err := session.BodyChunk([]byte("partial")); err != nil {
		t.Fatal(err)
	}
	if err := session.Abort(); err != nil {
		t.Fatal(err)
	}
	time.Sleep(20 * time.Millisecond)
	assertEmptySpool(t, capacity)
	_ = session.Close()
}

func TestMessageLimitTempfailsAndCleans(t *testing.T) {
	capacity, err := newCapacity(t.TempDir(), 8, 32, 1)
	if err != nil {
		t.Fatal(err)
	}
	filter := newFilter(capacity, time.Minute)
	if response, _ := filter.MailFrom("sender@example.net", nil); response != milter.RespContinue {
		t.Fatal("transaction did not start")
	}
	response, err := filter.BodyChunk([]byte("123456789"), nil)
	if err != nil {
		t.Fatal(err)
	}
	if response != milter.RespTempFail {
		t.Fatalf("oversized chunk response = %v, want tempfail", response)
	}
	assertEmptySpool(t, capacity)
}

func TestAbandonedTransactionTimesOutAndCleans(t *testing.T) {
	capacity, err := newCapacity(t.TempDir(), 1<<20, 2<<20, 1)
	if err != nil {
		t.Fatal(err)
	}
	filter := newFilter(capacity, 10*time.Millisecond)
	if response, _ := filter.MailFrom("sender@example.net", nil); response != milter.RespContinue {
		t.Fatal("transaction did not start")
	}
	if response, _ := filter.BodyChunk([]byte("partial"), nil); response != milter.RespContinue {
		t.Fatal("body chunk was not accepted")
	}
	deadline := time.Now().Add(time.Second)
	for capacity.budget.usage() != 0 && time.Now().Before(deadline) {
		time.Sleep(time.Millisecond)
	}
	assertEmptySpool(t, capacity)
}

func TestMailFromClearsHeadersLeftByReusedConnection(t *testing.T) {
	capacity, err := newCapacity(t.TempDir(), 1<<20, 2<<20, 1)
	if err != nil {
		t.Fatal(err)
	}
	filter := newFilter(capacity, time.Minute)
	modifier := &milter.Modifier{Headers: textproto.MIMEHeader{"Subject": {"previous message"}}}
	if response, _ := filter.MailFrom("sender@example.net", modifier); response != milter.RespContinue {
		t.Fatal("transaction did not start")
	}
	if len(modifier.Headers) != 0 {
		t.Fatalf("stale headers remain: %#v", modifier.Headers)
	}
	if err := filter.Abort(modifier); err != nil {
		t.Fatal(err)
	}
}

func runProtocolMessage(t *testing.T, raw io.Reader, maxMessage int64) ([]milter.ModifyAction, *milter.Action) {
	t.Helper()
	capacity, err := newCapacity(t.TempDir(), maxMessage, maxMessage*2, 1)
	if err != nil {
		t.Fatal(err)
	}
	address, stop := startTestServer(t, capacity)
	defer stop()
	parsed, err := mail.ReadMessage(raw)
	if err != nil {
		t.Fatal(err)
	}
	client := protocolClient(address)
	session, err := client.Session()
	if err != nil {
		t.Fatal(err)
	}
	defer session.Close()
	if action, err := session.Mail("sender@example.net", nil); err != nil || action.Code != milter.ActContinue {
		t.Fatalf("MAIL action=%v error=%v", action, err)
	}
	for name, values := range parsed.Header {
		for _, value := range values {
			if action, err := session.HeaderField(name, value); err != nil || action.Code != milter.ActContinue {
				t.Fatalf("header %s action=%v error=%v", name, action, err)
			}
		}
	}
	if action, err := session.HeaderEnd(); err != nil || action.Code != milter.ActContinue {
		t.Fatalf("EOH action=%v error=%v", action, err)
	}
	actions, final, err := session.BodyReadFrom(parsed.Body)
	if err != nil {
		t.Fatal(err)
	}
	assertEmptySpool(t, capacity)
	return actions, final
}

func protocolClient(address string) *milter.Client {
	return milter.NewClientWithOptions("tcp", address, milter.ClientOptions{
		Dialer:     &net.Dialer{Timeout: time.Second},
		ActionMask: milter.OptChangeBody,
	})
}

func startTestServer(t *testing.T, capacity *capacity) (string, func()) {
	t.Helper()
	listener, err := net.Listen("tcp", "127.0.0.1:0")
	if err != nil {
		t.Fatal(err)
	}
	server := &milter.Server{
		Actions: milter.OptChangeBody,
		NewMilter: func() milter.Milter {
			return newFilter(capacity, time.Minute)
		},
	}
	done := make(chan struct{})
	go func() {
		_ = server.Serve(listener)
		close(done)
	}()
	return listener.Addr().String(), func() {
		_ = server.Close()
		<-done
	}
}

func assertEmptySpool(t *testing.T, capacity *capacity) {
	t.Helper()
	entries, err := os.ReadDir(capacity.dir)
	if err != nil {
		t.Fatal(err)
	}
	if len(entries) != 0 || capacity.budget.usage() != 0 {
		t.Fatalf("spool files=%d budget=%d after transaction", len(entries), capacity.budget.usage())
	}
}
