SHELL := /bin/bash

ENV_FILE ?= .env
FILE_DOCKER_PROJECT_NAME := $(strip $(shell sed -n 's/^DOCKER_PROJECT_NAME=//p' $(ENV_FILE) 2>/dev/null | head -n 1))
FILE_APP_ENV := $(strip $(shell sed -n 's/^APP_ENV=//p' $(ENV_FILE) 2>/dev/null | head -n 1))
ifneq ($(origin DOCKER_PROJECT_NAME),command line)
DOCKER_PROJECT_NAME := $(or $(FILE_DOCKER_PROJECT_NAME),one-market)
endif
ifneq ($(origin APP_ENV),command line)
APP_ENV := $(FILE_APP_ENV)
endif
LOAD_ENV := set -a && source $(ENV_FILE) && set +a &&
COMPOSE_BASE := docker compose -p $(DOCKER_PROJECT_NAME) --env-file $(ENV_FILE)
LOCAL_COMPOSE := $(COMPOSE_BASE) -f docker-compose.yml -f docker-compose.override.yml
PROD_COMPOSE := $(COMPOSE_BASE) -f docker-compose.yml
IS_PRODUCTION := $(filter production,$(APP_ENV))
ENV_COMPOSE := $(if $(IS_PRODUCTION),$(PROD_COMPOSE),$(LOCAL_COMPOSE))
ENV_LOG_SERVICES := $(if $(IS_PRODUCTION),app queue scheduler db,app vite queue scheduler db)
ENV_ENSURE_VENDOR := $(if $(IS_PRODUCTION),,ensure-vendor)
ENV_ENSURE_NODE_MODULES := $(if $(IS_PRODUCTION),,ensure-node-modules)
test_args ?= --compact
dump_file ?= docker/db/dump.sql.gz

.PHONY: help key-show ensure-vendor ensure-node-modules build up down down-volumes logs ps dump import test deploy

help:
	@printf '%s\n' \
		'make build               # build and start using APP_ENV from .env' \
		'make key-show            # print a generated APP_KEY without starting the stack' \
		'make ensure-vendor       # local: install composer deps if vendor is missing' \
		'make ensure-node-modules # local: install node deps in the vite volume if missing' \
		'make up                  # start without build using APP_ENV from .env' \
		'make down                # stop the stack using APP_ENV from .env' \
		'make down-volumes        # stop the stack and delete named volumes, including the database' \
		'make logs                # follow logs using APP_ENV from .env' \
		'make ps                  # show container status using APP_ENV from .env' \
		'make dump                # export database to docker/db/dump.sql.gz' \
		'make import              # import database from docker/db/dump.sql.gz' \
		'make test                # local: run tests in app container' \
		'make deploy              # git pull, build, migrate, and restart queue workers'

key-show:
	$(LOAD_ENV) $(LOCAL_COMPOSE) run --rm --no-deps --entrypoint php app artisan key:generate --show

ensure-vendor:
	@if [ ! -f vendor/autoload.php ]; then \
		echo 'vendor/autoload.php is missing. Installing Composer dependencies...'; \
		$(LOAD_ENV) $(LOCAL_COMPOSE) run --rm --entrypoint sh app -lc 'composer install --no-interaction --prefer-dist --no-progress'; \
	fi

ensure-node-modules:
	@$(LOAD_ENV) $(LOCAL_COMPOSE) run --rm --no-deps --entrypoint sh vite -lc '\
		if [ ! -x node_modules/.bin/vite ]; then \
			echo "node_modules is missing. Installing Node dependencies..."; \
			npm ci --no-fund --no-audit; \
		fi'

build: $(ENV_ENSURE_VENDOR) $(ENV_ENSURE_NODE_MODULES)
	$(LOAD_ENV) $(ENV_COMPOSE) up -d --build --remove-orphans

up: $(ENV_ENSURE_VENDOR) $(ENV_ENSURE_NODE_MODULES)
	$(LOAD_ENV) $(ENV_COMPOSE) up -d --remove-orphans

down:
	$(LOAD_ENV) $(ENV_COMPOSE) down --remove-orphans

down-volumes:
	$(LOAD_ENV) $(ENV_COMPOSE) down -v --remove-orphans

logs:
	$(LOAD_ENV) $(ENV_COMPOSE) logs -f $(ENV_LOG_SERVICES)

ps:
	$(LOAD_ENV) $(ENV_COMPOSE) ps

dump:
	@mkdir -p "$(dir $(dump_file))"
	$(LOAD_ENV) $(ENV_COMPOSE) exec -T db env MYSQL_PWD="$$DB_PASSWORD" mariadb-dump --single-transaction --routines --triggers -u"$$DB_USERNAME" "$$DB_DATABASE" | gzip > "$(dump_file)"

import:
	@test -f "$(dump_file)" || (echo 'Dump file not found: $(dump_file)' >&2; exit 1)
	$(LOAD_ENV) gunzip -c "$(dump_file)" | $(ENV_COMPOSE) exec -T db env MYSQL_PWD="$$DB_PASSWORD" mariadb -u"$$DB_USERNAME" "$$DB_DATABASE"

test:
	$(LOAD_ENV) $(LOCAL_COMPOSE) exec app php artisan test $(test_args)

deploy:
	git pull
	$(LOAD_ENV) $(ENV_COMPOSE) up -d --build --remove-orphans
	$(LOAD_ENV) $(ENV_COMPOSE) exec app php artisan migrate --force
	$(LOAD_ENV) $(ENV_COMPOSE) exec app php artisan queue:restart
