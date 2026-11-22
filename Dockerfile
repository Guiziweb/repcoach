FROM dunglas/frankenphp:1-php8.4-bookworm AS base

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "expose_php = Off" > "$PHP_INI_DIR/conf.d/zz-hide-the-version.ini" \
    && install-php-extensions intl zip pdo_sqlite opcache

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS demo

ENV APP_ENV=dev APP_DEBUG=0 DATABASE_URL="sqlite:////app/var/demo.db"

COPY . .
RUN composer install --no-interaction --no-progress --no-scripts --prefer-dist \
    && php bin/console doctrine:migrations:migrate --no-interaction \
    && php bin/console app:import-exercises \
    && php bin/console doctrine:fixtures:load --no-interaction --purge-exclusions=exercise

FROM base AS dependencies

ENV APP_ENV=prod APP_DEBUG=0

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && php bin/console importmap:install \
    && php bin/console asset-map:compile \
    && php bin/console cache:clear

FROM base AS runtime

COPY --from=dependencies /app /app
COPY --from=demo /app/var/demo.db /app/var/data_prod.db
COPY Caddyfile /etc/frankenphp/Caddyfile

RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /config/caddy /data/caddy /app/var

ENV PORT=80 APP_ENV=prod APP_DEBUG=0 \
    SYMFONY_TRUSTED_PROXIES=REMOTE_ADDR \
    SYMFONY_TRUSTED_HEADERS=x-forwarded-for,x-forwarded-proto

USER www-data

CMD ["sh", "-c", "test -n \"$APP_SECRET\" || { echo 'APP_SECRET is missing' >&2; exit 1; }; exec frankenphp run --config /etc/frankenphp/Caddyfile"]
