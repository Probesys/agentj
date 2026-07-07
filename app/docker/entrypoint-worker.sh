#!/bin/sh
set -e

sh /setup-env.sh

sed -i 's|memory_limit = 128M|memory_limit = 512M|g' /etc/php/8.4/cli/php.ini

cd /var/www/agentj || exit 4

# Allow web server user to write Symphony logs
chown -R www-data:www-data /var/www/agentj/var

if [ ! -d /var/log/agentj ]; then
	mkdir /var/log/agentj && chown -R www-data /var/log/agentj
fi

cd /

touch /tmp/healthy.txt

exec "$@"
