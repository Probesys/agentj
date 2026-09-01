package main

import (
	"fmt"
	"os"
	"strconv"
	"time"
)

const (
	defaultListen          = ":9999"
	defaultSpoolDir        = "/var/spool/agentj-milter"
	defaultMaxMessageBytes = 40 << 20
	defaultMaxSpoolBytes   = 512 << 20
	defaultMaxConcurrency  = 4
	defaultTimeout         = 2 * time.Minute
)

type Config struct {
	Listen          string
	SpoolDir        string
	MaxMessageBytes int64
	MaxSpoolBytes   int64
	MaxConcurrency  int
	Timeout         time.Duration
}

func loadConfig() (Config, error) {
	maxMessage, err := positiveInt64("MILTER_MAX_MESSAGE_BYTES", defaultMaxMessageBytes)
	if err != nil {
		return Config{}, err
	}
	maxSpool, err := positiveInt64("MILTER_MAX_SPOOL_BYTES", defaultMaxSpoolBytes)
	if err != nil {
		return Config{}, err
	}
	concurrency, err := positiveInt64("MILTER_MAX_CONCURRENCY", defaultMaxConcurrency)
	if err != nil {
		return Config{}, err
	}
	timeout, err := positiveDuration("MILTER_TRANSACTION_TIMEOUT", defaultTimeout)
	if err != nil {
		return Config{}, err
	}

	return Config{
		Listen:          envOrDefault("MILTER_LISTEN", defaultListen),
		SpoolDir:        envOrDefault("MILTER_SPOOL_DIR", defaultSpoolDir),
		MaxMessageBytes: maxMessage,
		MaxSpoolBytes:   maxSpool,
		MaxConcurrency:  int(concurrency),
		Timeout:         timeout,
	}, nil
}

func envOrDefault(name, fallback string) string {
	if value := os.Getenv(name); value != "" {
		return value
	}
	return fallback
}

func positiveInt64(name string, fallback int64) (int64, error) {
	value := os.Getenv(name)
	if value == "" {
		return fallback, nil
	}
	parsed, err := strconv.ParseInt(value, 10, 64)
	if err != nil {
		return 0, fmt.Errorf("parse %s=%q as a positive integer: %w", name, value, err)
	}
	if parsed <= 0 {
		return 0, fmt.Errorf("parse %s=%q as a positive integer: value must be greater than zero", name, value)
	}
	return parsed, nil
}

func positiveDuration(name string, fallback time.Duration) (time.Duration, error) {
	value := os.Getenv(name)
	if value == "" {
		return fallback, nil
	}
	parsed, err := time.ParseDuration(value)
	if err != nil {
		return 0, fmt.Errorf("parse %s=%q as a positive duration: %w", name, value, err)
	}
	if parsed <= 0 {
		return 0, fmt.Errorf("parse %s=%q as a positive duration: value must be greater than zero", name, value)
	}
	return parsed, nil
}
