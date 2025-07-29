# How to send and receive mail in a dev install

## quick recall, agentj can …  
- handle all *incoming* mail *for* a domain. We speak of mail ***to*** agentj. Those mail are then transfered to the protected domain SMTP server  
- handle all *outgoing* mail *from* a domain. We speak of mail ***via*** agentj. Those mail are sent to the original recipient, so to whatever SMTP server on the internet  
- send mail on his own: alert, report or validation mail. Those mail are sent, depending of the configuration (in the web interface) or as `SMTP_FROM` (defined in the env file).

> agentj will accept any mail to configured domains on `smtp` container, but to send mail ***via*** agentj you'll need to allow the sender smtp ip in the web interface.

**In a dev installation with the default tests data, all these mail are catched by mailpit**.  
If you manually add a domain, set the smtp to `smtp.test` and port to `25` or smtp to `mailpit.test` and port to `1025`.  

## using the test scripts

From the docker host, you can run `tests/tests.sh` or scripts from `scripts/` folder.

> The `tests.sh` script try to load the [fixtures](../app/src/DataFixtures) and will display a warning if they are already in the database.  

`tests/tests.sh` sends mail *via* and *to* agentj, and use the mailpit api to verify what was received. It needs a clean database (use `reset_db.sh` or `docker compose down -v/up`).  
The 2 others scripts also send mail in both direction but don't do any checks, they are mainly useful to tests quota and alerts.

## manually send mail

You can send mail using any smtp client. `swaks` is installed in `app` in dev mode, but can also be used from your host. You can use the following smtp server :
- ***to*** agentj using `smtp` container, on `$SMTP_LISTEN_ADDRESS:$SMTP_PORT` (probably `127.0.0.1:2552`)
- ***via*** agentj using `outsmtp` container, on `$SMTP_LISTEN_ADDRESS:$SMTP_OUT_PORT` (probably `127.0.0.1:2662`)
- via `smtptest`, see below

### preconfigured smtp servers for dev and tests

`smtptest` listen [on 3 ports](../tests/smtpd.conf)
- `25` for mail sent by agentj to the internet and to domains configured as said previously. Those mail are transfered to mailpit
- `26` for external mail sent ***to*** agentj (meaning, to one of a protected domain). Those mail are signed via DKIM and sent to agentj `smtp`
- `27` for mail sent from protected domain to the internet, so ***via*** agentj. They are sent trought `outsmtp`

> with default data from the fixtures, both mail sent to ports `26` and `27` will come back on port `25`
