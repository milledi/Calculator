# --- Автозагрузчик Composer без dev-пакетов ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-autoloader --no-scripts
COPY src ./src
RUN composer dump-autoload --no-dev --classmap-authoritative

# --- Dev-зависимости (PHPUnit) для тестов ---
FROM composer:2 AS vendor-dev
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts

# --- Тесты: docker compose --profile test run --rm api-tests ---
FROM php:8.3-cli-alpine AS test
WORKDIR /app
COPY --from=vendor-dev /app/vendor ./vendor
COPY . .
CMD ["vendor/bin/phpunit"]

# --- Рабочий образ: Apache + mod_php, порт 4000 ---
FROM php:8.3-apache AS runtime

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/^Listen 80$/Listen 4000/' /etc/apache2/ports.conf \
 && echo 'ServerName localhost' > /etc/apache2/conf-available/server-name.conf \
 && a2enconf server-name \
 && sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf

WORKDIR /var/www/app
COPY --from=vendor /app/vendor ./vendor
COPY public ./public
COPY src ./src
COPY templates ./templates
COPY migrations ./migrations
COPY bin ./bin

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/app-entrypoint && chmod +x /usr/local/bin/app-entrypoint

EXPOSE 4000
HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=3 \
  CMD php -r 'exit(@file_get_contents("http://127.0.0.1:4000/api/health") === false ? 1 : 0);'

ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
