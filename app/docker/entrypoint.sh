#!/bin/sh
set -e


sed -i 's|memory_limit = 128M|memory_limit = 512M|g' /etc/php/8.2/cli/php.ini


echo "Update schemas"
cd /var/www/agentj &&  php bin/console doctrine:migration:migrate

echo "Create or update super admin user"
cd /var/www/agentj && php bin/console agentj:create-super-admin $SUPER_ADMIN_USERNAME $SUPER_ADMIN_PASSWORD

echo "update groups wblist"
cd /var/www/agentj && php bin/console agentj:update-groups-wblist

# Allow web server user to write Symphony logs
rm -rf /var/www/agentj/var/cache
chown -R www-data:www-data /var/www/agentj/var
find /var/www/agentj/public -type d -exec chmod go+rwx {} \;
# Allow web server user to purge old virus and spam mails
usermod -aG www-data amavis && chmod -R g+w /tmp/amavis/quarantine

echo "Installing crontabs"
if [ ! -d /var/log/agentj ]
then
    mkdir /var/log/agentj && chown -R www-data /var/log/agentj
fi

cron

exec "$@"
