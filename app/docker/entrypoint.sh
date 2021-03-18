#!/usr/bin/env bash
set -e

# Generate APP_SECRET (required for CSRF token)
MYAPPSECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
# Generate token for encryption
MY_TOKEN_ENC_IV=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 16 | head -n 1)
MY_TOKEN_ENC_SALT=$(openssl rand -base64 32)

sed -i "s|\$MYAPPSECRET|$MYAPPSECRET|g" /var/www/html/agentj/.env
sed -i "s|\$MY_TOKEN_ENC_IV|$MY_TOKEN_ENC_IV|g" /var/www/html/agentj/.env
sed -i "s|\$MY_TOKEN_ENC_SALT|$MY_TOKEN_ENC_SALT|g" /var/www/html/agentj/.env
sed -i "s|\$DB_NAME|$DB_NAME|g" /var/www/html/agentj/.env
sed -i "s|\$DB_USER|$DB_USER|g" /var/www/html/agentj/.env
sed -i "s|\$DB_PASSWORD|$DB_PASSWORD|g" /var/www/html/agentj/.env
sed -i "s|\$MAIL_HOSTNAME|$MAIL_HOSTNAME|g" /var/www/html/agentj/.env
sed -i "s|\$MAIL_DOMAINNAME|$MAIL_DOMAINNAME|g" /var/www/html/agentj/.env
sed -i "s|\$DB_NAME|$DB_NAME|g" /db_init.sh
sed -i "s|\$DB_ROOT_PASSWORD|$DB_ROOT_PASSWORD|g" /db_init.sh
/bin/bash /db_init.sh

echo "Installing assets"
cd /var/www/html/agentj && sudo -u www-data php bin/console assets:install
echo "Updating SQL schemas"
cd /var/www/html/agentj && sudo -u www-data php bin/console doctrine:schema:update --force

# Allow web server user to write Symphony logs
rm -rf /var/www/html/agentj/var/cache
chown -R www-data:www-data /var/www/html/agentj/var
# Allow web server user to purge old virus and spam mails
groupadd -g 103 amavis && usermod -aG 103 www-data && chmod -R g+w /tmp/amavis/virusmails

echo "Installing crontabs"
mkdir /var/log/agentj && chown -R www-data /var/log/agentj
echo "* * * * * cd /var/www/html/agentj && sudo -u www-data php bin/console agentj:msgs-send-mail-token >> /var/log/agentj/cron.log 2>&1" > /etc/cron.d/agentj
echo "0 3 * * * cd /var/www/html/agentj && sudo -u www-data php bin/console agentj:truncate-message-since-day 30 >> /var/log/agentj/truncate.log 2>&1" >> /etc/cron.d/agentj
echo "5 3 * * * cd /var/www/html/agentj && sudo -u www-data php bin/console agentj:truncate-virus-queue >> /var/log/agentj/truncate.log 2>&1" >> /etc/cron.d/agentj
echo "0 7 * * * cd /var/www/html/agentj && sudo -u www-data php bin/console agentj:report-send-mail >> /var/log/agentj/send.log 2>&1" >> /etc/cron.d/agentj
cron && crontab /etc/cron.d/agentj

exec "$@"
