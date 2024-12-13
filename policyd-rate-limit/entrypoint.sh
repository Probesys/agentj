#!/bin/sh
set -e

test -f /var/run/policyd-rate-limit/policyd-rate-limit.pid && rm /var/run/policyd-rate-limit/policyd-rate-limit.pid
sed -i "s/\$DB_HOST/$DB_HOST/g" ~/.config/policyd-rate-limit.yaml
sed -i "s/\$DB_NAME/$DB_NAME/g" ~/.config/policyd-rate-limit.yaml
sed -i "s/\$DB_USER/$DB_USER/g" ~/.config/policyd-rate-limit.yaml
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" ~/.config/policyd-rate-limit.yaml

exec "$@"
