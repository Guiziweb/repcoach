.PHONY: install migrate cs cs-fix stan

install:
	composer install

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

cs:
	vendor/bin/php-cs-fixer check --diff

cs-fix:
	vendor/bin/php-cs-fixer fix

stan:
	php bin/console cache:warmup --env=dev --quiet
	vendor/bin/phpstan analyse --no-progress
