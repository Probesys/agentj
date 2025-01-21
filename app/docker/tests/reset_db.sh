#!/bin/bash

php bin/console d:d:d --force
php bin/console d:d:c
php bin/console d:m:m --no-interaction
php bin/console ag:create "$SUPER_ADMIN_USERNAME" "$SUPER_ADMIN_PASSWORD"
