package main

import (
	"bytes"
	"crypto/sha256"
	"fmt"
	"io"
	"net/mail"
	"net/textproto"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/emersion/go-message"
)

type transformedMessage struct {
	header  textproto.MIMEHeader
	body    []byte
	changed bool
}

func transformFixture(t *testing.T, name string) transformedMessage {
	t.Helper()
	path := filepath.Join(fixtureDir(), name)
	file, err := os.Open(path)
	if err != nil {
		t.Fatalf("open fixture %s: %v", name, err)
	}
	defer file.Close()
	parsed, err := mail.ReadMessage(file)
	if err != nil {
		t.Fatalf("parse fixture %s: %v", name, err)
	}
	var output bytes.Buffer
	header := textproto.MIMEHeader(parsed.Header)
	changed, err := (mimeTransformer{}).rewrite(header, parsed.Body, &output)
	if err != nil {
		t.Fatalf("transform fixture %s: %v", name, err)
	}
	return transformedMessage{header: header, body: output.Bytes(), changed: changed}
}

func fixtureDir() string {
	if dir := os.Getenv("MILTER_FIXTURES"); dir != "" {
		return dir
	}
	return filepath.Join("..", "..", "..", "tests", "milter", "fixtures")
}

func TestNoURLIsANoop(t *testing.T) {
	result := transformFixture(t, "no-url.eml")
	if result.changed {
		t.Fatal("message without URL was reported as changed")
	}
}

func TestPlainQuotedPrintable(t *testing.T) {
	result := transformFixture(t, "plain-quoted-printable.eml")
	if !result.changed {
		t.Fatal("message with plain-text URLs was not changed")
	}
	leaves := decodedLeaves(t, result.header, result.body)
	plain := leaves["text/plain"]
	if strings.Count(plain, wrapperPrefix) != 2 {
		t.Fatalf("wrapper count = %d, want 2; body=%q", strings.Count(plain, wrapperPrefix), plain)
	}
	if !strings.Contains(plain, "Café: <") || !strings.Contains(plain, ">, puis ") || !strings.HasSuffix(strings.TrimSpace(plain), ".") {
		t.Fatalf("charset or punctuation was not preserved: %q", plain)
	}
	assertIdempotent(t, result)
}

func TestHTMLBase64(t *testing.T) {
	result := transformFixture(t, "html-base64.eml")
	leaves := decodedLeaves(t, result.header, result.body)
	html := leaves["text/html"]
	if strings.Count(html, wrapperPrefix) != 1 {
		t.Fatalf("wrapper count = %d, want 1; body=%q", strings.Count(html, wrapperPrefix), html)
	}
	if !strings.Contains(html, `href="mailto:x@y"`) || !strings.Contains(html, ">Lien</a>") {
		t.Fatalf("non-HTTP href or visible text changed: %q", html)
	}
	assertIdempotent(t, result)
}

func TestMultipartAttachmentIsExcluded(t *testing.T) {
	original := transformFixture(t, "multipart-mixed.eml")
	if !original.changed {
		t.Fatal("multipart message was not changed")
	}
	leaves := decodedLeaves(t, original.header, original.body)
	if strings.Count(leaves["text/plain"], wrapperPrefix) != 1 || strings.Count(leaves["text/html"], wrapperPrefix) != 1 {
		t.Fatalf("target leaves were not rewritten: %#v", leaves)
	}
	want := sha256.Sum256([]byte("attachment https://attachment.example/file"))
	got := sha256.Sum256([]byte(leaves["attachment:links.txt"]))
	if got != want {
		t.Fatalf("attachment hash = %x, want %x", got, want)
	}
	if strings.Contains(leaves["attachment:links.txt"], wrapperPrefix) {
		t.Fatal("attachment URL was rewritten")
	}
	assertIdempotent(t, original)
}

func TestIncomingDKIMHeadersRemainAvailable(t *testing.T) {
	result := transformFixture(t, "incoming-dkim.eml")
	if !result.changed {
		t.Fatal("signed fixture body was not changed")
	}
	if result.header.Get("DKIM-Signature") == "" || !strings.Contains(result.header.Get("Authentication-Results"), "dkim=pass") {
		t.Fatal("incoming authentication headers were lost")
	}
}

func TestNonTargetLeafIsStreamed(t *testing.T) {
	header := message.HeaderFromMap(map[string][]string{"Content-Type": {"application/octet-stream"}})
	reader := &readProbe{remaining: 2 << 20}
	entity, err := message.New(header, reader)
	if err != nil {
		t.Fatal(err)
	}
	if _, err := rewriteEntityBody(entity, io.Discard); err != nil {
		t.Fatal(err)
	}
	if reader.maxRequest > 32*1024 {
		t.Fatalf("non-target leaf requested a %d-byte read; expected streaming copies", reader.maxRequest)
	}
}

func TestTargetTextLeafIsBufferedByDesign(t *testing.T) {
	header := message.HeaderFromMap(map[string][]string{"Content-Type": {"text/plain; charset=UTF-8"}})
	reader := &readProbe{remaining: 2 << 20}
	entity, err := message.New(header, reader)
	if err != nil {
		t.Fatal(err)
	}
	if _, err := rewriteEntityBody(entity, io.Discard); err != nil {
		t.Fatal(err)
	}
	if reader.maxRequest <= 32*1024 {
		t.Fatalf("target leaf largest read = %d; test no longer demonstrates the documented buffering limit", reader.maxRequest)
	}
}

func TestBase64OutputUsesMIMELineLength(t *testing.T) {
	var output bytes.Buffer
	writer, err := newTransferWriter(&output, "base64")
	if err != nil {
		t.Fatal(err)
	}
	if _, err := writer.Write(bytes.Repeat([]byte("x"), 200)); err != nil {
		t.Fatal(err)
	}
	if err := writer.Close(); err != nil {
		t.Fatal(err)
	}
	for _, line := range strings.Split(output.String(), "\r\n") {
		if len(line) > 76 {
			t.Fatalf("base64 line length = %d, want at most 76", len(line))
		}
	}
}

func assertIdempotent(t *testing.T, first transformedMessage) {
	t.Helper()
	var second bytes.Buffer
	changed, err := (mimeTransformer{}).rewrite(first.header, bytes.NewReader(first.body), &second)
	if err != nil {
		t.Fatalf("second transformation: %v", err)
	}
	if changed {
		t.Fatal("second transformation was not a no-op")
	}
}

func decodedLeaves(t *testing.T, header textproto.MIMEHeader, body []byte) map[string]string {
	t.Helper()
	root, err := message.New(message.HeaderFromMap(map[string][]string(header)), bytes.NewReader(body))
	if err != nil {
		t.Fatalf("decode transformed root: %v", err)
	}
	result := make(map[string]string)
	var walk func(*message.Entity)
	walk = func(entity *message.Entity) {
		mediaType, _, err := entity.Header.ContentType()
		if err != nil {
			t.Fatalf("parse transformed Content-Type: %v", err)
		}
		if reader := entity.MultipartReader(); reader != nil {
			for {
				part, err := reader.NextPart()
				if err == io.EOF {
					return
				}
				if err != nil {
					t.Fatalf("read transformed part: %v", err)
				}
				walk(part)
			}
		}
		decoded, err := io.ReadAll(entity.Body)
		if err != nil {
			t.Fatalf("read transformed leaf: %v", err)
		}
		key := mediaType
		if _, params, _ := entity.Header.ContentDisposition(); params["filename"] != "" {
			key = "attachment:" + params["filename"]
		}
		result[key] = string(decoded)
	}
	walk(root)
	return result
}

type readProbe struct {
	remaining  int
	maxRequest int
}

func (r *readProbe) Read(buffer []byte) (int, error) {
	if len(buffer) > r.maxRequest {
		r.maxRequest = len(buffer)
	}
	if r.remaining == 0 {
		return 0, io.EOF
	}
	n := len(buffer)
	if n > r.remaining {
		n = r.remaining
	}
	for index := 0; index < n; index++ {
		buffer[index] = 'x'
	}
	r.remaining -= n
	return n, nil
}

func (m transformedMessage) String() string {
	return fmt.Sprintf("changed=%v body=%d bytes", m.changed, len(m.body))
}
