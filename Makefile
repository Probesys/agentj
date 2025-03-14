.DEFAULT_GOAL := help

.PHONY: up
up: .env ## Start the Docker containers
	docker compose up --build

.PHONY: clean
clean: ## Clean the Docker stuff (take a FORCE argument)
ifndef FORCE
	$(error Please run the operation with FORCE=true)
endif
	docker compose down -v

.env:
	cp .env.example .env
	sed -i "s/^VERSION=.*/VERSION=dev/" .env
	sed -i "s/^DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=secret/" .env
	sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=secret/" .env
	sed -i "s/^DB_OPENDKIM_PASSWORD=.*/DB_OPENDKIM_PASSWORD=secret/" .env
	sed -i "s/^SUPER_ADMIN_PASSWORD=.*/SUPER_ADMIN_PASSWORD=secret/" .env
	sed -i "s/^DOMAIN=.*/DOMAIN=localhost/" .env
	sed -i "s/^SF_APP_ENV=.*/SF_APP_ENV=dev/" .env
	sed -i "/^SF_APP_SECRET/s/$$/$(shell openssl rand -hex 16)/" .env
	sed -i "/^SF_TOKEN_ENCRYPTION_IV/s/$$/$(shell openssl rand -hex 8)/" .env
	sed -i "/^SF_TOKEN_ENCRYPTION_SALT/s/$$/$(shell openssl rand -hex 32)/" .env
	sed -i "/COMPOSE_FILE/s/^#//" .env
	sed -i "s/.*DB_EXPOSED_PORT.*/DB_EXPOSED_PORT=3307/" .env
	sed -i "s/.*UID.*/UID=$(shell id -u)/" .env
	sed -i "s/.*GID.*/GID=$(shell id -g)/" .env
	sed -i "s/.*MAILPIT_WEB_PORT.*/MAILPIT_WEB_PORT=8025/" .env

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
