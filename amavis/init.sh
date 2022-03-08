#!/bin/sh

# Initialize AV database
if [ ! -f /var/lib/clamav/main.cvd ]
then
    /usr/bin/freshclam --log=/var/log/clamav/freshclam.log \
        --daemon-notify=/etc/clamav/clamd.conf \
        --config-file=/etc/clamav/freshclam.conf
fi

# Refresh SA rules
/usr/bin/sa-update

echo "true" > /var/amavis/.initdone
