include .env

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

CURRENT_UID := $(shell id -u)
CURRENT_GID := $(shell id -g)

export CURRENT_UID
export CURRENT_GID

init: deps assets migration ##[host] all the tasks for a first install

up:
	docker compose  up -d

up-build:
	docker compose  up -d --build

down:
	docker compose  down

down-v:
	docker compose  down -v

swaks: ## [host] Send swaks command
	docker compose exec app swaks --server smtp --from   --to   --header-X-Test "Test AgentJ dev" --body "Test $(date)" --helo smtp26.services.sfr.fr
	# swaks --server 172.24.42.5 --from   --to   --header-X-Test "Test AgentJ dev" --body "Test $(date)" --helo smtp26.services.sfr.fr

install: ## [host] Install php, js and css vendors
	docker compose  exec app composer install --no-interaction #--no-progress
	docker compose  exec app yarnpkg install
	docker compose  exec app yarnpkg encore prod


migration:
	docker compose  exec app php bin/console doctrine:migrations:migrate --no-interaction


bash: ## [host] opens a bash on web
	docker compose  exec app bash

restore-db:
	docker compose exec -T db mariadb -u ${DB_USER} -p  ${DB_PASSWORD}  ${DB_NAME} < docker-probesys-db-1-agentj.sql.10.08.2023.sql

