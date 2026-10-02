.PHONY: up down restart build bash console test composer install rr-install rr-direct-up rr-benchmark logs rr-logs ps rr-version

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose up -d --build

restart: down up

bash:
	docker compose exec php bash

console:
	docker compose exec php php bin/console

test:
	docker compose exec php php bin/phpunit

composer:
	docker compose exec php composer $(filter-out $@,$(MAKECMDGOALS))

install:
	docker compose run --rm --no-deps php composer install

rr-install:
	docker compose run --rm --no-deps php vendor/bin/rr get --location bin/ --no-config --no-interaction

rr-direct-up:
	docker compose stop nginx php
	docker compose up -d --build roadrunner

rr-benchmark:
	k6 run -e BASE_URL=http://localhost:8081 benchmark.js

logs:
	docker compose logs -f

rr-logs:
	docker compose logs -f roadrunner nginx

ps:
	docker compose ps

rr-version:
	docker compose exec roadrunner bin/rr --version

%:
	@:
