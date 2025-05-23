#!/bin/sh
set -e

IPV4_NETWORK="$(ip route | grep  kernel | awk '{ print $1}') $SMTP_TRUSTED_PROXIES"
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/postfix-*/main.cf
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/postfix-*/master.cf

# Set mailname
sed -i "s/\$DOMAIN/$DOMAIN/g" /etc/postfix-*/main.cf
sed -i "s/\$EHLO_DOMAIN/${EHLO_DOMAIN:-$DOMAIN}/g" /etc/postfix-*/main.cf
echo "$DOMAIN" > /etc/mailname

# update relayhost
sed -i "s~\$SMTP_OUT_RELAY~$SMTP_OUT_RELAY~g" /etc/postfix-*/master.cf

postmap "lmdb:/etc/postfix-common/slow_dest_domains_transport"
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/postfix-*/mysql-*.cf
sed -i "s/\$DB_HOST/$DB_HOST/g" /etc/postfix-*/mysql-*.cf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/postfix-*/mysql-*.cf
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/postfix-*/mysql-*.cf

/usr/sbin/postfix start-fg
