DOCKER_COMPOSE = docker compose
RUN = $(DOCKER_COMPOSE) run --rm php

.PHONY: build shell composer-install composer-update test

build:
	$(DOCKER_COMPOSE) build --build-arg UID=$$(id -u) --build-arg GID=$$(id -g)

shell:
	$(RUN) bash

composer-install:
	$(RUN) composer install

composer-update:
	$(RUN) composer update

test:
	$(RUN) vendor/bin/phpunit
