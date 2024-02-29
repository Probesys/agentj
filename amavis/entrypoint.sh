#!/bin/bash
set -e
IPV4_NETWORK=$(ip route | grep  kernel | awk '{ print $1}')
IPV4=$(ip addr | grep 'state UP' -A2 | tail -n1 | awk '{print $2}' | cut -f1 -d\/)
# Initialize Amavis conf with variables
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/$AMAVIS_CONF
sed -i "s/\$DB_USER/$DB_USER/g" /etc/$AMAVIS_CONF
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/$AMAVIS_CONF
sed -i "s~\$IPV4_NETWORK~$IPV4_NETWORK~g" /etc/$AMAVIS_CONF
sed -i "s/\$MAIL_HOSTNAME/$MAIL_HOSTNAME/g" /etc/$AMAVIS_CONF
sed -i "s~\$IPV4~$IPV4~g" /etc/$AMAVIS_CONF
sed -i "s/\$CLAMAV_AUTOSTART/$CLAMAV_AUTOSTART/g" /etc/supervisord.conf
echo "$MAIL_HOSTNAME" > /etc/mailname
chmod 644 /etc/$AMAVIS_CONF
if [ ! -d /var/run/amavis ]
then
    mkdir /var/run/amavis
fi
chown -R amavis:amavis /var/run/amavis

# amavis TEMPBASE & db_home folders
mkdir -p /var/amavis/tmp /var/amavis/db
chown -R amavis:amavis /var/amavis/

# Make sure Clamd environment exists
if [ ! -d /run/clamav ]
then
    mkdir /run/clamav
    chmod 755 /run/clamav
    touch /run/clamav/clamd.ctl
    chmod 644 /etc/$AMAVIS_CONF
    adduser clamav amavis && adduser amavis clamav
    chown -R clamav:clamav /run/clamav
fi
if [ $CLAMAV_AUTOSTART == "true" ]
then
    echo "Configuring local ClamAV server"
    CLAMAV_CONFIG="\/run\/clamav\/clamd.ctl"
    sed -i 's|\$AGENTJ_AV_SCANNER|\ ["ClamAV-clamd", \n\ \ \\\&ask_daemon, ["CONTSCAN {}\\n", "$CLAMAV_CONFIG"],\n\ \ qr/\\bOK$/m, qr/\\bFOUND$/m, \n\ \ qr/^.*?: (?!Infected Archive)(.*) FOUND$/m ],\n|g' /etc/$AMAVIS_CONF
else
    echo "Configuring remote ClamAV server"
    CLAMAV_CONFIG="$CLAMAV_TCPADDRESS:$CLAMAV_TCPPORT"
    sed -i 's|\$AGENTJ_AV_SCANNER|\ ["ClamAV-remote-stream", \n\ \ \\\&ask_daemon, [ \n\ \ \ "{}/*", \n\ \ \ [ \n\ \ \ \ "clamd:$CLAMAV_CONFIG", \n\ \ \ ], \n\ \ ], \n\ \ qr/\\bOK$/m, qr/\\bFOUND$/m, \n\ \ qr/^.*?: (?!Infected Archive)(.*) FOUND$/m \n\ ],\n|g' /etc/$AMAVIS_CONF
fi
sed -i "s/\$CLAMAV_CONFIG/$CLAMAV_CONFIG/g" /etc/$AMAVIS_CONF

cron

exec "$@"
