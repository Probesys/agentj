# POC - URL Rewriting

## Status

This POC demonstrates a DKIM- and ARC-compatible transformation pipeline. It
is for development only: it must not process real messages or be enabled in
production.

```text
Postfix -> Rspamd auth -> Amavis -> policy router -> URL milter -> DKIM -> ARC -> Mailpit
```

The private keys under `poc/` are test fixtures only.

## Run and Verify

Prerequisites: Docker Compose, GNU Make, and a development `.env` file
(created automatically on first launch).

```sh
make poc-up
make poc-test
make poc-down
```

The checks can also be run separately:

```sh
make poc-test-auth
make poc-test-pipeline
make poc-test-failures
```

The exploratory benchmark remains available through
`./tests/authentication/benchmark.sh [message_count]`. It temporarily
changes the test listener's milters and restores its configuration afterwards;
it is not a capacity measurement.

## What Is Demonstrated

- Rspamd calculates SPF, DKIM, and DMARC before Amavis, removes forged
  `Authentication-Results`, and publishes the trusted result.
- The Go milter does not replace a body without URLs and rewrites HTTP(S) URLs
  in `text/plain` and HTML `href` values from the corpus.
- The router groups recipients with the same demonstration policy and separates
  divergent groups after removing the untrusted policy header.
- OpenDKIM signs the message after rewriting, and Rspamd ARC seals that
  version. Delivered-message signatures are verified with `dkimpy`.
- A final OpenDKIM or Rspamd ARC failure defers the message and allows delivery
  after recovery, without partially signed messages.

## Known Limitations

- The router policy is deliberately artificial (recipient local-part parity),
  and the milter does not apply it yet. The split proves the mechanism, not the
  production policy.
- The router does not guarantee fan-out atomicity: a retry after partial
  success can create a duplicate. DSN, SMTPUTF8, and the global spool quota are
  not supported.
- `go-milter` can panic on a malformed frame. MIME reconstruction and HTML
  processing are not yet robust enough for production.
- Inbound ARC and an Rspamd auth failure are not covered.
- The benchmark is local and sequential; it measures neither concurrency nor
  system capacity.

## Related Documents

- [Target Architecture](/architecture-agentj-url-rewriting.md)
- [Product Specifications](/docs/ticket-specifications-reecriture-url.md)
