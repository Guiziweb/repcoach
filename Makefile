.PHONY: install migrate cs cs-fix

install:
	composer install

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

cs:
	vendor/bin/php-cs-fixer check --diff

cs-fix:
	vendor/bin/php-cs-fixer fix
