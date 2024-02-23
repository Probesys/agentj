#!/bin/sh
set -e
IPV4_NETWORK=$(ip route | grep  kernel | awk '{ print $1}')
# Set mailname
sed -i "s/\$MAIL_HOSTNAME/$MAIL_HOSTNAME/g" /etc/conf/$SMTP_TYPE/postfix/main.cf
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/conf/$SMTP_TYPE/postfix/main.cf
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/conf/$SMTP_TYPE/postfix/master.cf
echo $MAIL_HOSTNAME > /etc/mailname

if [ $SMTP_TYPE != "relay" ] 
then
	# Configure transport map
	sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
	sed -i "s/\$DB_USER/$DB_USER/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
	sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf

	# Configure recipients map
	sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
	sed -i "s/\$DB_USER/$DB_USER/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
	sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf

	# Configure domaines map
	sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
	sed -i "s/\$DB_USER/$DB_USER/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
	sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
else
	if [ -n "$RELAYHOST" ]
	then
	    echo "relayhost = $RELAYHOST" >> /etc/postfix/main.cf
	else
	    echo "relayhost="  >> /etc/postfix/main.cf
	fi

	postmap /etc/conf/$SMTP_TYPE/postfix/slow_dest_domains_transport
fi

# Fix file permissions
find /etc/conf/$SMTP_TYPE/postfix/ -type f -exec chmod 644 {} \;

for dir in active bounce corrupt defer deferred flush hold incoming \
    private saved trace
do
    mkdir -p /var/spool/postfix/"$dir"
    chown -R 100:0 /var/spool/postfix/"$dir"
done
for dir in maildrop public
do
    mkdir -p /var/spool/postfix/"$dir"
    chown -R 100:103 /var/spool/postfix/"$dir"
done

exec "$@"
