
## Description

AgentJ is intended to be the target of MX field for your mail domain. Properly configured, it will receive all your mails, filter them, and transfer them to your already existing mailboxes.  
It will also send validation mail to the unknown senders (mail adresses with which you never exchanged mail), so it need a proper setup to send mail, eventually using your mail domain.

#### services

- **app**: main AgentJ web interface (configuration for admins, usage for users)
- **db**: a MariaDB instance to store mails, user account, DKIM keys, authorized/banned senders, Amavis scores …
- **smtp**: a postfix instance that will receive the incoming e-mails and check them using **amavis** container (amavis/clamav/spamassassin service)
- **relay**: a Postfix instance needed to avoid loops when forwarding the released or white-listed e-mails to their recipients(s)
- **outsmtp**: a postfix instance that will handle outgoing e-mails, sent by local users (via their original smtp server) and check them using **outamavis**
- **amavis**: a container running Amavis/Spamassassin and ClamAV services which check incoming mail
- **outamavis**: same as **amavis** but used for outgoing e-mails sent by local users
- **opendkim**: verify incoming mail DKIM signature for incoming mail, and append signature for outgoing mail
- **policyd-rate-limit**: rate limiting service used by **outsmtp**, get policies from **db**
- *for tests only* **smtptest** and **badrelay**: see [tests](#tests) below

> By default *ClamAV* run in the amavis containers, but you can run it externally (directly on the host or in a separated docker container)

#### volumes

- *amavis_in*/*amavis_out* : Amavis databases
- *db* : MariaDB databases files
- *postqueue* : the incoming mail queue (for **smtp**)
- *outpostqueue* : the outgoing mail queue (for **outsmtp

## Usage

> It is not recommended to launch the stack as *root*. We recommend you to create a dedicated *docker* user (make sure it belongs to the *docker* group).

Except all external configuration (eg DNS) which will not be covered here for the moment, all you have to do is to configure `.env` using the documented `.env.example` and start the containers.

#### upgrade

***If you upgrade from an old (pre version 2) version

#### develop

You need to configure `.env` to set `VERSION`, `COMPOSE_FILE` and `UID`/`GID` then **build the containers locally** using `docker compose build` or directly `docker compose up --build -d`.

##### `COMPOSE_FILE`

- `compose.dev.yml` will mount the code from your dev folder into app container, to ease development; and expose database port and log on the host
- `compose.test.yml` will start 2 smtp servers and fix IP addresses of some containers. Also used in CI, it allows you to run [mail test script](../app/docker/tests/testmail.sh) from within the `app` container

> to manually run the mail tests (a good idea to check you dev install, but **don't use it on a production setup**), run `docker compose exec -u www-data app ./docker/tests/testmail.sh`

##### `UID`/`GID`

At least for a classic Docker installation on Linux, those allow to share permissions of files you'll want to edit in this git repo with users in the containers.

##### `VERSION`

Use `dev` or equivalent. As the dev setup require a local build of images it should not matter, but this way you're sure no existing image will be accidentally pulled, which can lead to weird errors.

## Communication matrix


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


