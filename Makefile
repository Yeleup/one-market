SHELL := /bin/bash

ENV_FILE ?= .env
DOCKER_PROJECT_NAME ?= $(strip $(shell sed -n 's/^DOCKER_PROJECT_NAME=//p' $(ENV_FILE) 2>/dev/null | head -n 1))
DOCKER_PROJECT_NAME := $(or $(DOCKER_PROJECT_NAME),one-market)
LOAD_ENV := set -a && source $(ENV_FILE) && set +a &&
COMPOSE_BASE := docker compose -p $(DOCKER_PROJECT_NAME) --env-file $(ENV_FILE)
LOCAL_COMPOSE := $(COMPOSE_BASE) -f docker-compose.yml -f docker-compose.override.yml
PROD_COMPOSE := $(COMPOSE_BASE) -f docker-compose.yml

cmd ?= about
service ?= app

.PHONY: help key-show up start rebuild down logs ps artisan shell migrate queue-restart npm-install \
	prod-up prod-start prod-down prod-logs prod-ps prod-artisan prod-shell \
	prod-migrate prod-queue-restart prod-deploy

help:
	@printf '%s\n' \
		'make up                  # local: build and start the full stack' \
		'make key-show            # print a generated APP_KEY without starting the stack' \
		'make start               # local: start without rebuild' \
		'make rebuild             # local: rebuild and restart' \
		'make down                # local: stop the stack' \
		'make logs                # local: follow logs' \
		'make ps                  # local: show container status' \
		'make artisan cmd="..."   # local: run artisan command in app' \
		'make shell               # local: open shell in app container' \
		'make migrate             # local: run migrations' \
		'make npm-install         # local: install npm deps in vite container' \
		'make prod-up             # production: build and start' \
		'make prod-start          # production: start without rebuild' \
		'make prod-down           # production: stop the stack' \
		'make prod-logs           # production: follow logs' \
		'make prod-ps             # production: show container status' \
		'make prod-artisan cmd="..." # production: run artisan command in app' \
		'make prod-migrate        # production: run migrations' \
		'make prod-queue-restart  # production: restart queue workers' \
		'make prod-deploy         # production: git pull + build + migrate + queue restart'

key-show:
	$(LOAD_ENV) $(LOCAL_COMPOSE) run --rm --no-deps --entrypoint php app artisan key:generate --show

up:
	$(LOAD_ENV) $(LOCAL_COMPOSE) up -d --build --remove-orphans

start:
	$(LOAD_ENV) $(LOCAL_COMPOSE) up -d --remove-orphans

rebuild:
	$(LOAD_ENV) $(LOCAL_COMPOSE) up -d --build --remove-orphans

down:
	$(LOAD_ENV) $(LOCAL_COMPOSE) down --remove-orphans

logs:
	$(LOAD_ENV) $(LOCAL_COMPOSE) logs -f web app vite queue scheduler db

ps:
	$(LOAD_ENV) $(LOCAL_COMPOSE) ps

artisan:
	$(LOAD_ENV) $(LOCAL_COMPOSE) exec app php artisan $(cmd)

shell:
	$(LOAD_ENV) $(LOCAL_COMPOSE) exec $(service) sh

migrate:
	$(LOAD_ENV) $(LOCAL_COMPOSE) exec app php artisan migrate --force

queue-restart:
	$(LOAD_ENV) $(LOCAL_COMPOSE) exec app php artisan queue:restart

npm-install:
	$(LOAD_ENV) $(LOCAL_COMPOSE) exec vite npm install --no-package-lock

prod-up:
	$(LOAD_ENV) $(PROD_COMPOSE) up -d --build --remove-orphans

prod-start:
	$(LOAD_ENV) $(PROD_COMPOSE) up -d --remove-orphans

prod-down:
	$(LOAD_ENV) $(PROD_COMPOSE) down --remove-orphans

prod-logs:
	$(LOAD_ENV) $(PROD_COMPOSE) logs -f web app queue scheduler db

prod-ps:
	$(LOAD_ENV) $(PROD_COMPOSE) ps

prod-artisan:
	$(LOAD_ENV) $(PROD_COMPOSE) exec app php artisan $(cmd)

prod-shell:
	$(LOAD_ENV) $(PROD_COMPOSE) exec $(service) sh

prod-migrate:
	$(LOAD_ENV) $(PROD_COMPOSE) exec app php artisan migrate --force

prod-queue-restart:
	$(LOAD_ENV) $(PROD_COMPOSE) exec app php artisan queue:restart

prod-deploy:
	git pull
	$(LOAD_ENV) $(PROD_COMPOSE) up -d --build --remove-orphans
	$(LOAD_ENV) $(PROD_COMPOSE) exec app php artisan migrate --force
	$(LOAD_ENV) $(PROD_COMPOSE) exec app php artisan queue:restart
