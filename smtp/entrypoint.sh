#!/bin/sh
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

# Fix file permissions
find /etc/postfix/ -type f -exec chmod 644 {} \;
for dir in active bounce corrupt defer deferred flush hold incoming \
    private saved trace
do
    chown -R 100:0 /var/spool/postfix/"$dir"
done
for dir in maildrop public
do
    chown -R 100:103 /var/spool/postfix/"$dir"
done


exec "$@"
