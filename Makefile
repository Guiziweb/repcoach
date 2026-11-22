.PHONY: install browsers migrate import fixtures serve cs cs-fix lint stan test ci

install:
	composer install

browsers:
	vendor/bin/playwright-install chromium

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

import:
	php bin/console app:import-exercises

fixtures:
	php bin/console doctrine:fixtures:load --no-interaction --purge-exclusions=exercise

serve:
	php -S 127.0.0.1:8010 -t public

cs:
	vendor/bin/php-cs-fixer check --diff

cs-fix:
	vendor/bin/php-cs-fixer fix

lint:
	php bin/console lint:yaml config fixtures --no-interaction
	php bin/console lint:container --no-interaction
	php bin/console doctrine:schema:validate --skip-sync --no-interaction

stan:
	php bin/console cache:warmup --env=test --quiet
	vendor/bin/phpstan analyse --no-progress

test:
	vendor/bin/phpunit

ci: cs lint stan test
