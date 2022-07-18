#!/bin/sh
set -e

echo "Installing crontabs"
crond

exec "$@"
