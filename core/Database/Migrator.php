<?php

declare(strict_types=1);

namespace Ds\Database;

use PDO;
use RuntimeException;

final class Migrator
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    public function migrate(string $directory): array
    {
        $this->ensureRepository();
        $applied = array_column(
            $this->database->select('SELECT name FROM migrations'),
            'name'
        );
        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);
        $batch = (int) ($this->database->select('SELECT COALESCE(MAX(batch), 0) AS batch FROM migrations')[0]['batch'] ?? 0) + 1;
        $ran = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = require $file;
            if (!$migration instanceof Migration && !$migration instanceof \\Ds\\Foundations\\Migrate\\Migrations) {
                throw new RuntimeException("Migration file {$file} must return a v4 Migration or a legacy Migrations instance.");
            }

            $this->database->transaction(function (PDO $connection) use ($migration, $name, $batch): void {
                if ($migration instanceof Migration) {
                    $migration->up($connection);
                } else {
                    $migration->up(new \\Ds\\Foundations\\Migrate\\Scheme($this->database, $connection));
                }
                $statement = $connection->prepare(
                    'INSERT INTO migrations (name, batch) VALUES (:name, :batch)'
                );
                $statement->execute(['name' => $name, 'batch' => $batch]);
            });

            $ran[] = $name;
        }

        return $ran;
    }

    public function rollback(?string $directory = null): ?string
    {
        $this->ensureRepository();
        $latest = $this->database->select(
            'SELECT name FROM migrations ORDER BY batch DESC, name DESC LIMIT 1'
        )[0]['name'] ?? null;

        if (!is_string($latest)) {
            return null;
        }

        $file = $this->migrationFileFor($latest, $directory ?? base_path('database/migrations'));
        if ($file === null) {
            throw new RuntimeException("Migration source for [{$latest}] was not found.");
        }

        $migration = require $file;
        if (!$migration instanceof Migration && !$migration instanceof \\Ds\\Foundations\\Migrate\\Migrations) {
            throw new RuntimeException("Migration file {$file} must return a v4 Migration or a legacy Migrations instance.");
        }

        $this->database->transaction(function (PDO $connection) use ($migration, $latest): void {
            if ($migration instanceof Migration) {
                $migration->down($connection);
            } else {
                $migration->down(new \\Ds\\Foundations\\Migrate\\Scheme($this->database, $connection));
            }
            $statement = $connection->prepare('DELETE FROM migrations WHERE name = :name');
            $statement->execute(['name' => $latest]);
        });

        return $latest;
    }

    public function status(string $directory): array
    {
        $this->ensureRepository();
        $applied = array_fill_keys(
            array_column($this->database->select('SELECT name FROM migrations'), 'name'),
            true
        );

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);

        return array_map(static function (string $file) use ($applied): array {
            $name = basename($file, '.php');
            return ['name' => $name, 'applied' => isset($applied[$name])];
        }, $files);
    }

    private function ensureRepository(): void
    {
        $this->database->execute(
            'CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(255) PRIMARY KEY, batch INTEGER NOT NULL, ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)'
        );
    }

    private function migrationFileFor(string $name, string $directory): ?string
    {
        $file = $directory . DIRECTORY_SEPARATOR . $name . '.php';
        return is_file($file) ? $file : null;
    }
}
