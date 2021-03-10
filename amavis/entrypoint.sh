#!/usr/bin/env bash
set -e

# Initialize Amavis conf with variables
sed -i "s/\$DB_NAME/$DB_NAME/g" /etc/amavis/conf.d/50-user
sed -i "s/\$DB_USER/$DB_USER/g" /etc/amavis/conf.d/50-user
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /etc/amavis/conf.d/50-user
echo "$MAIL_HOSTNAME" > /etc/mailname
sed -i "12i\$myhostname = \"$MAIL_HOSTNAME\";" /etc/amavis/conf.d/05-node_id
chmod 644 /etc/amavis/conf.d/*

# Patch Amavis
patch -p1 /usr/sbin/amavisd-new < /root/amavisd-new.patch

# Initialize AV database
if [ ! -f /var/lib/clamav/main.cvd ]
then
    /usr/bin/freshclam
fi

# Start the AV database daemon
/usr/bin/freshclam -d -c 3

exec "$@"
