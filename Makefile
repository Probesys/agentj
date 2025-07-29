.DEFAULT_GOAL := help

ifdef NO_DOCKER
	PHP = cd app && php
	CONSOLE = cd app && php bin/console
else
	PHP = ./scripts/php
	CONSOLE = ./scripts/console
endif

.PHONY: docker-start
docker-start: .env ## Start the Docker containers (development mode)
	docker compose up --build

.PHONY: docker-images
docker-images: ## Rebuild Docker images and pull them from the Docker Hub
	docker compose pull --ignore-buildable
	docker compose build --pull

.PHONY: docker-clean
docker-clean: ## Delete the Docker containers, volumes and networks (take a FORCE argument)
ifndef FORCE
	$(error Please run the operation with FORCE=true)
endif
	docker compose down -v

.PHONY: lint
lint: LINTER ?= all
lint: ## Execute the linters (can take a LINTER argument)
ifeq ($(LINTER), $(filter $(LINTER), all phpstan))
	$(PHP) vendor/bin/phpstan analyse -v
endif
ifeq ($(LINTER), $(filter $(LINTER), all phpcs))
	$(PHP) vendor/bin/phpcs
endif
ifeq ($(LINTER), $(filter $(LINTER), all symfony))
	$(CONSOLE) lint:container
endif

.PHONY: lint-fix
lint-fix: LINTER ?= all
lint-fix: ## Fix the errors detected by the linters (can take a LINTER argument)
ifeq ($(LINTER), $(filter $(LINTER), all phpcs))
	$(PHP) vendor/bin/phpcbf
endif


.PHONY: release
release: ## Release a new version (take a VERSION argument)
ifndef VERSION
	$(error You need to provide a "VERSION" argument)
endif
	sed -i "s/^VERSION=.*/VERSION=$(VERSION)/" .env.example
	$(EDITOR) CHANGELOG.md
	git add .
	git commit -m "release: Publish version $(VERSION)"
	git tag -a $(VERSION) -m "Release version $(VERSION)"

.env:
	cp .env.example .env
	sed -i "s/^VERSION=.*/VERSION=dev/" .env
	sed -i "s/^DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=secret/" .env
	sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=secret/" .env
	sed -i "s/^DB_OPENDKIM_PASSWORD=.*/DB_OPENDKIM_PASSWORD=secret/" .env
	sed -i "s/^SUPER_ADMIN_PASSWORD=.*/SUPER_ADMIN_PASSWORD=secret/" .env
	sed -i "s/^DOMAIN=.*/DOMAIN=localhost/" .env
	sed -i "s/^APP_URL=.*/APP_URL=http:\/\/localhost:8090/" .env
	sed -i "/SMTP_PORT/s/^#//" .env
	sed -i "/SMTP_OUT_PORT/s/^#//" .env
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
