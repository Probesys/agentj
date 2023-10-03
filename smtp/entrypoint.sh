#!/bin/sh
set -e
IPV4_NETWORK=$(ip route | grep  kernel | awk '{ print $1}')
# Set mailname
sed -i "s/\$MAIL_HOSTNAME/$MAIL_HOSTNAME/g" /etc/conf/$SMTP_TYPE/postfix/main.cf
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/conf/$SMTP_TYPE/postfix/main.cf
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/conf/$SMTP_TYPE/postfix/master.cf
echo $MAIL_HOSTNAME > /etc/conf/$SMTP_TYPE/mailname

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
fi

# Fix file permissions
find /etc/conf/$SMTP_TYPE/postfix/ -type f -exec chmod 644 {} \;

exec "$@"
