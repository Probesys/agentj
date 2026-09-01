#!/bin/sh
set -e

IPV4_NETWORK=$(ip route | grep  kernel | awk '{ print $1}')
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/postfix-*/main.cf
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/postfix-*/master.cf

# Set mailname
sed -i "s/\$DOMAIN/$DOMAIN/g" /etc/postfix-*/main.cf
sed -i "s/\$EHLO_DOMAIN/${EHLO_DOMAIN:-$DOMAIN}/g" /etc/postfix-*/main.cf
echo "$DOMAIN" > /etc/mailname

postmap "lmdb:/etc/postfix-common/slow_dest_domains_transport"
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/postfix-*/mysql-*.cf
sed -i "s/\$DB_HOST/$DB_HOST/g" /etc/postfix-*/mysql-*.cf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/postfix-*/mysql-*.cf
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/postfix-*/mysql-*.cf

sed -i "s~\$POST_AMAVIS_CONTENT_FILTER~${POST_AMAVIS_CONTENT_FILTER:-}~g" /etc/postfix-in/master.cf
sed -i "s~\$INBOUND_MILTERS~${INBOUND_MILTERS:-inet:opendkim:8891}~g" /etc/postfix-in/main.cf
sed -i "s~\$MILTER_AUTH_SERV_ID~${MILTER_AUTH_SERV_ID:-$DOMAIN}~g" /etc/postfix-in/main.cf
if [ -n "${POST_AMAVIS_MILTERS:-}" ]; then
    sed -i 's/\$POST_AMAVIS_NO_MILTERS//g' /etc/postfix-in/master.cf
    sed -i "s~\$POST_AMAVIS_MILTERS~$POST_AMAVIS_MILTERS~g" /etc/postfix-in/master.cf
else
    sed -i 's/\$POST_AMAVIS_NO_MILTERS/,no_milters/g' /etc/postfix-in/master.cf
    sed -i '/\$POST_AMAVIS_MILTERS/d' /etc/postfix-in/master.cf
fi
if [ -n "${POST_REWRITE_MILTERS:-}" ]; then
    sed -i 's/\$POST_REWRITE_NO_MILTERS//g' /etc/postfix-in/master.cf
    sed -i "s~\$POST_REWRITE_MILTERS~$POST_REWRITE_MILTERS~g" /etc/postfix-in/master.cf
else
    sed -i 's/\$POST_REWRITE_NO_MILTERS/,no_milters/g' /etc/postfix-in/master.cf
    sed -i '/\$POST_REWRITE_MILTERS/d' /etc/postfix-in/master.cf
fi

if [ "$SMTPD_ENABLE_TLS" = "true" ] && [ -e "/etc/postfix-in/ssl/fullchain.pem" ]; then
    chown root: "/etc/postfix-in/ssl/fullchain.pem"
    chmod 640 "/etc/postfix-in/ssl/fullchain.pem"
    sed -i "s/#smtpd_tls_security_level =/smtpd_tls_security_level = /g" /etc/postfix-in/main.cf
    sed -i "s/#smtpd_tls_chain_files =/smtpd_tls_chain_files = /g" /etc/postfix-in/main.cf
fi

# We configure one less Amavis process than the total to reserve a process for the "app" release system.
AMAVIS_PROCESSES=${AMAVIS_PROCESSES:-3}
AMAVIS_PROCESSES=$((AMAVIS_PROCESSES - 1))
sed -i "s/\$AMAVIS_PROCESSES/${AMAVIS_PROCESSES}/g" /etc/postfix-*/master.cf

/usr/sbin/postfix start-fg
