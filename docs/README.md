# AgentJ documentation

AgentJ is intended to be set as your email domain MX, and as relay for your SMTP server.
It sends emails from the web domain and, depending on the configuration, from your email domain.
Users authentication can be made via IMAP, LDAP or Microsoft Azure.

## Infrastructure overview

AgentJ runs over Docker images.
You can learn more by opening the file [`docker-compose.yml`](/docker-compose.yml).

Services:

- **app**: the AgentJ web interface (configuration for admins, usage for users)
- **db**: a MariaDB instance to store emails, domains configuration, users info, DKIM keys, authorized/banned senders, Amavis scores…
- **smtp**: a postfix instance that will receive the incoming emails and check them using **amavis** container
- **outsmtp**: a postfix instance that will handle outgoing emails, sent by local users (via their original smtp server) and check them using **outamavis**
- **amavis**: a container running Amavis/Spamassassin
- **outamavis**: same as **amavis** but used for outgoing emails sent by local users
- **opendkim**: verify incoming email DKIM signature for incoming email, and append signature for outgoing email
- **policyd-rate-limit**: rate limiting service used by **outsmtp**, get policies from **db**
- **senderverifmilter**: custom postfix milter to secure multi-domains AgentJ instances
- **clamav** (optional): ClamAV instance used by both amavis containers. An external instance can be used instead

Services used during dev/tests:

- **mailpit**: catch all emails, from or to AgentJ
- **smtptest**: runs opensmtpd and dnsmasq. It can send email to AgentJ on the behalf of configured domains; relay email from AgentJ to mailpit, provide DNS for correct DKIM verification of tests email

Volumes:

- **db** : MariaDB database files
- **amavis_in** and **amavis_out** : Amavis databases
- **postqueue** : the incoming email queue (for **smtp**)
- **outpostqueue** : the outgoing email queue (for **outsmtp**)

## Production

### Usage

Get the code:

```console
$ git clone https://github.com/Probesys/agentj.git
$ cd agentj
```

Configure the application:

```console
$ cp .env.example .env
```

Edit the `.env` file to your needs.
The file is commented to help you to change it.

Then start the Docker stack:

```console
$ docker compose up
```

> [!CAUTION]
> It is not recommended to launch the stack as root.
> We recommend you to create a dedicated `docker` user (make sure it belongs to the `docker` group).

You can then access the web interface to configure your email domain.

### Upgrade

> [!IMPORTANT]
> Always backup your data (database and volumes) and check the [changelog](/CHANGELOG.md) **before** beginning the upgrade process.
> Breaking changes are highlighted in the migration notes for each version.

Update the code to the latest version (see [releases](https://github.com/Probesys/agentj/releases)):

```console
$ git fetch
$ git switch <VERSION>
```

Update the `VERSION` variable in the `.env` file and adapt other variables accordingly to the migration notes.

Restart the stack:

```console
$ docker compose pull
$ docker compose up -d
```

## Development

Get the code:

```console
$ git clone git@github.com:Probesys/agentj.git
$ cd agentj
```

Start the application:

```console
$ make docker-start
```

The command automatically setup an `.env` file suitable for development.

Login to [localhost:8090](http://localhost:8090) with the credentials `admin` / `secret`.

Read more to learn [how to send and receive emails during development.](/docs/dev_mail.md)

### Working in the Docker containers

There are few scripts to allow to execute commands in the Docker containers easily:

```
$ ./scripts/php
$ ./scripts/composer
$ ./scripts/console
$ ./scripts/yarn
$ ./scripts/mariadb
```

### Update the Docker images

You can rebuild and pull the images manually with:

```console
$ make docker-images
```

### Run the linters

Execute the linters with:

```console
$ make lint
```

You can run a specific linter with:

```console
$ make lint LINTER=phpstan
$ make lint LINTER=phpcs
$ make lint LINTER=symfony
```

### Clean your Docker environment

Sometimes, you may need to rebuild your environment from scratch.
You can remove all the Docker stuff (containers, volumes, networks) with:

```console
$ make docker-clean FORCE=true
```

### About the `compose.dev.yml` file

This file is used to override the Docker Compose configuration in development:

- it starts [Mailpit](https://mailpit.axllent.org)
- it mounts the code from your local `app/` folder into the app container
- it exposes the database port to the host
- it sets static IPs and specify DNS for some containers

### Release a version

Read the documentation [to release a version.](/docs/release.md)
