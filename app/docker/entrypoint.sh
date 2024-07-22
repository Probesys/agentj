#!/bin/sh
set -e


# sed -i 's|memory_limit = 128M|memory_limit = 512M|g' /etc/php/8.2/cli/php.ini
if [ "$APP_ENV" != "dev" ]; then
DIR=/var/www/agentj/vendor

if [ -d "$DIR" ] ; then
cd /var/www/agentj &&  php bin/console doctrine:migration:migrate
cd /var/www/agentj && php bin/console agentj:create-super-admin $SUPER_ADMIN_USERNAME $SUPER_ADMIN_PASSWORD
cd /var/www/agentj && php bin/console agentj:update-groups-wblist
fi



# Allow web server user to write Symphony logs
# rm -rf /var/www/agentj/var/cache
# chown -R www-data:www-data /var/www/agentj/var
# find /var/www/agentj/public -type d -exec chmod go+rwx {} \;
# Allow web server user to purge old virus and spam mails
usermod -aG www-data amavis && chmod -R g+w /tmp/amavis/quarantine

echo "Installing crontabs"
if [ ! -d /var/log/agentj ]
then
    mkdir /var/log/agentj && chown -R www-data /var/log/agentj
fi

cron
if


exec "$@"
