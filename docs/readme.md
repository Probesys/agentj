# Technical description

AgentJ is intended to be set as your mail domain MX, and as relay for your SMTP server. It will send mail from the web domain and, depending on the configuration, from your mail domain.
Users authentication can be made via IMAP, LDAP or Microsoft Azure.

## services

- **app**: main AgentJ web interface (configuration for admins, usage for users)
- **db**: a MariaDB instance to store mails, domains configuration, users info, DKIM keys, authorized/banned senders, Amavis scores …
- **smtp**: a postfix instance that will receive the incoming e-mails and check them using **amavis** container
- **outsmtp**: a postfix instance that will handle outgoing e-mails, sent by local users (via their original smtp server) and check them using **outamavis**
- **amavis**: a container running Amavis/Spamassassin
- **outamavis**: same as **amavis** but used for outgoing e-mails sent by local users
- **opendkim**: verify incoming mail DKIM signature for incoming mail, and append signature for outgoing mail
- **policyd-rate-limit**: rate limiting service used by **outsmtp**, get policies from **db**
- **senderverifmilter**: custom postfix milter to secure multi-domains AgentJ instances
- *optionnal* **clamav**: ClamAV instance used by both amavis containers. An external instance can be used instead

### services for dev/tests

- **mailpit** will catch all mail, from or to agentj
- **smtptest** runs opensmtpd and dnsmasq. It can send mail to agentj on the behalf of configured domains; relay mail from agentj to mailpit, provide DNS for correct DKIM verification of tests mail
- **badrelay** an opensmtpd instance not authorized to sent mail via agentj

## volumes

- *amavis_in*/*amavis_out* : Amavis databases
- *db* : MariaDB databases files
- *postqueue* : the incoming mail queue (for **smtp**)
- *outpostqueue* : the outgoing mail queue (for **outsmtp**)

## Usage

> It is not recommended to launch the stack as *root*. We recommend you to create a dedicated *docker* user (make sure it belongs to the *docker* group).

Except external mail configuration which is not covered here, all you have to do is to configure `.env` using the documented `.env.example` and start the containers.
You can then access the web interface to configure your mail domain.

## Upgrade

> Always check the [changelog](../CHANGELOG.md) / [releases notes](https://github.com/Probesys/agentj/releases) ***before*** begining the upgrade process, and do a backup (database, volumes).

1. ensure you're using the correct `docker-compose.yml` version
2. upgrade your `.env`: set `VERSION` and check changes from `.env.example`
3. run something like `docker compose pull ; docker compose up -d`

## Develop

You need to configure `.env` to set `VERSION`, `COMPOSE_FILE` and `UID`/`GID` then **build the containers locally** using `docker compose build` or directly `docker compose up --build -d`.

> see [`docs/dev_mail.md`](./dev_mail.md) for information about how to send and receive mail in a dev installation

### `COMPOSE_FILE`

`compose.dev.yml` will 
- start [mailpit](https://mailpit.axllent.org)
- mount the code from your dev folder into the app container
- expose database port and log on the host
- set static IPs and specify DNS for some containers

> to manually run the mail tests (a good idea to check your dev install, but **not on a production setup**), run `docker compose exec -u www-data app ./docker/tests/testmail.sh`

### `UID`/`GID`

At least for a classic Docker installation on Linux, those allow to share permissions of files from this repo with users in the containers.

### `VERSION`

Use `dev` or equivalent. As the dev setup require a local build of images it should not matter, but this way you're sure no existing image will be accidentally pulled, which can lead to weird errors as versions will not match.
