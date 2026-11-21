.PHONY: install migrate import fixtures cs cs-fix lint stan test ci

install:
	composer install

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

import:
	php bin/console app:import-exercises

fixtures:
	php bin/console doctrine:fixtures:load --no-interaction --purge-exclusions=exercise

cs:
	vendor/bin/php-cs-fixer check --diff

cs-fix:
	vendor/bin/php-cs-fixer fix

lint:
	php bin/console lint:yaml config fixtures --no-interaction
	php bin/console lint:container --no-interaction
	php bin/console doctrine:schema:validate --skip-sync --no-interaction

stan:
	php bin/console cache:warmup --env=dev --quiet
	vendor/bin/phpstan analyse --no-progress

test:
	vendor/bin/phpunit

ci: cs lint stan test
