package main

import (
	"errors"
	"os"
	"testing"
)

func TestCapacityBoundsConcurrencyAndBytes(t *testing.T) {
	capacity, err := newCapacity(t.TempDir(), 16, 8, 1)
	if err != nil {
		t.Fatal(err)
	}
	first, err := capacity.begin()
	if err != nil {
		t.Fatal(err)
	}
	if _, err := capacity.begin(); !errors.Is(err, errWorkersExhausted) {
		t.Fatalf("second transaction error = %v, want %v", err, errWorkersExhausted)
	}
	if _, err := first.input.Write([]byte("12345678")); err != nil {
		t.Fatal(err)
	}
	if _, err := first.input.Write([]byte("9")); !errors.Is(err, errSpoolExhausted) {
		t.Fatalf("budget error = %v, want %v", err, errSpoolExhausted)
	}
	if err := first.cleanup(); err != nil {
		t.Fatal(err)
	}
	if capacity.budget.usage() != 0 {
		t.Fatalf("budget usage = %d after cleanup, want 0", capacity.budget.usage())
	}
	entries, err := os.ReadDir(capacity.dir)
	if err != nil {
		t.Fatal(err)
	}
	if len(entries) != 0 {
		t.Fatalf("spool contains %d files after cleanup", len(entries))
	}
}

func TestCapacityRemovesStaleFilesAtStartup(t *testing.T) {
	dir := t.TempDir()
	stale := dir + "/agentj-input-stale"
	if err := os.WriteFile(stale, []byte("stale"), 0o600); err != nil {
		t.Fatal(err)
	}
	if _, err := newCapacity(dir, 16, 32, 1); err != nil {
		t.Fatal(err)
	}
	if _, err := os.Stat(stale); !errors.Is(err, os.ErrNotExist) {
		t.Fatalf("stale file still exists: %v", err)
	}
}
