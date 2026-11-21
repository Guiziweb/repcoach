.PHONY: install migrate

install:
	composer install

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
