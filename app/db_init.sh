#!/usr/bin/env bash
set -e

if ! /usr/bin/mysql -h db -u root -p$DB_ROOT_PASSWORD -e "USE $DB_NAME"
then
    # Wait until db server has initialized itself
    sleep 30
    /usr/bin/mysql -h db -u root -p$DB_ROOT_PASSWORD $DB_NAME < /var/www/html/agentj/setup/agentj.sql \
    && echo -e "AGENTJ: Database successfully initialized"
else
    echo "Database already initialized"
fi