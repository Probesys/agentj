#!/bin/sh
set -e

echo "Installing crontabs"
cron

exec "$@"
