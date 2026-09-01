package main

import (
	"bytes"
	"encoding/base64"
	"fmt"
	"io"
	"mime/multipart"
	"mime/quotedprintable"
	"net/textproto"
	"regexp"
	"strings"

	"github.com/emersion/go-message"
	_ "github.com/emersion/go-message/charset"
	"golang.org/x/text/encoding/htmlindex"
	"golang.org/x/text/transform"
)

const wrapperPrefix = "https://agentj.invalid/r?u="

var (
	plainURLPattern = regexp.MustCompile(`(?i)https?://[^\s<>"']+`)
	hrefPattern     = regexp.MustCompile(`(?i)\bhref[ \t\r\n]*=[ \t\r\n]*(?:"[^"]*"|'[^']*'|[^ \t\r\n>]+)`)
)

type mimeTransformer struct{}

func (mimeTransformer) hasRewrite(header textproto.MIMEHeader, body io.Reader) (bool, error) {
	entity, err := message.New(message.HeaderFromMap(map[string][]string(header)), body)
	if err != nil {
		return false, fmt.Errorf("decode root MIME entity: %w", err)
	}
	changed, err := entityHasRewrite(entity)
	if err != nil {
		return false, fmt.Errorf("inspect root MIME entity: %w", err)
	}
	return changed, nil
}

func (mimeTransformer) rewrite(header textproto.MIMEHeader, body io.Reader, output io.Writer) (bool, error) {
	entity, err := message.New(message.HeaderFromMap(map[string][]string(header)), body)
	if err != nil {
		return false, fmt.Errorf("decode root MIME entity: %w", err)
	}
	changed, err := rewriteEntityBody(entity, output)
	if err != nil {
		return false, fmt.Errorf("rewrite root MIME entity: %w", err)
	}
	return changed, nil
}

func entityHasRewrite(entity *message.Entity) (bool, error) {
	mediaType, _, err := entity.Header.ContentType()
	if err != nil {
		return false, fmt.Errorf("parse Content-Type: %w", err)
	}
	if reader := entity.MultipartReader(); reader != nil {
		for index := 0; ; index++ {
			part, err := reader.NextPart()
			if err == io.EOF {
				return false, nil
			}
			if err != nil {
				return false, fmt.Errorf("read MIME part %d: %w", index, err)
			}
			changed, err := entityHasRewrite(part)
			if err != nil {
				return false, fmt.Errorf("inspect MIME part %d: %w", index, err)
			}
			if changed {
				return true, nil
			}
		}
	}
	if !isTarget(entity.Header, mediaType) {
		if _, err := io.Copy(io.Discard, entity.Body); err != nil {
			return false, fmt.Errorf("discard non-target %s leaf: %w", mediaType, err)
		}
		return false, nil
	}
	decoded, err := io.ReadAll(entity.Body)
	if err != nil {
		return false, fmt.Errorf("read target %s leaf: %w", mediaType, err)
	}
	if strings.EqualFold(mediaType, "text/html") {
		return !bytes.Equal(decoded, rewriteHTML(decoded)), nil
	}
	return !bytes.Equal(decoded, rewritePlain(decoded)), nil
}

func rewriteEntityBody(entity *message.Entity, output io.Writer) (bool, error) {
	mediaType, params, err := entity.Header.ContentType()
	if err != nil {
		return false, fmt.Errorf("parse Content-Type: %w", err)
	}
	if strings.HasPrefix(strings.ToLower(mediaType), "multipart/") {
		reader := entity.MultipartReader()
		if reader == nil {
			return false, fmt.Errorf("create reader for %s", mediaType)
		}
		writer := multipart.NewWriter(output)
		boundary := params["boundary"]
		if boundary == "" {
			return false, fmt.Errorf("%s has no boundary", mediaType)
		}
		if err := writer.SetBoundary(boundary); err != nil {
			return false, fmt.Errorf("set multipart boundary: %w", err)
		}

		changed := false
		for index := 0; ; index++ {
			part, err := reader.NextPart()
			if err == io.EOF {
				break
			}
			if err != nil {
				return false, fmt.Errorf("read MIME part %d: %w", index, err)
			}
			partOutput, err := writer.CreatePart(mimeHeader(part.Header))
			if err != nil {
				return false, fmt.Errorf("create MIME part %d: %w", index, err)
			}
			partChanged, err := rewriteEntityBody(part, partOutput)
			if err != nil {
				return false, fmt.Errorf("rewrite MIME part %d: %w", index, err)
			}
			changed = changed || partChanged
		}
		if err := writer.Close(); err != nil {
			return false, fmt.Errorf("close multipart writer: %w", err)
		}
		return changed, nil
	}

	if isTarget(entity.Header, mediaType) {
		decoded, err := io.ReadAll(entity.Body)
		if err != nil {
			return false, fmt.Errorf("read target %s leaf: %w", mediaType, err)
		}
		var rewritten []byte
		if strings.EqualFold(mediaType, "text/html") {
			rewritten = rewriteHTML(decoded)
		} else {
			rewritten = rewritePlain(decoded)
		}
		changed := !bytes.Equal(decoded, rewritten)
		if err := writeEncodedLeaf(output, entity.Header, bytes.NewReader(rewritten)); err != nil {
			return false, err
		}
		return changed, nil
	}

	if err := writeEncodedLeaf(output, entity.Header, entity.Body); err != nil {
		return false, err
	}
	return false, nil
}

func isTarget(header message.Header, mediaType string) bool {
	if !strings.EqualFold(mediaType, "text/plain") && !strings.EqualFold(mediaType, "text/html") {
		return false
	}
	if value := header.Get("Content-Disposition"); value != "" {
		disposition, params, err := header.ContentDisposition()
		if err != nil || strings.EqualFold(disposition, "attachment") || params["filename"] != "" {
			return false
		}
	}
	_, params, err := header.ContentType()
	return err == nil && params["name"] == ""
}

func writeEncodedLeaf(output io.Writer, header message.Header, body io.Reader) error {
	transferWriter, err := newTransferWriter(output, header.Get("Content-Transfer-Encoding"))
	if err != nil {
		return err
	}
	writer := io.Writer(transferWriter)
	var charsetWriter io.WriteCloser
	_, params, err := header.ContentType()
	if err != nil {
		return fmt.Errorf("parse leaf Content-Type: %w", err)
	}
	charset := strings.ToLower(params["charset"])
	if charset != "" && charset != "utf-8" && charset != "us-ascii" {
		encoding, err := htmlindex.Get(charset)
		if err != nil {
			return fmt.Errorf("find encoder for charset %q: %w", charset, err)
		}
		charsetWriter = transform.NewWriter(transferWriter, encoding.NewEncoder())
		writer = charsetWriter
	}
	if _, err := io.Copy(writer, body); err != nil {
		return fmt.Errorf("stream MIME leaf: %w", err)
	}
	if charsetWriter != nil {
		if err := charsetWriter.Close(); err != nil {
			return fmt.Errorf("close charset encoder: %w", err)
		}
	}
	if err := transferWriter.Close(); err != nil {
		return fmt.Errorf("close transfer encoder: %w", err)
	}
	return nil
}

func newTransferWriter(output io.Writer, encoding string) (io.WriteCloser, error) {
	switch strings.ToLower(strings.TrimSpace(encoding)) {
	case "base64":
		return base64.NewEncoder(base64.StdEncoding, &mimeLineWriter{writer: output}), nil
	case "quoted-printable":
		return quotedprintable.NewWriter(output), nil
	case "", "7bit", "8bit", "binary":
		return nopWriteCloser{output}, nil
	default:
		return nil, fmt.Errorf("unsupported Content-Transfer-Encoding %q", encoding)
	}
}

type nopWriteCloser struct{ io.Writer }

func (nopWriteCloser) Close() error { return nil }

type mimeLineWriter struct {
	writer io.Writer
	column int
}

func (w *mimeLineWriter) Write(data []byte) (int, error) {
	written := 0
	for len(data) > 0 {
		lineRemaining := 76 - w.column
		if lineRemaining > len(data) {
			lineRemaining = len(data)
		}
		n, err := w.writer.Write(data[:lineRemaining])
		written += n
		w.column += n
		data = data[n:]
		if err != nil {
			return written, err
		}
		if n != lineRemaining {
			return written, io.ErrShortWrite
		}
		if w.column == 76 {
			if _, err := io.WriteString(w.writer, "\r\n"); err != nil {
				return written, err
			}
			w.column = 0
		}
	}
	return written, nil
}

func mimeHeader(header message.Header) textproto.MIMEHeader {
	result := make(textproto.MIMEHeader)
	for fields := header.Fields(); fields.Next(); {
		result.Add(fields.Key(), fields.Value())
	}
	return result
}

func rewritePlain(input []byte) []byte {
	return plainURLPattern.ReplaceAllFunc(input, func(candidate []byte) []byte {
		url, suffix := splitTrailingPunctuation(candidate)
		if strings.HasPrefix(strings.ToLower(string(url)), wrapperPrefix) {
			return candidate
		}
		wrapped := wrapURL(string(url))
		return append([]byte(wrapped), suffix...)
	})
}

func splitTrailingPunctuation(candidate []byte) ([]byte, []byte) {
	end := len(candidate)
	for end > 0 && strings.ContainsRune(".,;:!?)]}", rune(candidate[end-1])) {
		end--
	}
	return candidate[:end], candidate[end:]
}

func rewriteHTML(input []byte) []byte {
	matches := hrefPattern.FindAllIndex(input, -1)
	if len(matches) == 0 {
		return input
	}
	var output bytes.Buffer
	last := 0
	for _, match := range matches {
		attribute := input[match[0]:match[1]]
		equals := bytes.IndexByte(attribute, '=')
		valueStart := equals + 1
		for valueStart < len(attribute) && strings.ContainsRune(" \t\r\n", rune(attribute[valueStart])) {
			valueStart++
		}
		valueEnd := len(attribute)
		if valueStart < valueEnd && (attribute[valueStart] == '\'' || attribute[valueStart] == '"') {
			valueStart++
			valueEnd--
		}
		value := attribute[valueStart:valueEnd]
		if !plainURLPattern.Match(value) || plainURLPattern.FindIndex(value)[0] != 0 ||
			strings.HasPrefix(strings.ToLower(string(value)), wrapperPrefix) {
			continue
		}
		output.Write(input[last : match[0]+valueStart])
		output.WriteString(wrapURL(string(value)))
		output.Write(input[match[0]+valueEnd : match[1]])
		last = match[1]
	}
	if last == 0 {
		return input
	}
	output.Write(input[last:])
	return output.Bytes()
}

func wrapURL(url string) string {
	return wrapperPrefix + base64.RawURLEncoding.EncodeToString([]byte(url))
}
