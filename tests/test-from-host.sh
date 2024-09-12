#!/bin/sh

docker compose exec -u www-data app ./docker/tests/testmail.sh
