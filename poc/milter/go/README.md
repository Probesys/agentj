# POC milter Go

A vertical slice of a content milter using `go-milter` for the protocol and
the `go-message` streaming parser for MIME. The POC keeps the incoming body in
a temporary spool, performs a detection pass, then reconstructs a candidate
body on disk only when necessary. It sends `SMFIR_REPLBODY` actions only when a
transformation actually occurred.

The demonstration transformation replaces HTTP(S) URLs in `text/plain` leaves
and HTTP(S) `href` values in `text/html` with:

```text
https://agentj.invalid/r?u=<original URL in base64url>
```

The prefix is recognized before transformation, making the result deterministic
and idempotent. Parts declared as attachments or with a `filename`/`name`
parameter are excluded.

## Build and Test

The Docker context must be the repository root to include the shared corpus
without duplicating it:

```sh
docker build --target build -f poc/milter/go/Dockerfile .
docker build -t agentj-milter-poc -f poc/milter/go/Dockerfile .
```

To run only the tests in a Go image:

```sh
docker run --rm \
  -v "$PWD/poc/milter/go:/src" \
  -v "$PWD/tests/milter/fixtures:/fixtures:ro" \
  -w /src \
  -e MILTER_FIXTURES=/fixtures \
  golang:1.23-alpine go test ./...
```

The protocol test starts a real `milter.Server`, submits messages to it using
the `go-milter` client, checks the no-op behavior, and verifies that a large
body is replaced by multiple actions at EOM.

## Test in AgentJ

The post-Amavis integration is deliberately opt-in until the POC is hardened.
The overlay runs the milter in the incoming Postfix network namespace and
exposes it only on its loopback interface:

```sh
make poc-up
make poc-test-pipeline
```

The test submits two messages to the external test SMTP server. They pass
through Postfix, Amavis, the `10025` reinjection listener, the policy router,
and the `10026` listener hosting the milter before delivery to Mailpit. It also
verifies that a shared policy preserves one transaction and that two policies
produce two transactions with their respective headers. Without this overlay,
`10025` retains its historical `no_milters` behavior.

Configuration through environment variables:

| Variable | Default | Purpose |
|---|---:|---|
| `MILTER_LISTEN` | `:9999` | Listening TCP address |
| `MILTER_SPOOL_DIR` | `/var/spool/agentj-milter` | Temporary spool, intended for a `tmpfs` |
| `MILTER_MAX_MESSAGE_BYTES` | `41943040` | Limit for headers + incoming body and output body |
| `MILTER_MAX_SPOOL_BYTES` | `536870912` | Shared budget for incoming and output files |
| `MILTER_MAX_CONCURRENCY` | `4` | Concurrent transactions |
| `MILTER_TRANSACTION_TIMEOUT` | `2m` | Cleanup for an abandoned transaction |

Pool saturation, exceeding the per-message limit, exhausting the global budget,
a MIME error, or a spool error result in `SMFIR_TEMPFAIL`. Files are deleted
after success, no-op, abort, and error. A timer recovers transactions whose
connection disappears without `ABORT`; at startup, `agentj-*` files left by an
abrupt stop are deleted. The spool directory must therefore be dedicated to a
single instance.

## Demonstrated Limitations

- `go-message` parses multiparts and exposes their decoded bodies as
  `io.Reader`. The POC streams copies and re-encodes non-target leaves, so it
  deliberately does not materialize attachments. Their transfer representation
  may change, but their decoded content is tested by hash.
- A targeted `text/plain` or `text/html` leaf is read entirely into memory so
  rewriting can detect URLs that cross read boundaries. Its size remains
  bounded by `MILTER_MAX_MESSAGE_BYTES`. The
  `TestTargetTextLeafIsBufferedByDesign` test makes this limit explicit, while
  `TestNonTargetLeafIsStreamed` covers the attachment path.
- Reconstruction is sequential but not byte-for-byte: part-header order and
  folding, transfer encoding, and MIME separators may be normalized. Multipart
  preambles and epilogues are not exposed separately by the API in use and are
  not preserved. The no-op never sends this reconstruction to the MTA.
- HTML detection is deliberately minimal: it handles quoted and unquoted
  `href` attributes without building a DOM. It is not a production HTML
  sanitizer.
- The timeout and startup cleanup compensate for the lack of a disconnect
  callback in `go-milter` v0.4.1. A killed process cannot, of course, delete its
  files until its next startup; the `tmpfs` also removes them with the
  container.

The full setup, authentication checks, and POC limitations are documented in
[`docs/poc-reecriture-url.md`](../../../docs/poc-reecriture-url.md).
