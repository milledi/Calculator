#!/bin/sh
# Перед стартом Apache применяем миграции БД, затем запускаем команду контейнера
set -e
php /var/www/app/bin/migrate.php
exec "$@"
