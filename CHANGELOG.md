# v2.0.0

This release brings a main new feature: outgoing mails management. AgentJ
can now be set as relay of an SMTP server, which allows to
- configure send quota (for domains, groups or users)
- prevent unwanted sent mail: spams, virus, eg by a compromised mail account
- get mail alerts for quota overrun/blocked outgoing spam or virus
- get statistics about sent/received mails

Others improvments/bugfixes have been made, details below.

Improvments

* preview untreated mails
* handle outgoing mails: AgentJ can now be set as a relay on a SMTP server
* block outgoing virus/spam (with a different amavis configuration than incoming mails)
* send quota: domain administrator can configure quota for domains, groups or users
* alerts by mail and/or on dashboard when an internal user send a virus, a spam or exceed a quota
* sending a mail to an unknow mail address automatically add it to «Authorized senders»
* improved dashboard: show statistics about received & sent mails
* new search page to find messages by amavis score, sender, receiver …
* show DKIM public keys (DNS format) for domains
* many UI improvments

Bug fixes

* prevent loss of encryption token when restarting app
* unified date format in different views
* fixed language switch
* security fixes

Technical

* upgrade to Symfony 7
* new dev setup, easily configurable in .env
* add basic tests for sent and received mails (block unknow users/virus/spam, in both way; allow an expeditor by sending a mail; quota)
* unification of authentication methods (IMAP/LDAP/M365)
* all containers are now based on Debian 12
* DKIM keys are now generated via PHP and stored in the database

## Manual actions

**Before** starting the upgrade, copy DKIM keys and Symfony tokens from the running instance (see helper script `scripts/get-dkim-sf-encryption-token-pre-agentj2.sh`)  
- add tokens to `.env`, and update this file from `.env.example`
- pull and start the new images, then wait for the end of the migrations and installation of dependencies
- insert the previously saved DKIM keys in the migrated database
- run `docker compose exec db /docker-entrypoint-initdb.d/opendkim_user.sh` to create the new opendkim user
- ensure nginx log folder are ok `docker compose exec app 'mkdir -p /var/log/nginx ; chown -R www-data:www-data /var/log/nginx'`

# v1.6.4

If you use oauth authentication you need to create an Office365 connector, configure it with your Microsoft Entra application and synchronize agentj users.

# v1.5.3

This version improves the way virus mail are handled and displayed in the Web interface. There is now a dedicated section for such mails in which they cannot be released (for security reasons, if releasing the mail is legitimate it should be done manually on the server side by an admin).
It also adds the ability to use a remote ClamAV server so you **must** add the following variables in your `.env` file:

```
CLAMAV_AUTOSTART=true
CLAMAV_TCPADDRESS=0.0.0.0
CLAMAV_TCPPORT=3310
```

By default, the *clamav* service, installed in the *amavis* container will start. If CLAMAV_AUTOSTART is set to *false*, it won't start and *amavis* will try to connect ClamAV at the IP address configured in CLAMAV_TCPADDRESS.

# v1.5.0

This version adds [OAuth with Azure](01-installation.md#oauth-with-azure). Please refer to [dedicated deployement documentation](01-installation.md#oauth-with-azure) if you need this feature.

# v1.4.3

This version improves the startup process of critical components and removes some volumes.

### Changes in Amavis

In the *amavis* service, the following volumes have been removed:

* clamav
* spamassassin

The signatures databases of these services are now generated during image build time. This improves the startup when no Interne connection is available.

### Changes in openDKIM

The *opendkim* configuration is now static and embedded in the `app` image. The keys volume now points to `/var/db/dkim` its name remains unchanged (`opendkim`).

* In file `KeyTable` of this volume, you need to change `/etc/opendkim` to `/var/db/dkim` at beginning of each key path.

### Changes in database

This version changes mariadb version:

* launch following commands (`DB_ROOT_PASSWORD` is defined in your `.env` file):
```bash
docker-compose exec db /bin/bash
mariadb-upgrade --password=<DB_ROOT_PASSWORD>
```

### Others changes

The `postfix_relay` volume has been removed. The *postfix* configuration is now static and embedded in their respective images. This will make the future changes and upgrades easier and safer.
The *watchtower* has been removed as it was not useful, upgrades often need manual changes.

### Upgrade process

Change $VERSION variable in your `.env` file.

```
docker-compose down
docker volume remove $COMPOSE_PROJECT_NAME_clamav $COMPOSE_PROJECT_NAME_postfix_relay $COMPOSE_PROJECT_NAME_smtpconfig $COMPOSE_PROJECT_NAME_spamassassin
docker-compose up -d
```

# v1.4.2

This version add *SPF* checks to postfix and DKIM keys generation when adding a domain using web UI.

* modifications on `smtp` container

!!! important
    If you have made custom changes to `smtpconfig` volume, you have to merge default config manually.

Unless you have made custom changes in `main.cf` and `master.cf` files in `smtpconfig` volume, you can safely remove it:

    docker volume rm '<COMPOSE_PROJECT_NAME>_smtpconfig'

* modifications on `app` container

You must launch following commands once `app` container is running:

    docker-compose exec app /bin/sh
    chown -R opendkim: /etc/opendkim/
    echo "RequireSafeKeys         false" >> /etc/opendkim/opendkim.conf


# v1.4.0

* New `.env.example` file, modify your `.env` file:
    * Add `VERSION` variable
    * Check `ADMIN_PASSWORD` variable name (was `ADMIN_password`)
* The *public* volume, shared between former `web` container and `app` container is not needed anymore

```
docker volume rm agentj-docker_public
```

# v1.3.2

!!! important
    If you have made custom changes to `smtpconfig` volume, you have to merge default config manually.

Configuration of `smtp` container has been changed, unless you have made custom changes in `main.cf` and `master.cf` files in `smtpconfig` volume, you can safely remove it:

    docker volume rm '<COMPOSE_PROJECT_NAME>_smtpconfig'

Launch following commande in `smtp` container:

    chown postfix -R /var/spool/postfix/

MariaDB image is now 10.7.3, an upgrade of database is needed. Once your container is running, launch following commands:

```bash
docker-compose exec db /bin/bash
mariadb-upgrade
```

# v1.3.1

Nothing specific.

# v1.3.0

Variable `COMPOSE_PROJECT_NAME=docker` was added in `.env` file.
Its value must correspond to volumes already present for your AgentJ docker stack.

# Upgrade from version 1.0.6 to 1.2+

Newer version of AgentJ (starting from 1.2) introduce breaking changes such as:

- change in the underlying base images (Alpine instead of Debian slim) and some changes in database structure;
- Amavis does not use the same directories structure to store the mails and logs are sort differently;
- new initialisation and migration process for the database;
- new variables are used in the `.env` file.

### Database migration

Stop the entire stack:

    docker-compose down

Start only the database:

    docker-compose up -d db

Connect to the database:

    docker-compose exec db sh
    mysql -uroot -p
    # Type password

Execute the following:

    CREATE TABLE doctrine_migration_versions ( version varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL, executed_at datetime DEFAULT NULL, execution_time int(11) DEFAULT NULL, PRIMARY KEY (version) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
    insert into doctrine_migration_versions (version, executed_at, execution_time) values ('DoctrineMigrations\\Version20211221152434',null, 0);
    exit;

Exit the container (Ctrl + D)

### Moving the volumes content

It is recommended to prefix old volumes name with something like `old` during the migration process to preserve their content. This can also be done by setting the `COMPOSE_PROJECT_NAME` variable (in `env.` file) to something different and by starting the new stack with it.

#### Amavis

Copy content of `tmp` to `tmp` in new volume as well as `virusmails` folder.

#### MariaDB

Copy content of the old `db` volume to the new one.

#### Logs

Move or copy the relevant logs to the new volume.

#### openDKIM

Copy the whole content of the old volume to the new one.

#### Postfix: configuration of relay container

If changes have been made to the default configuration, the relevant `main.cf` and/or `master.cf` files must be copied from one volume to the other.

#### Postfix: configuration of smtp container (smtpconfig volume)

If changes have been made to the default configuration, the relevant `main.cf` and/or `master.cf` files must be copied from one volume to the other.

### Fix ownership and rights

User names and ids are different between Debian and Alpine, so rights and ownership must be fixed:

| Volume                  | Old user id | Old group id | New user id | New group id |
|-------------------------|-------------|--------------|-------------|--------------|
| amavis                  | 102         | 103          | 100         | 101          |
| opendkim (keys folder ) | 102         | 104          | 100         | 101          |

# v1.2.3

* clamav: improve start process, move it to entrypoint
* amavis: update clamav run dir
* syslogng: improve log format, add rotation cron
* app: remove duplicate cron entries

# v1.2.1

* fix csv import emails

# v1.2.0

* upgrade to Symfony 5
* upgrade Alpine base
* database initialisation process improvement
