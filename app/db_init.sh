#!/usr/bin/env bash
set -e

SQL_SCRIPT="/var/www/html/agentj/setup/agentj.sql"

# Wait until db server has initialized itself
sleep 30
if /usr/bin/mysql -h db -u root -p$DB_ROOT_PASSWORD -e "USE $DB_NAME"
then
    if [ -f "$SQL_SCRIPT" ]
    then
        /usr/bin/mysql -h db -u root -p$DB_ROOT_PASSWORD $DB_NAME < "$SQL_SCRIPT" \
        && echo -e "AGENTJ: Database successfully initialized"
        rm "$SQL_SCRIPT"
    else
        echo "Database already initialized"
    fi
fi