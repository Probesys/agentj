#!/usr/bin/env bash
set -e

# Set mailname
sed -i "s/\$MAIL_DOMAINNAME/$MAIL_DOMAINNAME/g" /etc/postfix/main.cf
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/postfix/main.cf
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/postfix/master.cf
echo relay.$MAIL_DOMAINNAME > /etc/mailname

exec "$@"