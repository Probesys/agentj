#!/bin/sh
set -e

echo "Installing crontabs"
echo "* * * * * find /var/log/syslogng/ -daystart -mtime +31 -type f -exec rm {} \;" >> /etc/cron.d/syslogng-rotate
crond

exec "$@"
