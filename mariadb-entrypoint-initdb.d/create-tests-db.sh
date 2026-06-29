#!/bin/sh
set -e

if [ "$SF_APP_ENV" != 'prod' ]; then
    TEST_DB="${MYSQL_DATABASE}_tests"

    mariadb -u root -p"$MYSQL_ROOT_PASSWORD" -e "
    CREATE DATABASE IF NOT EXISTS \`$TEST_DB\`;
    GRANT ALL PRIVILEGES ON \`$TEST_DB\`.* TO '$MYSQL_USER'@'%';
    FLUSH PRIVILEGES;
    "
fi
