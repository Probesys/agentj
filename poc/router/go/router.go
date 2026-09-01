package main

import (
	"bufio"
	"fmt"
	"io"
	"log"
	"net"
	"os"
	"strings"
	"time"
	"unicode/utf8"

	"github.com/emersion/go-smtp"
)

const policyHeader = "X-AgentJ-Policy"

type router struct {
	cfg config
	log *log.Logger
}

type session struct {
	router     *router
	from       string
	recipients []string
}

func (r *router) NewSession(*smtp.Conn) (smtp.Session, error) {
	return &session{router: r}, nil
}

func (s *session) Mail(from string, _ *smtp.MailOptions) error {
	s.from = from
	s.recipients = nil
	return nil
}

func (s *session) Rcpt(to string, _ *smtp.RcptOptions) error {
	s.recipients = append(s.recipients, to)
	return nil
}

func (s *session) Data(data io.Reader) error {
	spool, err := os.CreateTemp(s.router.cfg.SpoolDirectory, "agentj-router-*")
	if err != nil {
		return s.temporary("create spool", err)
	}
	name := spool.Name()
	defer os.Remove(name)

	written, copyErr := io.Copy(spool, io.LimitReader(data, s.router.cfg.MaxMessageBytes+1))
	closeErr := spool.Close()
	if copyErr != nil {
		return s.temporary("write spool", copyErr)
	}
	if closeErr != nil {
		return s.temporary("close spool", closeErr)
	}
	if written > s.router.cfg.MaxMessageBytes {
		return s.temporary("spool message", fmt.Errorf("message exceeds %d bytes", s.router.cfg.MaxMessageBytes))
	}

	groups := groupRecipients(s.recipients)
	for _, policy := range []string{"1", "2"} {
		recipients := groups[policy]
		if len(recipients) == 0 {
			continue
		}
		if err := s.router.submit(name, s.from, recipients, policy); err != nil {
			return s.temporary("submit policy "+policy, err)
		}
		s.router.log.Printf("submitted policy=%s recipients=%d", policy, len(recipients))
	}
	return nil
}

func (s *session) Reset() {
	s.from = ""
	s.recipients = nil
}

func (s *session) Logout() error { return nil }

func (s *session) temporary(operation string, err error) error {
	s.router.log.Printf("%s: %v", operation, err)
	return &smtp.SMTPError{
		Code:         451,
		EnhancedCode: smtp.EnhancedCode{4, 3, 0},
		Message:      "routing failed; retry later",
	}
}

func groupRecipients(recipients []string) map[string][]string {
	groups := make(map[string][]string)
	for _, recipient := range recipients {
		policy := policyFor(recipient)
		groups[policy] = append(groups[policy], recipient)
	}
	return groups
}

// policyFor is deliberately simplistic for this POC: odd local-part lengths use policy 1.
func policyFor(address string) string {
	local := address
	if at := strings.LastIndexByte(address, '@'); at >= 0 {
		local = address[:at]
	}
	if utf8.RuneCountInString(local)%2 == 1 {
		return "1"
	}
	return "2"
}

func (r *router) submit(spoolName, from string, recipients []string, policy string) error {
	conn, err := (&net.Dialer{Timeout: r.cfg.DialTimeout}).Dial("tcp", r.cfg.DownstreamAddress)
	if err != nil {
		return err
	}
	client := smtp.NewClient(conn)
	client.CommandTimeout = r.cfg.CommandTimeout
	client.SubmissionTimeout = r.cfg.SubmissionTimeout
	defer client.Close()

	spool, err := os.Open(spoolName)
	if err != nil {
		return err
	}
	defer spool.Close()

	if err := client.Mail(from, nil); err != nil {
		return err
	}
	for _, recipient := range recipients {
		if err := client.Rcpt(recipient, nil); err != nil {
			return err
		}
	}
	data, err := client.Data()
	if err != nil {
		return err
	}
	if err := conn.SetWriteDeadline(time.Now().Add(r.cfg.SubmissionTimeout)); err != nil {
		return err
	}
	if err := writePolicyMessage(data, spool, policy); err != nil {
		return err
	}
	return data.Close()
}

func writePolicyMessage(dst io.Writer, src io.Reader, policy string) error {
	reader := bufio.NewReader(src)
	dropContinuation := false

	for {
		line, err := reader.ReadString('\n')
		if len(line) > 0 {
			if line == "\n" || line == "\r\n" {
				if _, writeErr := fmt.Fprintf(dst, "%s: %s\r\n", policyHeader, policy); writeErr != nil {
					return writeErr
				}
				if _, writeErr := io.WriteString(dst, line); writeErr != nil {
					return writeErr
				}
				_, copyErr := io.Copy(dst, reader)
				return copyErr
			}

			continuation := line[0] == ' ' || line[0] == '\t'
			if !continuation {
				name, _, found := strings.Cut(line, ":")
				dropContinuation = found && strings.EqualFold(strings.TrimSpace(name), policyHeader)
			}
			if !dropContinuation {
				if _, writeErr := io.WriteString(dst, line); writeErr != nil {
					return writeErr
				}
			}
		}
		if err != nil {
			if err == io.EOF {
				return fmt.Errorf("message has no header/body separator")
			}
			return err
		}
	}
}
