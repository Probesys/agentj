#!/bin/sh
# SMTP_TYPE is relay|in|out
set -e

ln -f -s "/etc/postfix-$SMTP_TYPE"/* /etc/postfix/ 

IPV4_NETWORK=$(ip route | grep  kernel | awk '{ print $1}')
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" "/etc/postfix/main.cf"
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" "/etc/postfix/master.cf"

# Set mailname
sed -i "s/\$DOMAIN/${EHLO_DOMAIN:-$DOMAIN}/g" "/etc/postfix/main.cf"
echo "${EHLO_DOMAIN:-$DOMAIN}" > /etc/mailname

if [ "$SMTP_TYPE" != "relay" ]
then
	# Configure transport map
	sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/postfix/mysql-*.cf
	sed -i "s/\$DB_HOST/$DB_HOST/g" /etc/postfix/mysql-*.cf
	sed -i "s/\$DB_USER/$DB_USER/g" /etc/postfix/mysql-*.cf
	sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/postfix/mysql-*.cf

else
	postmap "lmdb:/etc/postfix/slow_dest_domains_transport"
fi

# # For existing installs: fix file permissions
# # For new installs: create dir
# find "/etc/postfix/" -type f -exec chmod 644 {} \;
# 
# for dir in active bounce corrupt defer deferred flush hold incoming \
# 	private saved trace
# do
# 	mkdir -p "/var/spool/postfix/$dir"
# 	chown -R postfix:root "/var/spool/postfix/$dir"
# done
# 
# for dir in maildrop public
# do
# 	mkdir -p "/var/spool/postfix/$dir"
# 	chown -R postfix:postdrop "/var/spool/postfix/$dir"
# done

/usr/sbin/postfix -c "/etc/postfix" start-fg
