#!/bin/sh
set -e

sed -i "s/\$DB_HOST/$DB_HOST/g" /etc/opendkim.conf
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/opendkim.conf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/opendkim.conf
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/opendkim.conf

exec "$@"
