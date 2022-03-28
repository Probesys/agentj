#!/bin/sh
set -e

# Configure opendkim
sed -i "s/\$MAIL_HOSTNAME/$MAIL_HOSTNAME/g" /etc/opendkim/TrustedHosts
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/opendkim/TrustedHosts
sed -i "s/\$MAIL_DOMAINNAME/$MAIL_DOMAINNAME/g" /etc/opendkim/SigningTable
sed -i "s/\$MAIL_DOMAINNAME/$MAIL_DOMAINNAME/g" /etc/opendkim/KeyTable
touch /etc/opendkim/DomainsList
addgroup www-data opendkim
if [ ! -d /etc/opendkim/keys ]
then
    mkdir /etc/opendkim/keys
fi
chgrp -R opendkim /etc/opendkim
chmod -R g+w /etc/opendkim
if [ ! -f /etc/sudoers.d/opendkim ]
then
    echo "Cmnd_Alias      CHMODDKIM = /bin/chown -R opendkim\:opendkim /etc/opendkim" >> /etc/sudoers.d/opendkim
    echo "www-data ALL=(ALL) NOPASSWD: CHMODDKIM" >> /etc/sudoers.d/opendkim
fi

# Generate APP_SECRET (required for CSRF token)
MYAPPSECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
# Generate token for encryption
MY_TOKEN_ENC_IV=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 16 | head -n 1)
MY_TOKEN_ENC_SALT=$(openssl rand -base64 32)

sed -i "s|\$MYAPPSECRET|$MYAPPSECRET|g" /var/www/agentj/.env
sed -i "s|\$MY_TOKEN_ENC_IV|$MY_TOKEN_ENC_IV|g" /var/www/agentj/.env
sed -i "s|\$MY_TOKEN_ENC_SALT|$MY_TOKEN_ENC_SALT|g" /var/www/agentj/.env
sed -i "s|\$DB_NAME|$DB_NAME|g" /var/www/agentj/.env
sed -i "s|\$DB_USER|$DB_USER|g" /var/www/agentj/.env
sed -i "s|\$DB_PASSWORD|$DB_PASSWORD|g" /var/www/agentj/.env
sed -i "s|\$MAIL_HOSTNAME|$MAIL_HOSTNAME|g" /var/www/agentj/.env
sed -i "s|\$MAIL_DOMAINNAME|$MAIL_DOMAINNAME|g" /var/www/agentj/.env

echo "Installing assets"
cd /var/www/agentj && sudo -u www-data php bin/console assets:install

echo "Create database if not exists and update schemas"
cd /var/www/agentj && sudo -u www-data php bin/console doctrine:database:create --if-not-exists
cd /var/www/agentj && sudo -u www-data php bin/console doctrine:migration:migrate

echo "Create or update super admin user"
cd /var/www/agentj && php bin/console agentj:create-super-admin $SUPER_ADMIN_USERNAME $SUPER_ADMIN_PASSWORD

# Allow web server user to write Symphony logs
rm -rf /var/www/agentj/var/cache
chown -R www-data:www-data /var/www/agentj/var
find /var/www/agentj/public -type d -exec chmod go+rwx {} \;
# Allow web server user to purge old virus and spam mails
addgroup -g 101 amavis && adduser www-data amavis && chmod -R g+w /tmp/amavis/quarantine

echo "Installing crontabs"
if [ ! -d /var/log/agentj ]
then
    mkdir /var/log/agentj && chown -R www-data /var/log/agentj
fi
echo "* * * * * cd /var/www/agentj && sudo -u www-data php bin/console agentj:msgs-send-mail-token >> /var/log/agentj/cron.log 2>&1" > /etc/cron.d/agentj
echo "0 3 * * * cd /var/www/agentj && sudo -u www-data php bin/console agentj:truncate-message-since-day 30 >> /var/log/agentj/truncate.log 2>&1" >> /etc/cron.d/agentj
echo "5 3 * * * cd /var/www/agentj && sudo -u www-data php bin/console agentj:truncate-virus-queue >> /var/log/agentj/truncate.log 2>&1" >> /etc/cron.d/agentj
echo "0 7 * * * cd /var/www/agentj && sudo -u www-data php bin/console agentj:report-send-mail >> /var/log/agentj/send.log 2>&1" >> /etc/cron.d/agentj
crond

exec "$@"