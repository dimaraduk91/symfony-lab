up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose up -d --build

bash:
	docker compose exec php bash

console:
	docker compose exec php php bin/console

test:
	docker compose exec php php bin/phpunit

composer:
	docker compose exec php composer $(filter-out $@,$(MAKECMDGOALS))

logs:
	docker compose logs -f

ps:
	docker compose ps

%:
	@:
