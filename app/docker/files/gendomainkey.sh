#!/bin/sh
# |----------------------------------------------------------------------------
# | Name        : gendomainkey.sh
# | Description : Create DKIM keys and config (called via AgentJ webapp)
# | Dependency  : opendkim opendkim-utils
# | Author      : Probesys
# | Last update : 2022/04/07
# | Version     : 0.1
# | Licence     : GNU GLPv3 or later
# |----------------------------------------------------------------------------

# |----------------------------------------------------------------------------
# | Usage : ./gendomainkey.sh DOMAIN_NAME SMTP_SERVER_NAME
# |----------------------------------------------------------------------------

# |----------------------------------------------------------------------------
# | Variables :
# |----------------------------------------------------------------------------

_DOMAIN="$1"
_MX="$2"

# |----------------------------------------------------------------------------
# | Functions :
# |----------------------------------------------------------------------------

printUsage () {
    echo "Usage: ./gendomainkey.sh DOMAIN_NAME SMTP_SERVER_NAME"
}

# |----------------------------------------------------------------------------
# | Main :
# |----------------------------------------------------------------------------

if [ $# -ne 2 ] ; then
    echo "$(basename) : Missing argument"
    printUsage
    exit 2
fi

echo "$_DOMAIN" >> /var/db/dkim/DomainsList
echo "$_MX" >> /var/db/dkim/TrustedHosts
echo "*@$_DOMAIN agentj._domainkey.$_DOMAIN" >> /var/db/dkim/SigningTable
echo "agentj._domainkey.$_DOMAIN $_DOMAIN:agentj:/var/db/dkim/keys/$_DOMAIN/agentj.private" >> /var/db/dkim/KeyTable

if [ -d /var/db/dkim/keys/"$_DOMAIN" ]; then
    echo "This domain already has DKIM keys. Nothing to do."
else
    echo "No DKIM keys exist for this domain. Generating new ones."
    mkdir -p /var/db/dkim/keys/"$_DOMAIN"
    cd /var/db/dkim/keys/"$_DOMAIN" || exit
    opendkim-genkey -s agentj -d "$_DOMAIN"
    sudo /bin/chown -R opendkim:opendkim /var/db/dkim
fi

exit $?
