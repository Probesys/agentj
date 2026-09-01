package main

import (
	"bytes"
	"reflect"
	"strings"
	"testing"
)

func TestGroupRecipientsSamePolicy(t *testing.T) {
	recipients := []string{"alice@laissepasser.fr", "carol@laissepasser.fr"}
	groups := groupRecipients(recipients)

	if len(groups) != 1 || !reflect.DeepEqual(groups["1"], recipients) {
		t.Fatalf("unexpected groups: %#v", groups)
	}
}

func TestGroupRecipientsMixedPolicies(t *testing.T) {
	groups := groupRecipients([]string{
		"alice@laissepasser.fr",
		"john@laissepasser.fr",
		"carol@laissepasser.fr",
	})

	if len(groups) != 2 {
		t.Fatalf("group count = %d, want 2: %#v", len(groups), groups)
	}
	if got, want := groups["1"], []string{"alice@laissepasser.fr", "carol@laissepasser.fr"}; !reflect.DeepEqual(got, want) {
		t.Fatalf("policy 1 recipients = %#v, want %#v", got, want)
	}
	if got, want := groups["2"], []string{"john@laissepasser.fr"}; !reflect.DeepEqual(got, want) {
		t.Fatalf("policy 2 recipients = %#v, want %#v", got, want)
	}
}

func TestWritePolicyMessageRemovesSpoofedHeaders(t *testing.T) {
	input := "From: sender@example.org\r\n" +
		"X-AgentJ-Policy: spoofed\r\n" +
		"\tcontinued spoof\r\n" +
		"Subject: test\r\n" +
		"x-agentj-policy: also spoofed\r\n" +
		" another continuation\r\n" +
		"\r\nbody\r\n"

	var output bytes.Buffer
	if err := writePolicyMessage(&output, strings.NewReader(input), "2"); err != nil {
		t.Fatal(err)
	}
	message := output.String()
	if got := strings.Count(strings.ToLower(message), strings.ToLower(policyHeader)+":"); got != 1 {
		t.Fatalf("policy header count = %d; message:\n%s", got, message)
	}
	if !strings.Contains(message, "X-AgentJ-Policy: 2\r\n") {
		t.Fatalf("trusted policy header missing; message:\n%s", message)
	}
	if strings.Contains(message, "spoofed") || strings.Contains(message, "continuation") {
		t.Fatalf("spoofed header was not fully removed; message:\n%s", message)
	}
}
