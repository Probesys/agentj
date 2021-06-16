#!/bin/sh
set -e

# Set mailname
sed -i "s/\$MAIL_DOMAINNAME/$MAIL_DOMAINNAME/g" /etc/postfix/main.cf
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/postfix/main.cf
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/postfix/master.cf
echo relay.$MAIL_DOMAINNAME > /etc/mailname

sed -i "s/\$MAIL_HOSTNAME/$MAIL_HOSTNAME/g" /etc/opendkim/TrustedHosts
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/opendkim/TrustedHosts
sed -i "s/\$MAIL_DOMAINNAME/$MAIL_DOMAINNAME/g" /etc/opendkim/SigningTable
sed -i "s/\$MAIL_DOMAINNAME/$MAIL_DOMAINNAME/g" /etc/opendkim/KeyTable

for domain in $(cat /etc/opendkim/TrustedHosts)
do
    if [ "$domain" == "localhost" ] || [ "$domain" == "127.0.0.1" ] || [ "$domain" == "$IPV4_NETWORK.0/24" ]; then
        continue
    elif [ -d /etc/opendkim/keys/"${domain#*.}" ]; then
        echo "This domain already has DKIM keys. Nothing to do."
        continue
    else
        echo "No DKIM keys exist for this domain. Generating new ones."
        mkdir -p /etc/opendkim/keys/"${domain#*.}"
        cd /etc/opendkim/keys/"${domain#*.}"
        opendkim-genkey -s mail -d "${domain#*.}"
        chown -R  opendkim:opendkim /etc/opendkim/keys
    fi
done

# Fix file permissions
find /etc/postfix/ -type f -exec chmod 644 {} \;

exec "$@"