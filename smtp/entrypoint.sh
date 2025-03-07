#!/bin/sh
# SMTP_TYPE is relay|in|out
# shellcheck disable=SC2086
set -e

IPV4_NETWORK=$(ip route | grep  kernel | awk '{ print $1}')
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" "/etc/conf/$SMTP_TYPE/postfix/main.cf"
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" "/etc/conf/$SMTP_TYPE/postfix/master.cf"

# Set mailname
sed -i "s/\$DOMAIN/${EHLO_DOMAIN:-$DOMAIN}/g" "/etc/conf/$SMTP_TYPE/postfix/main.cf"
echo "${EHLO_DOMAIN:-$DOMAIN}" > /etc/mailname

if [ "$SMTP_TYPE" != "relay" ]
then
	# Configure transport map
	sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
	sed -i "s/\$DB_HOST/$DB_HOST/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
	sed -i "s/\$DB_USER/$DB_USER/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf
	sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/conf/$SMTP_TYPE/postfix/mysql-*.cf

else
	postmap "lmdb:/etc/conf/$SMTP_TYPE/postfix/slow_dest_domains_transport"
fi

# For existing installs: fix file permissions
# For new installs: create dir
find "/etc/conf/$SMTP_TYPE/postfix/" -type f -exec chmod 644 {} \;

for dir in active bounce corrupt defer deferred flush hold incoming \
	private saved trace
do
	mkdir -p "/var/spool/postfix/$dir"
	chown -R postfix:root "/var/spool/postfix/$dir"
done

for dir in maildrop public
do
	mkdir -p "/var/spool/postfix/$dir"
	chown -R postfix:postdrop "/var/spool/postfix/$dir"
done

exec "$@"
