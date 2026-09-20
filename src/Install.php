<?php

declare(strict_types=1);

namespace App;

use Throwable;

/**
 * How far along the installation is.
 *
 * Every page consults this before doing real work, so a half-installed system
 * produces a readable instruction instead of a stack trace.
 */
final class Install
{
    public const NO_CONFIG = 'no-config';
    public const NO_DATABASE = 'no-database';
    public const NO_SCHEMA = 'no-schema';
    public const NO_COLLECTIONS = 'no-collections';
    public const NO_ADMIN = 'no-admin';
    public const READY = 'ready';

    public static function status(): string
    {
        if (!Config::exists()) {
            return self::NO_CONFIG;
        }
        if (!Db::isReachable()) {
            return self::NO_DATABASE;
        }
        if (!self::schemaPresent() || self::pendingMigrations() !== []) {
            return self::NO_SCHEMA;
        }
        if (self::collectionCount() === 0) {
            return self::NO_COLLECTIONS;
        }
        if (!Auth::hasAdmin()) {
            return self::NO_ADMIN;
        }

        return self::READY;
    }

    /** True once the very first migration has created the core tables. */
    public static function schemaPresent(): bool
    {
        try {
            return Db::tableExists('admin_users') && Db::tableExists('collections');
        } catch (Throwable) {
            return false;
        }
    }

    /** @return string[] */
    public static function pendingMigrations(): array
    {
        try {
            return (new Migrator())->pending();
        } catch (Throwable) {
            return [];
        }
    }

    public static function collectionCount(): int
    {
        try {
            return (int) Db::fetchValue('SELECT COUNT(*) FROM collections');
        } catch (Throwable) {
            return 0;
        }
    }

    public static function itemCount(): int
    {
        try {
            return (int) Db::fetchValue('SELECT COUNT(*) FROM collection_items');
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * True while the setup page may still be opened with the setup token.
     * Once an admin account exists it is only reachable after signing in.
     */
    public static function setupIsOpen(): bool
    {
        if (!self::schemaPresent()) {
            return true;
        }

        try {
            return !Auth::hasAdmin();
        } catch (Throwable) {
            return true;
        }
    }
}
