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

echo "$_DOMAIN" >> /etc/opendkim/DomainsList
echo "$_MX" >> /etc/opendkim/TrustedHosts
echo "*@$_DOMAIN agentj._domainkey.$_DOMAIN" >> /etc/opendkim/SigningTable
echo "agentj._domainkey.$_DOMAIN $_DOMAIN:agentj:/etc/opendkim/keys/$_DOMAIN/agentj.private" >> /etc/opendkim/KeyTable

if [ -d /etc/opendkim/keys/"$_DOMAIN" ]; then
    echo "This domain already has DKIM keys. Nothing to do."
else
    echo "No DKIM keys exist for this domain. Generating new ones."
    mkdir -p /etc/opendkim/keys/"$_DOMAIN"
    cd /etc/opendkim/keys/"$_DOMAIN" || exit
    opendkim-genkey -s agentj -d "$_DOMAIN"
    sudo /bin/chown -R opendkim:opendkim /etc/opendkim
fi

exit $?