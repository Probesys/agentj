#!/bin/bash

mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" \
	-e "create user 'opendkim' identified by '$DB_OPENDKIM_PASSWORD'; grant select on $MYSQL_DATABASE.* to 'opendkim';"
