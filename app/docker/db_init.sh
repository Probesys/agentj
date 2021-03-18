#!/usr/bin/env bash

SQL_SCRIPT="/var/www/html/agentj/setup/agentj.sql"

# Wait until db server has initialized itself
while ! /usr/bin/mysql -h db -u root -p$DB_ROOT_PASSWORD 
do
    sleep 10
    echo "AGENTJ: Waiting for database to become available"
done
if /usr/bin/mysql -h db -u root -p$DB_ROOT_PASSWORD -e "USE agentj; SELECT * FROM domain"
then
    echo "AGENTJ: Database already initialized"
    exit
elif [ -f "$SQL_SCRIPT" ]
then
    /usr/bin/mysql -h db -u root -p$DB_ROOT_PASSWORD $DB_NAME < "$SQL_SCRIPT" \
    && echo -e "AGENTJ: Database successfully initialized"
    rm "$SQL_SCRIPT"
fi