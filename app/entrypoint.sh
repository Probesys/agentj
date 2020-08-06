#!/usr/bin/env bash
set -e

sed -i "s/\$DB_NAME/$DB_NAME/g" /var/www/html/agentj/.env
sed -i "s/\$DB_USER/$DB_USER/g" /var/www/html/agentj/.env
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /var/www/html/agentj/.env
sed -i "s/\$MAIL_HOSTNAME/$MAIL_HOSTNAME/g" /var/www/html/agentj/.env
sed -i "s/\$DB_NAME/$DB_NAME/g" /db_init.sh
sed -i "s/\$DB_ROOT_PASSWORD/$DB_ROOT_PASSWORD/g" /db_init.sh
/bin/bash /db_init.sh

echo "Installing assets"
cd /var/www/html/agentj && sudo -u www-data php bin/console assets:install
echo "Updating SQL schemas"
cd /var/www/html/agentj && sudo -u www-data php bin/console doctrine:schema:update --force
chown -R www-data:www-data /var/www/html/agentj/var

echo "Installing crontabs"
echo "* * * * * cd /var/www/html/agentj && sudo -u www-data php bin/console agentj:msgs-send-mail-token >>/tmp/cron.log 2>&1" >> /etc/cron.d/agentj
echo "00 3 * * * cd /var/www/html/agentj && sudo -u www-data php bin/console agentj:truncate-message-since-day 30 >/tmp/truncate.log 2>&1" >> /etc/cron.d/agentj
echo "00 7 * * * cd /var/www/html/agentj && sudo -u www-data php bin/console agentj:report-send-mail > /tmp/send.log 2>&" >> /etc/cron.d/agentj
cron && crontab /etc/cron.d/agentj

exec "$@"