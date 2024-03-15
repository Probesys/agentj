# AgentJ dockerized repository

This is the Docker stack to set up a fully functional AgentJ antispam system at a glance.

## Introduction

The AgentJ Docker stack is composed of the following services:

- **db**: a MariaDB instance, it keeps track of the mail headers and other information needed to manage the e-mails life cycle (sender, recipient, amavis id, ...)
- **app**: a Web UI that allows you to add and manage your domains and associated users as well as managing the incoming e-mails (block, release, white/black lists)
- **smtp**: a Postfix instance that will receive the e-mails and forward them to the **amavis** container (Amavis/ClamAV/Spamassassin service)
- **outsmtp**: a Postfix instance that will handle sent mails by local user (transmitted via their original smtp server) once checked by **outamavis**
- **amavis**: a container running Amavis/Spamassassin and ClamAV services which check incoming mail
- **outamavis**: same as **amavis** but used for mail sent by local user (by their original smtp server)
- **logspout + syslogng**: a Syslog-NG instance that will collect and centralize logs from the other containers
- **relay**: an other Postfix instance, needed to avoid loops when forwarding the released or white-listed e-mails to their recipients(s)

## Get the sources

All you have to do is to clone the repository

    git clone https://github.com/Probesys/agentj.git

then, `cd` to the cloned repository to configure a few variables:

    cd agentj

## Configure

### Variables

Variables are defined in the `.env.example`. This file is just a template, you **must** copy and rename it to `.env`:

    cp .env.example .env

Then the following runtime variables must be configured in the `.env` file:

| Variable                  | Default        | Use                                         |
|---------------------------|----------------|---------------------------------------------|
| VERSION                   |                | this AgentJ latest prod version             |
| COMPOSE_PROJECT_NAME      | local          | this AgentJ instance name                   |
| DB_ROOT_PASSWORD          | secret         | the MariaDB instance root password          |
| DB_NAME                   | agentj         | the AgentJ database name                    |
| DB_USER                   | agentj         | the AgentJ database user name               |
| DB_PASSWORD               | secret         | the AgentJ database password                |
| MAIL_HOSTNAME             | aj.example.com | the mailname used in postfix configuration  |
| MAIL_DOMAINNAME           | example.com    | the domain name used in relay configuration |
| SUPER_ADMIN_USERNAME      | admin          | default super admin login                   |
| SUPER_ADMIN_PASSWORD      | Sup3rZECR37    | default super admin password                |
| TZ                        | Europe/Paris   | the containers default timezone             |
| PROXY_PORT                | 8090           | default listening port for web interface    |
| PROXY_LISTEN_ADDR         | 127.0.0.1      | default listening address for web interface |
| SMTP_PORT                 | 25             | default listening port for in smtp server   |
| SMTP_OUT_PORT             | 26             | default listening port for out smtp server  |
| SMTP_LISTEN_ADDR          | 0.0.0.0        | default listening address for smtp server   |
| OAUTH_AZURE_CLIENT_SECRET | secret         | client secret if using Azure auth           |
| OAUTH_AZURE_CLIENT_ID     | secret         | client ID if using Azure auth               |
| ENABLE_AZURE_OAUTH        | false          | enable Azure OAuth                          |
| TRUSTED_PROXIES           | 172.24.42.1    | default stack gateway                       |
| CLAMAV_AUTOSTART          | true           | use the ClamAV instance of this stack       |
| CLAMAV_TCPADDRESS         | 0.0.0.0        | remote ClamAV server IP address             |
| CLAMAV_TCPPORT            | 3310           | remote ClamAV server TCP port               |

For dev/tests:

| Variable                  | Default        | Use                                         |
| DB_EXPOSED_PORT           |                | if using dev compose file                   |
| IN_SMTP                   | smtp           | host (container) name of in smtp server     |
| OUT_SMTP                  | outsmtp        | host (container) name of out smtp server    |


## Use

It is not recommended to launch the stack as *root*. We recommend you to create a dedicated *docker* user (make sure it belongs to the *docker* group).
After you have set the above variables, you can start the stack with following commands:

    docker-compose up -d

The Web UI will be available at http://hostname:8090.
The default login is `admin` and the default password is `Sup3rZECR37`.

### Development

To expose database on host (set `DB_EXPOSED_PORT` in .env), mount app src, config and migrations directories in the running container, and start `mailpit` use  
`docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d`

## Details

### Volumes

When started, the AgentJ stack will create the following volumes:

- *amavis* : the Amavis databases, shared between the two instances
- *applogs* : the application logs (cron tasks)
- *db* : the MariaDB databases files
- *logs*: the log files from all containers, centralized by the **syslogng** container
- *opendkim* : DKIM signature and conf files
- *postqueue* : the incoming mail queue (for **smtp**)
- *outpostqueue* : the outgoing mail queue (for **outsmtp**)

### Communication matrix

*italic are to be verified*

| from ↓ \ to →              | amavis        | outamavis       | app          | db           | relay      | smtp          | outsmtp       | syslog        |
|----------------------------|---------------|-----------------|--------------|--------------|------------|---------------|---------------|---------------|
| amavis (10024/tcp)         | -             | -               | -            | ? → 3306/tcp | -          | ? → 10025/tcp |               | ? → 514/udp   |
| outamavis (10024/tcp)      | -             | -               | -            | ? → 3306/tcp | -          |               | ? → 10025/tcp | *? → 514/udp* |
| app (8090/tcp)             | ? → 9998/tcp  |                 | -            | ? → 3306/tcp | ???        | ? → 514/udp   |               | -             |
| db (3306/tcp)              | -             | -               | -            | -            | -          | -             | -             | ? → 514/udp   |
| relay 25/tcp)              | -             | -               | -            | -            | -          | -             | -             | ? → 514/udp   |
| stmp (25/tcp 10025/tcp)    | ? → 10024/tcp |                 | -            | ? → 3306/tcp | ? → 25/tcp | ? → 514/udp   |               | -             |
| outstmp (26/tcp 10025/tcp) |               | ? → 10024/tcp   | -            | ? → 3306/tcp |            |               | *? → 514/udp* | -             |
| syslogng (514/udp)         | -             | -               | -            | -            | -          | -             | -             | ? → 514/udp   |

## Upgrade

Please read the [dedicated documentation](https://doc.agentj.io/infra/upgrade/) as well as releases notes before upgrading.

Generally speaking, the upgrade processes consists in the following:

    docker-compose down
    # Change VERSION variable in your `.nev` file
    docker-compose up -d

## About

### License

This work is made available under the GNU Affero General Public License v3.0.

### Development

AgentJ is a [Probesys](https://www.probesys.com) project.
