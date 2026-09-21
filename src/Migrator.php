<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Applies migrations/NNNN_name.sql in order and records them in
 * schema_migrations. Runnable from the CLI (bin/migrate.php) and from the
 * admin setup page, so a server without shell access can still be upgraded.
 */
final class Migrator
{
    public function __construct(
        private readonly string $directory = APP_ROOT . '/migrations',
    ) {}

    /**
     * Version => absolute path, for every migration file on disk.
     *
     * @return array<string, string>
     */
    public function available(): array
    {
        $files = glob($this->directory . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $migrations = [];
        foreach ($files as $file) {
            $migrations[$this->versionOf($file)] = $file;
        }

        return $migrations;
    }

    /**
     * Versions already recorded in the database.
     *
     * @return list<string>
     */
    public function applied(): array
    {
        $this->ensureRegistry();
        $rows = Db::fetchAll('SELECT version FROM schema_migrations ORDER BY version');

        return array_column($rows, 'version');
    }

    /**
     * Versions on disk that have not been applied yet.
     *
     * @return list<string>
     */
    public function pending(): array
    {
        $applied = $this->applied();

        return array_values(array_diff(array_keys($this->available()), $applied));
    }

    /**
     * Apply every pending migration.
     *
     * @return string[] the versions that were applied
     */
    public function migrate(): array
    {
        $available = $this->available();
        $done = [];

        foreach ($this->pending() as $version) {
            $sql = file_get_contents($available[$version]);
            if ($sql === false) {
                throw new RuntimeException("Cannot read migration {$version}");
            }

            // MariaDB commits DDL implicitly, so a transaction around the whole
            // file would be a false promise. One statement at a time, and stop
            // at the first failure instead.
            foreach ($this->splitStatements($sql) as $statement) {
                Db::pdo()->exec($statement);
            }

            Db::query(
                'INSERT INTO schema_migrations (version, applied_at) VALUES (?, NOW())',
                [$version],
            );
            $done[] = $version;
        }

        return $done;
    }

    private function ensureRegistry(): void
    {
        Db::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(64) NOT NULL PRIMARY KEY,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    private function versionOf(string $file): string
    {
        return basename($file, '.sql');
    }

    /**
     * Split a migration file into statements on semicolons that are not inside
     * a quoted string or an identifier.
     *
     * @return string[]
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($quote !== null) {
                $buffer .= $char;
                if ($char === '\\' && $i + 1 < $length) {
                    $buffer .= $sql[++$i];
                    continue;
                }
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            // Line comment: skip to the end of the line.
            if (($char === '-' && ($sql[$i + 1] ?? '') === '-') || $char === '#') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                $buffer .= "\n";
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === ';') {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $statements[] = $buffer;

        return array_values(array_filter(
            array_map(trim(...), $statements),
            static fn(string $s): bool => $s !== '',
        ));
    }
}
