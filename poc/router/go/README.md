# AgentJ SMTP router POC

This small SMTP proxy uses the actively maintained, MIT-licensed
[`github.com/emersion/go-smtp`](https://github.com/emersion/go-smtp) library.
It spools each complete inbound DATA payload to a size-bounded temporary file,
groups envelope recipients by policy, then submits one downstream SMTP
transaction for each non-empty policy group. The envelope sender and the
recipients in each group are preserved.

The intentionally simple POC policy uses local-part length parity: odd lengths
select policy `1`, even lengths select policy `2`. Thus `alice` and `carol`
share policy `1`, while `john` uses policy `2`. Every downstream message has
all inbound `X-AgentJ-Policy` headers, including folded continuations, removed
before exactly one trusted header is added.

Configuration:

| Variable | Default | Purpose |
|---|---|---|
| `ROUTER_LISTEN` | `:2525` | SMTP listen address |
| `ROUTER_DOWNSTREAM` | `127.0.0.1:2526` | Downstream SMTP address |
| `ROUTER_SPOOL_DIR` | `/tmp/agentj-router` | Temporary spool directory |
| `ROUTER_MAX_MESSAGE_BYTES` | `41943040` | Maximum spooled DATA bytes |
| `ROUTER_READ_TIMEOUT` | `2m` | Inbound SMTP read timeout |
| `ROUTER_WRITE_TIMEOUT` | `2m` | Inbound SMTP write timeout |
| `ROUTER_DIAL_TIMEOUT` | `10s` | Downstream connection timeout |
| `ROUTER_COMMAND_TIMEOUT` | `30s` | Downstream command timeout |
| `ROUTER_SUBMISSION_TIMEOUT` | `2m` | Downstream DATA write and response timeout |

The spool directory must be writable by the process. Temporary files are
removed after every success or failure. The minimal container runs as UID/GID
`65532`. The complete POC runs this router only on the Postfix loopback network.

## Verify

```sh
gofmt -w *.go
go test ./...
go vet ./...
```

## Known delivery risk

The downstream submissions cannot be atomic across policy groups. If one group
is accepted and a later group fails, the router returns a temporary SMTP
failure, causing Postfix to retry the group that already succeeded. A
production router needs durable per-group delivery state or a Postfix-native
split to resolve this partial-success risk.

An ambiguous downstream commit can produce the same duplicate even with one
policy group. DSN and SMTPUTF8 envelope options are not forwarded, and the POC
does not yet bound concurrent spool use across connections.

## AgentJ integration

The opt-in `compose.milter-poc.yml` overlay configures this path:

```text
Amavis -> Postfix :10025 -> router :10030 -> Postfix :10026
       -> URL milter -> OpenDKIM-final -> Rspamd ARC -> delivery
```

The integration test proves that recipients sharing policy `1` remain in one
SMTP transaction, while recipients assigned to policies `1` and `2` produce
two transactions. It verifies replacement of an untrusted inbound policy
header and independently validates the final DKIM and ARC signatures:

```sh
make poc-test-pipeline
```

The complete startup procedure and limitations are documented in
[`docs/poc-reecriture-url.md`](../../../docs/poc-reecriture-url.md).
