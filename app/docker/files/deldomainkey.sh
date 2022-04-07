#!/bin/sh
# |----------------------------------------------------------------------------
# | Name        : deldomainkey.sh
# | Description : Remove DKIM keys and config (called via AgentJ webapp)
# | Dependency  : none
# | Author      : Probesys
# | Last update : 2022/04/07
# | Version     : 0.1
# | Licence     : GNU GLPv3 or later
# |----------------------------------------------------------------------------

# |----------------------------------------------------------------------------
# | Usage : ./deldomainkey.sh DOMAIN_NAME SMTP_SERVER_NAME
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
    echo "Usage: ./deldomainkey.sh DOMAIN_NAME SMTP_SERVER_NAME"
}

# |----------------------------------------------------------------------------
# | Main :
# |----------------------------------------------------------------------------

if [ $# -ne 2 ] ; then
    echo "$(basename) : Missing argument"
    printUsage
    exit 2
fi

if [ ! -d /etc/opendkim/keys/"$_DOMAIN" ]; then
    echo "This domain does not have DKIM keys. Nothing to do."
else                                                                     
    echo "Removing DKIM keys and config for this domain..."                                                               
    sed -i "/$_DOMAIN/d" /etc/opendkim/DomainsList                              
    sed -i "/$_MX/d" /etc/opendkim/TrustedHosts                                                                                  
    sed -i "/*@$_DOMAIN agentj._domainkey.$_DOMAIN/d" /etc/opendkim/SigningTable                                                 
    sed -i "/agentj._domainkey.$_DOMAIN $_DOMAIN:agentj:\/etc\/opendkim\/keys\/$_DOMAIN\/agentj.private/d" /etc/opendkim/KeyTable
    rm -rf /etc/opendkim/keys/"$_DOMAIN"                   
fi                                                     
                                                       
exit $?