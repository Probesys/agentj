
## Technical description

AgentJ is intended to be set as your mail domain MX, and as relay for your SMTP server. It will send mail from the web domain and, depending on the configuration, from your mail domain.  
Users authentication can be made via IMAP, LDAP or Microsoft Azure.

#### services

- **app**: main AgentJ web interface (configuration for admins, usage for users)
- **db**: a MariaDB instance to store mails, domains configuration, users info, DKIM keys, authorized/banned senders, Amavis scores …
- **smtp**: a postfix instance that will receive the incoming e-mails and check them using **amavis** container
- **relay**: a Postfix instance needed to avoid loops when forwarding the released or white-listed e-mails to their recipients(s)
- **outsmtp**: a postfix instance that will handle outgoing e-mails, sent by local users (via their original smtp server) and check them using **outamavis**
- **amavis**: a container running Amavis/Spamassassin and ClamAV services
- **outamavis**: same as **amavis** but used for outgoing e-mails sent by local users
- **opendkim**: verify incoming mail DKIM signature for incoming mail, and append signature for outgoing mail
- **policyd-rate-limit**: rate limiting service used by **outsmtp**, get policies from **db**
- *for tests only* **smtptest** and **badrelay**: see [tests](#tests) below

> By default *ClamAV* run in the amavis containers, but you can run it externally (directly on the host or in a separated container). See `.env.example` for details

#### volumes

- *amavis_in*/*amavis_out* : Amavis databases
- *db* : MariaDB databases files
- *postqueue* : the incoming mail queue (for **smtp**)
- *outpostqueue* : the outgoing mail queue (for **outsmtp

## Usage

> It is not recommended to launch the stack as *root*. We recommend you to create a dedicated *docker* user (make sure it belongs to the *docker* group).

Except all external mail configuration which is not covered here, all you have to do is to configure `.env` using the documented `.env.example` and start the containers.
You can then access the web interface to configure your mail domain.

### upgrade

> Always check the [changelog](../CHANGELOG.md) / [releases notes](https://github.com/Probesys/agentj/releases) ***before*** begining the upgrade process, and do a backup (database, volumes).

1. ensure you're using the correct `docker-compose.yml` version
2. upgrade your `.env`: set `VERSION` and check changes from `.env.example`
3. run something like `docker compose pull ; docker compose up -d`

### develop

You need to configure `.env` to set `VERSION`, `COMPOSE_FILE` and `UID`/`GID` then **build the containers locally** using `docker compose build` or directly `docker compose up --build -d`.

##### `COMPOSE_FILE`

- `compose.dev.yml` will mount the code from your dev folder into app container; and expose database port and log on the host
- `compose.test.yml` will start 2 smtp servers and fix IP addresses of some containers. Also used in CI, it allows you to run [mail test script](../app/docker/tests/testmail.sh) from within the `app` container

> to manually run the mail tests (a good idea to check your dev install, but **not on a production setup**), run `docker compose exec -u www-data app ./docker/tests/testmail.sh`

##### `UID`/`GID`

At least for a classic Docker installation on Linux, those allow to share permissions of files you'll want to edit in this git repo with users in the containers.

##### `VERSION`

Use `dev` or equivalent. As the dev setup require a local build of images it should not matter, but this way you're sure no existing image will be accidentally pulled, which can lead to weird errors.
