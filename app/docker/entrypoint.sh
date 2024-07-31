#!/bin/sh
set -e

# Generate APP_SECRET (required for CSRF token)
MYAPPSECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
# Generate token for encryption
MY_TOKEN_ENC_IV=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 16 | head -n 1)
MY_TOKEN_ENC_SALT=$(openssl rand -base64 32)

env_file=/var/www/agentj/.env
if [ ! -f $env_file ]; then
    cp /var/www/agentj/.env.example $env_file
    sed -i "s|\$MYAPPSECRET|$MYAPPSECRET|g" /var/www/agentj/.env
    sed -i "s|\$MY_TOKEN_ENC_IV|$MY_TOKEN_ENC_IV|g" /var/www/agentj/.env
    sed -i "s|\$MY_TOKEN_ENC_SALT|$MY_TOKEN_ENC_SALT|g" /var/www/agentj/.env
    sed -i "s|\$DB_NAME|$DB_NAME|g" /var/www/agentj/.env
    sed -i "s|\$DB_USER|$DB_USER|g" /var/www/agentj/.env
    sed -i "s|\$DB_PASSWORD|$DB_PASSWORD|g" /var/www/agentj/.env
    sed -i "s|\$DB_HOST|$DB_HOST|g" /var/www/agentj/.env
    sed -i "s|\$MAIL_HOSTNAME|$MAIL_HOSTNAME|g" /var/www/agentj/.env
    sed -i "s|\$MAIL_DOMAINNAME|$MAIL_DOMAINNAME|g" /var/www/agentj/.env
    sed -i "s|\$ENABLE_AZURE_OAUTH|$ENABLE_AZURE_OAUTH|g" /var/www/agentj/.env
    sed -i "s|\$OAUTH_AZURE_CLIENT_ID|$OAUTH_AZURE_CLIENT_ID|g" /var/www/agentj/.env
    sed -i "s|\$OAUTH_AZURE_CLIENT_SECRET|$OAUTH_AZURE_CLIENT_SECRET|g" /var/www/agentj/.env
    sed -i "s|\$TRUSTED_PROXIES|$TRUSTED_PROXIES|g" /var/www/agentj/.env
    sed -i "s|\$TZ|$TZ|g" /var/www/agentj/.env
    sed -i 's|memory_limit = 128M|memory_limit = 512M|g' /etc/php/8.2/cli/php.ini
fi

echo "Installing libraries"
cd /var/www/agentj && sudo -u www-data composer install --ignore-platform-reqs --no-scripts
cd /var/www/agentj && sudo -u www-data yarnpkg install
# cd /var/www/agentj && sudo -u www-data yarnpkg encore production

echo "Installing assets"
cd /var/www/agentj && sudo -u www-data php bin/console assets:install

echo "Create database if not exists and update schemas"
cd /var/www/agentj && sudo -u www-data php bin/console doctrine:database:create --if-not-exists
cd /var/www/agentj && sudo -u www-data php bin/console doctrine:migration:migrate

echo "Create or update super admin user"
cd /var/www/agentj && sudo -u www-data php bin/console agentj:create-super-admin $SUPER_ADMIN_USERNAME $SUPER_ADMIN_PASSWORD

echo "update groups wblist"
cd /var/www/agentj && sudo -u www-data php bin/console agentj:update-groups-wblist

# Allow web server user to write Symphony logs
rm -rf /var/www/agentj/var/cache
chown -R www-data:www-data /var/www/agentj/var
find /var/www/agentj/public -type d -exec chmod go+rwx {} \;
# Allow web server user to purge old virus and spam mails
usermod -aG www-data amavis && chmod -R g+w /tmp/amavis/quarantine

echo "Installing crontabs"
if [ ! -d /var/log/agentj ]; then
    mkdir /var/log/agentj && chown -R www-data /var/log/agentj
fi

cron

exec "$@"
