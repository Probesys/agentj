#!/bin/sh
set -e

sed -i "s/\$DB_HOST/$DB_HOST/g" /etc/opendkim.conf
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/opendkim.conf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/opendkim.conf
sed -i "s/\$DB_OPENDKIM_PASSWORD/$DB_OPENDKIM_PASSWORD/g" /etc/opendkim.conf

IPV4_NETWORK=$(ip route | grep  kernel | awk '{ print $1}')
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/trusted.opendkim.conf
sed -i "s/\$DOMAIN/$DOMAIN/g" /etc/trusted.opendkim.conf

exec "$@"
