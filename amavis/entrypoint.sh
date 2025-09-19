#!/bin/bash
set -e
IPV4_NETWORK=$(ip route | grep  kernel | awk '{ print $1}')
IPV4=$(ip addr | grep 'state UP' -A2 | tail -n1 | awk '{print $2}' | cut -f1 -d\/)

# Initialize Amavis conf with variables
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/$AMAVIS_CONF
sed -i "s/\$DB_USER/$DB_USER/g" /etc/$AMAVIS_CONF
sed -i "s/\$DB_HOST/$DB_HOST/g" /etc/$AMAVIS_CONF
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/$AMAVIS_CONF
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/$AMAVIS_CONF
sed -i "s/\$DOMAIN/$DOMAIN/g" /etc/$AMAVIS_CONF
sed -i "s/\$CLAMAV_TCPADDRESS/$CLAMAV_TCPADDRESS/g" /etc/$AMAVIS_CONF
sed -i "s/\$CLAMAV_TCPPORT/$CLAMAV_TCPPORT/g" /etc/$AMAVIS_CONF
sed -i "s~\$inet_socket_bind = \[.*\]~\$inet_socket_bind = ['127.0.0.1', '$IPV4']~g" /etc/$AMAVIS_CONF
sed -i "s/\$AMAVIS_PROCESSES/${AMAVIS_PROCESSES:-3}/g" /etc/$AMAVIS_CONF

echo "$DOMAIN" > /etc/mailname
chmod 644 /etc/$AMAVIS_CONF
if [ ! -d /var/run/amavis ]
then
    mkdir /var/run/amavis
fi
chown -R amavis:amavis /var/run/amavis

# amavis TEMPBASE & db_home folders
mkdir -p /var/amavis/tmp /var/amavis/db
chown -R amavis:amavis /var/amavis/

cron

exec "$@"
