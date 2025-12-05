#!/bin/sh
set -e

sh /setup-env.sh

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

echo "Installing crontabs"
if [ ! -d /var/log/agentj ]; then
	mkdir /var/log/agentj && chown -R www-data /var/log/agentj
fi

cd /

exec "$@"
