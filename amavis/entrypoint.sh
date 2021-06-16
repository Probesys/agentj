#!/bin/sh
set -e

# Initialize Amavis conf with variables
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/amavisd.conf
sed -i "s/\$DB_USER/$DB_USER/g" /etc/amavisd.conf
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/amavisd.conf
echo "$MAIL_HOSTNAME" > /etc/mailname
sed -i "12i\$myhostname = \"$MAIL_HOSTNAME\";" /etc/amavisd.conf
chmod 644 /etc/amavisd.conf
mkdir /var/run/amavis
chown -R amavis:amavis /var/run/amavis

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
