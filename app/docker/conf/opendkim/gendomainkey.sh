#!/bin/sh

_DOMAIN="$1"
_MX="$2"

echo "$_DOMAIN" >> /etc/opendkim/DomainsList
echo "$_MX" >> /etc/opendkim/TrustedHosts
echo "*@$_DOMAIN mail._domainkey.$_DOMAIN" >> /etc/opendkim/SigningTable
echo "mail._domainkey.$_DOMAIN $_DOMAIN:mail:/etc/opendkim/keys/$_DOMAIN/mail.private" >> /etc/opendkim/KeyTable

if [ -d /etc/opendkim/keys/"$_DOMAIN" ]; then
    echo "This domain already has DKIM keys. Nothing to do."
else
    echo "No DKIM keys exist for this domain. Generating new ones."
    mkdir -p /etc/opendkim/keys/"$_DOMAIN"
    cd /etc/opendkim/keys/"$_DOMAIN" || exit
    opendkim-genkey -s mail -d "$_DOMAIN"
    sudo chown -R opendkim:opendkim /etc/opendkim
fi

exit $?