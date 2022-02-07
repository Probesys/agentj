#!/bin/sh
set -e

# Initialize Amavis conf with variables
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/amavisd.conf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/amavisd.conf
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/amavisd.conf
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

# Initialize AV database
if [ ! -f /var/lib/clamav/main.cvd ]
then
    /usr/bin/freshclam
fi

# Start the AV database daemon
/usr/bin/freshclam -d -c 3

# Refresh SA rules
/usr/bin/sa-update

exec "$@"
