<?php

declare(strict_types=1);

// Применяет миграции из migrations/. Запускается при старте контейнера (docker/entrypoint.sh).

use App\Config;
use App\Database;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = Config::fromEnvironment();
$attempts = 30;

for ($i = 1; ; $i++) {
    try {
        $pdo = Database::connect($config);
        break;
    } catch (PDOException $e) {
        if ($i >= $attempts) {
            fwrite(STDERR, "migrate: база недоступна: {$e->getMessage()}\n");
            exit(1);
        }
        fwrite(STDERR, "migrate: жду базу ({$i}/{$attempts})…\n");
        sleep(1);
    }
}

$applied = Database::migrate($pdo, dirname(__DIR__) . '/migrations');
echo $applied === []
    ? "migrate: схема актуальна\n"
    : 'migrate: применено ' . implode(', ', $applied) . "\n";
