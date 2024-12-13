#!/bin/sh
set -e

env_file=/var/www/agentj/.env
cp /var/www/agentj/.env.example $env_file
sed -i "s|\$AGENTJ_VERSION|$VERSION|g" $env_file
sed -i "s|\$SF_APP_ENV|$SF_APP_ENV|g" $env_file
sed -i "s|\$SF_APP_SECRET|$SF_APP_SECRET|g" $env_file
sed -i "s|\$SF_TOKEN_ENCRYPTION_IV|$SF_TOKEN_ENCRYPTION_IV|g" $env_file
sed -i "s|\$SF_TOKEN_ENCRYPTION_SALT|$SF_TOKEN_ENCRYPTION_SALT|g" $env_file
sed -i "s|\$SF_SENTRY_DSN|$SF_SENTRY_DSN|g" $env_file
sed -i "s|\$DB_NAME|$DB_NAME|g" $env_file
sed -i "s|\$DB_USER|$DB_USER|g" $env_file
sed -i "s|\$DB_PASSWORD|$DB_PASSWORD|g" $env_file
sed -i "s|\$DB_HOST|$DB_HOST|g" $env_file
sed -i "s|\$MAIL_HOSTNAME|$MAIL_HOSTNAME|g" $env_file
sed -i "s|\$MAIL_DOMAINNAME|$MAIL_DOMAINNAME|g" $env_file
sed -i "s|\$ENABLE_AZURE_OAUTH|$ENABLE_AZURE_OAUTH|g" $env_file
sed -i "s|\$OAUTH_AZURE_CLIENT_ID|$OAUTH_AZURE_CLIENT_ID|g" $env_file
sed -i "s|\$OAUTH_AZURE_CLIENT_SECRET|$OAUTH_AZURE_CLIENT_SECRET|g" $env_file
sed -i "s|\$TRUSTED_PROXIES|$TRUSTED_PROXIES|g" $env_file
sed -i "s|\$TZ|$TZ|g" $env_file
sed -i "s|\$DEFAULT_LOCALE|$DEFAULT_LOCALE|g" $env_file
sed -i 's|memory_limit = 128M|memory_limit = 512M|g' /etc/php/8.2/cli/php.ini

cd /var/www/agentj || exit 4

if [ -x "$(which composer)" ] && [ -x "$(which yarnpkg)" ] ; then
	echo "Installing libraries"
	sudo -u www-data composer install --ignore-platform-reqs --no-scripts
	sudo -u www-data yarnpkg install
fi

echo "Installing assets"
sudo -u www-data php bin/console assets:install

echo "Create database if not exists and update schemas"
sudo -u www-data php bin/console doctrine:database:create --if-not-exists
sudo -u www-data php bin/console doctrine:migration:migrate

echo "Create or update super admin user"
sudo -u www-data php bin/console agentj:create-super-admin "$SUPER_ADMIN_USERNAME" "$SUPER_ADMIN_PASSWORD"

echo "update groups wblist"
sudo -u www-data php bin/console agentj:update-groups-wblist

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

cd /

exec "$@"
