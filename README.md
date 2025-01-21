# AgentJ dockerized repository

This is the Docker stack to set up a fully functional AgentJ antispam system at a glance.

## Introduction

The AgentJ Docker stack is composed of the following services:

- **app**: main AgentJ interface (configuration for admins, usage for users)
- **db**: a MariaDB instance to store mails, user account, DKIM keys, allowed/banned senders, Amavis scores …
- **smtp**: a postfix instance that will receive the incoming e-mails and forward them to the **amavis** container (amavis/clamav/spamassassin service)
- **outsmtp**: a postfix instance that will handle outgoing e-mails, sent by local users (via their original smtp server) and pass them to **outamavis**
- **relay**: an other Postfix instance, needed to avoid loops when forwarding the released or white-listed e-mails to their recipients(s)
- **amavis**: a container running Amavis/Spamassassin and ClamAV services which check incoming mail
- **outamavis**: same as **amavis** but used for outgoing e-mails sent by local users
- **opendkim**: verify incoming mail DKIM signature for incoming mail, and append signature for outgoing mail
- **policyd-rate-limit**: rate limiting service used by **outsmtp**, get policies from **db**
- *wip* **logspout + syslogng**: a Syslog-NG instance that will collect and centralize logs from the other containers
- *for tests only* **smtptest** and **badrelay**: see [tests](#tests) below

## Get the sources

All you have to do is to clone the repository

    git clone https://github.com/Probesys/agentj.git

then, `cd` to the cloned repository to configure a few variables:

    cd agentj

## Configure

### Variables

Variables are defined in the `.env.example`. This file is just a template, you **must** copy it to `.env` and adapt variables to you need

## Use

It is not recommended to launch the stack as *root*. We recommend you to create a dedicated *docker* user (make sure it belongs to the *docker* group).
After you have set the above variables, you can start the stack with following commands:

    docker compose up -d

The Web UI will be available at http://hostname:8090.
The default login is `admin` and the default password is `changeme`.

### Development

In .env set `COMPOSE_FILE=docker-compose.yml:compose.dev.yml`, and `DB_EXPOSED_PORT`.

### Tests

Set `COMPOSE_FILE=docker-compose.yml:compose.dev.yml:compose.test.yml`, then run `docker compose exec -u www-data app ./docker/tests/testmail.sh`

## Details

### Volumes

When started, the AgentJ stack will create the following volumes:

- *amavis_in*/*amavis_out* : Amavis databases
- *applogs* : application logs (cron, web server logs …)
- *db* : MariaDB databases files
- wip *logs*: log files from all containers, centralized by the **syslogng** container
- *postqueue* : the incoming mail queue (for **smtp**)
- *outpostqueue* : the outgoing mail queue (for **outsmtp**)

### Communication matrix

| from ↓ \ to →                 | amavis        | outamavis       | app          | db           | relay      | smtp          | outsmtp       | opendkim      | policyd-rate-limit |
|-------------------------------|---------------|-----------------|--------------|--------------|------------|---------------|---------------|---------------|--------------------|
| amavis (10024/tcp)            | -             | -               | -            | ? → 3306/tcp | -          | ? → 10025/tcp |               | -             |
| outamavis (10024/tcp)         | -             | -               | -            | ? → 3306/tcp | -          |               | ? → 10025/tcp | -             |
| app (8090/tcp)                | ? → 9998/tcp  |                 | -            | ? → 3306/tcp | ???        | -             |               | -             |
| db (3306/tcp)                 | -             | -               | -            | -            | -          | -             | -             | -             |
| opendkim (8891/tcp)           | -             | -               | -            | ? → 3306/tcp | -          | -             | -             | -             |
| relay (25/tcp)                | -             | -               | -            | -            | -          | -             | -             | ? → 8891/tcp  |
| smtp (25/tcp 10025/tcp)       | ? → 10024/tcp |                 | -            | ? → 3306/tcp | ? → 25/tcp | -             |               | ? → 8891/tcp  |
| outsmtp (26/tcp 10025/tcp)    |               | ? → 10024/tcp   | -            | ? → 3306/tcp |            |               |               | ? → 8891/tcp  | ? → 8552/tcp
| policyd-rate-limit (8552/tcp) |               |                 |              | ? → 3306/tcp |            |               |               |               |

## Upgrade

Please read the [dedicated documentation](https://doc.agentj.io/infra/upgrade/) as well as releases notes before upgrading.

Generally speaking, the upgrade processes consists in the following:

    docker compose down
    # Change VERSION variable in your `.env` file
    docker compose up -d

## About

### License

This work is made available under the GNU Affero General Public License v3.0.

### Development

AgentJ is a [Probesys](https://www.probesys.coop) project.
