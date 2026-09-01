package main

import (
	"log"
	"os"

	"github.com/emersion/go-smtp"
)

func main() {
	cfg, err := loadConfig()
	if err != nil {
		log.Fatal(err)
	}
	if err := os.MkdirAll(cfg.SpoolDirectory, 0o700); err != nil {
		log.Fatalf("create spool directory: %v", err)
	}

	logger := log.New(os.Stderr, "router: ", log.LstdFlags)
	server := smtp.NewServer(&router{cfg: cfg, log: logger})
	server.Addr = cfg.ListenAddress
	server.Domain = "agentj-router"
	server.ReadTimeout = cfg.ReadTimeout
	server.WriteTimeout = cfg.WriteTimeout
	server.MaxMessageBytes = cfg.MaxMessageBytes

	logger.Printf("listening on %s; downstream %s", cfg.ListenAddress, cfg.DownstreamAddress)
	if err := server.ListenAndServe(); err != nil {
		logger.Fatal(err)
	}
}
