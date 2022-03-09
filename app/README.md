
# AgentJ -Antispam with human authentification

AgentJ is an open source web application,written inPHP and based on the symfony framework. This web application is designed to configure and manage Amavisd-new.

Amavisd-new is an interface between an MTA (Mail Trasnport Agent such as PostFix) and various content filter (antivirus, antispam,..) such as SpamAssassin an ClamAV

---

### Features
- Filters spam and virus mails
- Human authentification of senders
- Add sender to whitelist or blacklist
- Daily reports

---

### Prerequisites

It is necessary to have a Postfix server.
It is also necessary to have installed Amavis, SpamAssassin and Clamav as well as a MySQL type database.
Finally, you must have set up Amavis to work with a MySQL database.
To do this, insert the following line in the amavisd /etc/amavis/conf.d/50-user configuration file:

```
@lookup_sql_dsn = ( ['DBI:mysql:database=postfix;host=10.0.0.47;port=3306', 'postfix', 'motdepassebasemysqlpourpostfix']);
```




### Installation
```shellscript
$ git clone git@gitlab.probesys.com:christian.tresvaux/agentj.git
$ composer install
$ yarn install
$ yarn encore production
$ php bin/console assets:install
$ php bin/console doctrine:schema:update --force
```
### Configuration
You need to edit the .env file at the root of the project and change these keys corresponding to your environnement.

```
### Environnement ###
APP_ENV=prod

### Database configuration ###
DATABASE_URL=mysql://db_user:db_password@db_host/db_name

### SMTP Configuration ###
SMTP_TRANSPORT=probinfra1.probesys.net

### Path to the Amavis executable ###
AMAVIS_RELEASE__PATH=/usr/local/bin/amavisd-release

### Domain name to access the application ###
DOMAIN="agentj.yourdomain.com"


```

You also need to add cront tack 
```
* * * * * cd /var/www/agentj && php bin/console agentj:msgs-send-mail-token >>/tmp/cron.log 2>&1
 00 3 * * * cd /var/www/agentj && php bin/console agentj:truncate-message-since-day 30 >/tmp/truncate.log 2>&1
 00 7 * * * cd /var/www/agentj && php bin/console agentj:report-send-mail > /tmp/send.log 2>&

```

### Licence  

# AgentJ -Antispam with human authentification

AgentJ is an open source web application,written inPHP and based on the symfony framework. This web application is designed to configure and manage Amavisd-new.

Amavisd-new is an interface between an MTA (Mail Trasnport Agent such as PostFix) and various content filter (antivirus, antispam,..) such as SpamAssassin an ClamAV

---

### Features
- Filters spam and virus mails
- Human authentification of senders
- Add sender to whitelist or blacklist
- Daily reports

---

### Prerequisites

It is necessary to have a Postfix server.
It is also necessary to have installed Amavis, SpamAssassin and Clamav as well as a MySQL type database.
Finally, you must have set up Amavis to work with a MySQL database.
To do this, insert the following line in the amavisd /etc/amavis/conf.d/50-user configuration file:

```
@lookup_sql_dsn = ( ['DBI:mysql:database=postfix;host=10.0.0.47;port=3306', 'postfix', 'motdepassebasemysqlpourpostfix']);
```




### Installation
```shellscript
$ git clone git@gitlab.probesys.com:christian.tresvaux/agentj.git
$ composer install
$ yarn install
$ yarn encore production
$ php bin/console assets:install
$ php bin/console doctrine:schema:update --force
```
### Configuration
You need to edit the .env file at the root of the project and change these keys corresponding to your environnement.

```
### Environnement ###
APP_ENV=prod

### Database configuration ###
DATABASE_URL=mysql://db_user:db_password@db_host/db_name

### SMTP Configuration ###
SMTP_TRANSPORT=probinfra1.probesys.net

### Path to the Amavis executable ###
AMAVIS_RELEASE__PATH=/usr/local/bin/amavisd-release

### Domain name to access the application ###
DOMAIN="agentj.yourdomain.com"


```

You also need to add cront tack 
```
* * * * * cd /var/www/agentj && php bin/console agentj:msgs-send-mail-token >>/tmp/cron.log 2>&1
 00 3 * * * cd /var/www/agentj && php bin/console agentj:truncate-message-since-day 30 >/tmp/truncate.log 2>&1
 00 7 * * * cd /var/www/agentj && php bin/console agentj:report-send-mail > /tmp/send.log 2>&

```

### Licence  

This application is under the GNU AFFERO GENERAL PUBLIC LICENSE v3.0
=======

This application is under the GNU AFFERO GENERAL PUBLIC LICENSE v3.0
### About  
AgentJ is a [Probesys](https://www.probesys.com) project.
