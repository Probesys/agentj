package main

import (
	"errors"
	"fmt"
	"log"
	"net"
	"os"
	"os/signal"
	"syscall"

	"github.com/emersion/go-milter"
)

func main() {
	if err := run(); err != nil {
		log.Fatal(err)
	}
}

func run() error {
	config, err := loadConfig()
	if err != nil {
		return fmt.Errorf("load configuration: %w", err)
	}
	capacity, err := newCapacity(config.SpoolDir, config.MaxMessageBytes, config.MaxSpoolBytes, config.MaxConcurrency)
	if err != nil {
		return fmt.Errorf("initialize capacity: %w", err)
	}
	listener, err := net.Listen("tcp", config.Listen)
	if err != nil {
		return fmt.Errorf("listen on %q: %w", config.Listen, err)
	}
	server := &milter.Server{
		Actions:  milter.OptChangeBody,
		Protocol: milter.OptNoConnect | milter.OptNoHelo | milter.OptNoRcptTo | milter.OptNoData,
		NewMilter: func() milter.Milter {
			return newFilter(capacity, config.Timeout)
		},
	}

	signals := make(chan os.Signal, 1)
	signal.Notify(signals, syscall.SIGINT, syscall.SIGTERM)
	go func() {
		<-signals
		if err := server.Close(); err != nil {
			log.Printf("close milter server: %v", err)
		}
	}()
	log.Printf("listening on %s, spool=%s, max_message=%d, max_spool=%d, concurrency=%d",
		config.Listen, config.SpoolDir, config.MaxMessageBytes, config.MaxSpoolBytes, config.MaxConcurrency)
	if err := server.Serve(listener); err != nil && !errors.Is(err, milter.ErrServerClosed) {
		return fmt.Errorf("serve milter protocol: %w", err)
	}
	return nil
}
