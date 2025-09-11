# Infrastructure overview

AgentJ is intended to be set as your email domain MX, and as relay for your SMTP server.
It sends emails from the web domain and, depending on the configuration, from your email domain.
Users authentication can be made via IMAP, LDAP or Microsoft Azure.

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

- **ldap**: runs an OpenLDAP server
- **mailpit**: catches all emails, from or to AgentJ
- **smtptest**: runs opensmtpd and dnsmasq. It can send email to AgentJ on the behalf of configured domains; relay email from AgentJ to mailpit, provide DNS for correct DKIM verification of tests email

Volumes:

- **db** : MariaDB database files
- **amavis_in** and **amavis_out** : Amavis databases
- **postqueue** : the incoming email queue (for **smtp**)
- **outpostqueue** : the outgoing email queue (for **outsmtp**)
