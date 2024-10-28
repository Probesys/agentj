#!/bin/sh
set -e

sed -i "s/\$DB_HOST/$DB_HOST/g" ~/.config/policyd-rate-limit.yaml
sed -i "s/\$DB_NAME/$DB_NAME/g" ~/.config/policyd-rate-limit.yaml
sed -i "s/\$DB_USER/$DB_USER/g" ~/.config/policyd-rate-limit.yaml
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" ~/.config/policyd-rate-limit.yaml

exec "$@"
