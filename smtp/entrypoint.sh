#!/usr/bin/env bash
set -e

# Set mailname
sed -i "s/\$MAIL_HOSTNAME/$MAIL_HOSTNAME/g" /etc/postfix/main.cf
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/postfix/main.cf
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/postfix/master.cf
echo $MAIL_HOSTNAME > /etc/mailname

# Configure transport map
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/postfix/mysql-transport_map.cf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/postfix/mysql-transport_map.cf
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/postfix/mysql-transport_map.cf

# Configure recipients map
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/postfix/mysql-virtual_recipient_maps.cf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/postfix/mysql-virtual_recipient_maps.cf
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/postfix/mysql-virtual_recipient_maps.cf

# Configure domaines map
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/postfix/mysql-virtual_domains.cf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/postfix/mysql-virtual_domains.cf
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/postfix/mysql-virtual_domains.cf

exec "$@"