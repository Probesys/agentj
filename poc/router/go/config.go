package main

import (
	"fmt"
	"os"
	"strconv"
	"time"
)

type config struct {
	ListenAddress     string
	DownstreamAddress string
	SpoolDirectory    string
	MaxMessageBytes   int64
	ReadTimeout       time.Duration
	WriteTimeout      time.Duration
	DialTimeout       time.Duration
	CommandTimeout    time.Duration
	SubmissionTimeout time.Duration
}

func loadConfig() (config, error) {
	cfg := config{
		ListenAddress:     envOr("ROUTER_LISTEN", ":2525"),
		DownstreamAddress: envOr("ROUTER_DOWNSTREAM", "127.0.0.1:2526"),
		SpoolDirectory:    envOr("ROUTER_SPOOL_DIR", "/tmp/agentj-router"),
	}

	var err error
	if cfg.MaxMessageBytes, err = envPositiveInt64("ROUTER_MAX_MESSAGE_BYTES", 40*1024*1024); err != nil {
		return config{}, err
	}
	if cfg.ReadTimeout, err = envDuration("ROUTER_READ_TIMEOUT", 2*time.Minute); err != nil {
		return config{}, err
	}
	if cfg.WriteTimeout, err = envDuration("ROUTER_WRITE_TIMEOUT", 2*time.Minute); err != nil {
		return config{}, err
	}
	if cfg.DialTimeout, err = envDuration("ROUTER_DIAL_TIMEOUT", 10*time.Second); err != nil {
		return config{}, err
	}
	if cfg.CommandTimeout, err = envDuration("ROUTER_COMMAND_TIMEOUT", 30*time.Second); err != nil {
		return config{}, err
	}
	if cfg.SubmissionTimeout, err = envDuration("ROUTER_SUBMISSION_TIMEOUT", 2*time.Minute); err != nil {
		return config{}, err
	}
	return cfg, nil
}

func envOr(name, fallback string) string {
	if value := os.Getenv(name); value != "" {
		return value
	}
	return fallback
}

func envPositiveInt64(name string, fallback int64) (int64, error) {
	value, err := strconv.ParseInt(envOr(name, strconv.FormatInt(fallback, 10)), 10, 64)
	if err != nil || value <= 0 {
		return 0, fmt.Errorf("%s must be a positive integer", name)
	}
	return value, nil
}

func envDuration(name string, fallback time.Duration) (time.Duration, error) {
	value, err := time.ParseDuration(envOr(name, fallback.String()))
	if err != nil || value <= 0 {
		return 0, fmt.Errorf("%s must be a positive duration", name)
	}
	return value, nil
}
