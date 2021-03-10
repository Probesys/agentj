#!/bin/bash

export DOCKER_HOST="ssh://${DEPLOY_USER:-$USER}@${DEPLOY_HOST:-$1}"

CURRENT_PATH="$(dirname $0)"

docker stack deploy --with-registry-auth --prune -c $CURRENT_PATH/docker-compose.yml ${DEPLOY_STACK:-$2}