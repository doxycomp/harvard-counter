<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOStatement;
use Throwable;

/**
 * Thin PDO wrapper. Every query in this application goes through here, which
 * is what keeps "prepared statements only" easy to verify.
 */
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string) Config::require('db.host'),
            (int) Config::get('db.port', 3306),
            (string) Config::require('db.name'),
        );

        self::$pdo = new PDO(
            $dsn,
            (string) Config::require('db.user'),
            (string) Config::get('db.pass', ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ],
        );

        return self::$pdo;
    }

    /** True when the database is reachable with the configured credentials. */
    public static function isReachable(): bool
    {
        try {
            self::pdo()->query('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @param list<mixed> $params */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * @param list<mixed> $params
     * @return list<array<string, mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * @param list<mixed> $params
     * @return array<string, mixed>|null
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * First column of the first row, or null when there is no row.
     *
     * @param list<mixed> $params
     */
    public static function fetchValue(string $sql, array $params = []): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /** @param list<mixed> $params */
    public static function insert(string $sql, array $params = []): int
    {
        self::query($sql, $params);

        return (int) self::pdo()->lastInsertId();
    }

    /** Run $fn inside a transaction, rolling back on any exception. */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function tableExists(string $table): bool
    {
        $found = self::fetchValue(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?',
            [$table],
        );

        return (int) $found > 0;
    }
}
