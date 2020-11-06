# AgentJ dockerized repository

This is the Docker stack to set up a fully functional AgentJ antispam system at a glance.

## Introduction

The AgentJ Docker stack is composed of the following services:

- **db**: a MariaDB instance, it keeps track of the mail headers and other information needed to manage the e-mails life cycle (sender, recipient, amavis id, ...)
- **app**: a Web UI that allows you to add and manage your domains and associated users as well as managing the incoming e-mails (block, release, white/black lists)
- **web**: a Nginx based reverse proxy that serves the **app**
- **stmp**: a Postfix instance that will receive the e-mails and forward them to the **amavis** container (Amavis/ClamAV/Spamassassin service)
- **amavis**: a container running Amavis/Spamassassin and ClamAV services
- **redis**: a Redis instance used as cache for the **app**
- **logspout + syslogng**: a Syslog-NG instance that will collect and centralize logs from the other containers
- **relay**: an other Postfix instance, needed to avoid loops when forwarding the released or white-listed e-mails to their receipient(s)

## Get the sources

All you have to do is to clone the repository

    git clone https://github.com/Probesys/agentj-docker.git

then, `cd` to the cloned repository to configure a few variables:

    cd agentj-docker

## Configure

### Variables

The following runtime variables must be configured:

| Variable         | Default        | Use                                         |
|------------------|----------------|---------------------------------------------|
| DB_ROOT_PASSWORD | secret         | the MariaDB instance root password          |
| DB_NAME          | agentj         | the AgentJ database name                    |
| DB_USER          | agentj         | the AgentJ database user name               |
| DB_PASSWORD      | secret         | the AgentJ database password                |
| IPV4_NETWORK     | 172.42.42      | the AgentJ Docker default network           |
| MAIL_HOSTNAME    | aj.example.com | the mailname used in postfix configuration  |
| MAIL_DOMAINNAME  | example.com    | the domain name used in relay configuration |
| TZ               | Europe/Paris   | the containers default timezone             |

### Network

The AgentJ antispam stack has its own Docker bridge `br-agentj` and IPv4 subnet which defaults to `172.42.42.0/24` (configurable, see variables table above).

## Use

It is not recommended to launch the stack as *root*. We recommend you to create a dedicated *docker* user (make sure it belongs to the *docker* group).
After you have set the above variables, you can start the stack with following commands:

    docker-compose up -d

The Web UI will be available at http://hostname:8080.
The default login is `admin` and the default password is `lutte antispam` (yes, this is a space between the two words).

## Details

### Volumes

When started, the AgentJ stack will create the following volumes:

- *public*: the **app** sources files
- *db* : the MariaDB databases files
- *redisdb*: Redis DB dumps
- *clamdb*: the ClamAV virus signatures database, updated 4 times a day
- *quarantine*: the quarantined e-mails
- *logs*: the log files from all containers, centralized by the **syslogng** container

### Communication matrix

| from ↓ \ to →           | amavis        | app          | db           | redis | relay      | smtp          | syslog      | web |
|-------------------------|---------------|--------------|--------------|-------|------------|---------------|-------------|-----|
| amavis (10024/tcp)      | -             | -            | ? → 3306/tcp | -     | -          | ? → 10025/tcp | ? → 514/udp | -   |
| app (9000/tcp)          | ? → 9998/tcp  | -            | ? → 3306/tcp | -     | ???        | ? → 514/udp   | -           | -   |
| db (3306/tcp)           | -             | -            | -            | -     | -          | -             | ? → 514/udp | -   |
| redis ()                | -             | -            | -            | -     | -          | -             | ? → 514/udp | -   |
| relay 25/tcp)           | -             | -            | -            | -     | -          | -             | ? → 514/udp | -   |
| stmp (25/tcp 10025/tcp) | ? → 10024/tcp | -            | ? → 3306/tcp | -     | ? → 25/tcp | ? → 514/udp   | -           | -   |
| syslogng (514/udp)      | -             | -            | -            | -     | -          | -             | ? → 514/udp | -   |
| web (8080/tcp)          | -             | ? → 9000/tcp | -            | -     | -          | -             | ? → 514/udp | -   |

## Upgrade

In order to upgrade the AgentJ dockerized stack, you must stop the running containers, pull (or build locally if you prefer) the updated images, remove the `public` volume and start the updated stack:

    docker-compose down
    # Delete and update only app image
    docker image rm $(docker image ls -q -f reference=*/*agentj_app)
    # Delete and update all images
    docker image rm $(docker image ls -q -f reference=*/*agentj_*)
    docker volume rm agentj-docker_public
    docker-compose up -d

## About

### License

This work is made available under the GNU Affero General Public License v3.0.

### Development

AgentJ is a [Probesys](https://www.probesys.com) project.
