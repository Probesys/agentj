#!/bin/bash

source .env
dx='docker compose exec -u www-data app'
$dx php bin/console d:d:d --force
$dx php bin/console d:d:c
$dx php bin/console d:m:m --no-interaction
$dx php bin/console ag:create "$SUPER_ADMIN_USERNAME" "$SUPER_ADMIN_PASSWORD"
