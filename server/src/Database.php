<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    public static function connect(Config $config): PDO
    {
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $config->dbHost, $config->dbPort, $config->dbName);

        return new PDO($dsn, $config->dbUser, $config->dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * Применяет новые *.sql из папки миграций по порядку имён.
     * Применённые записываются в schema_migrations, каждая миграция — в своей транзакции.
     *
     * @return list<string> имена применённых сейчас файлов
     */
    public static function migrate(PDO $pdo, string $dir): array
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            version    TEXT PRIMARY KEY,
            applied_at TIMESTAMPTZ NOT NULL DEFAULT now()
        )');

        $done = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob(rtrim($dir, '/') . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $applied = [];
        foreach ($files as $file) {
            $version = basename($file);
            if (in_array($version, $done, true)) {
                continue;
            }
            $pdo->beginTransaction();
            try {
                $pdo->exec((string) file_get_contents($file));
                $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([$version]);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
            $applied[] = $version;
        }

        return $applied;
    }
}
