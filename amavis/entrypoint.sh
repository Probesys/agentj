#!/bin/sh
set -e

# Initialize Amavis conf with variables
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/amavisd.conf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/amavisd.conf
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/amavisd.conf
sed -i "s/\$IPV4_NETWORK/$IPV4_NETWORK/g" /etc/amavisd.conf
echo "$MAIL_HOSTNAME" > /etc/mailname
sed -i "12i\$myhostname = \"$MAIL_HOSTNAME\";" /etc/amavisd.conf
chmod 644 /etc/amavisd.conf
if [ ! -d /var/run/amavis ]
then
    mkdir /var/run/amavis
fi
chown -R amavis:amavis /var/run/amavis

# Make sure Clamd environment exists
if [ ! -d /run/clamav ]
then
    mkdir /run/clamav
    chmod 755 /run/clamav
    touch /run/clamav/clamd.ctl
    chmod 644 /etc/amavisd.conf
    adduser clamav amavis && adduser amavis clamav
    chown -R clamav:clamav /run/clamav
fi

echo "0 14 * * * /usr/bin/freshclam --log=/var/log/clamav/freshclam.log --daemon-notify=/etc/clamav/clamd.conf --config-file=/etc/clamav/freshclam.conf" >> /etc/cron.d/agentj-amavis
echo "10 14 * * * /usr/bin/sa-update" >> /etc/cron.d/agentj-amavis
crond

if [ ! -f /var/amavis/.initdone ]
then
    ./init.sh && rm ./init.sh
fi

exec "$@"
