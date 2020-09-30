#!/usr/bin/env bash
set -e

# Set mailname
sed -i "s/\$MAIL_DOMAINNAME/$MAIL_DOMAINNAME/g" /etc/postfix/main.cf
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/postfix/main.cf
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/postfix/master.cf
echo relay.$MAIL_DOMAINNAME > /etc/mailname

sed -i "s/\$MAIL_HOSTNAME/$MAIL_HOSTNAME/g" /etc/opendkim/TrustedHosts
sed -i "s/\$MAIL_DOMAINNAME/$MAIL_DOMAINNAME/g" /etc/opendkim/SigningTable
sed -i "s/\$MAIL_DOMAINNAME/$MAIL_DOMAINNAME/g" /etc/opendkim/KeyTable

for domain in $(cat /etc/opendkim/TrustedHosts)
do
    if [ "$domain" == "localhost" ] || [ "$domain" == "127.0.0.1" ]; then
        continue
    else
        if [ ! -d "/tmp/keys/${domain#*.}" ]; then
            mkdir -p /tmp/keys/"${domain#*.}"
            cd /tmp/keys/"${domain#*.}"
            opendkim-genkey -s mail -d "${domain#*.}"
            chown opendkim:opendkim mail.private
        fi
    fi
done

cp -R /tmp/keys /etc/opendkim/keys
chown -R  opendkim:opendkim /etc/opendkim/keys

exec "$@"