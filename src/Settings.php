<?php

declare(strict_types=1);

namespace App;

/**
 * Key/value settings that an operator can change without a deployment:
 * default Discord templates per locale, the demo-mode notice, and so on.
 */
final class Settings
{
    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();

        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        Db::query(
            'INSERT INTO settings (k, v, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = NOW()',
            [$key, $value],
        );

        self::load();
        self::$cache[$key] = $value;
    }

    /**
     * All settings whose key starts with $prefix, keyed without the prefix.
     *
     * @return array<string, string|null>
     */
    public static function withPrefix(string $prefix): array
    {
        self::load();

        $result = [];
        foreach (self::$cache as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $result[substr($key, strlen($prefix))] = $value;
            }
        }

        return $result;
    }

    /**
     * The default Discord template for a locale, falling back to English.
     *
     * @return array{header:string, line:string, footer:string}
     */
    public static function defaultFormat(string $locale): array
    {
        $pick = static fn(string $part): string => (string) (
            self::get("fmt.default.{$locale}.{$part}")
            ?? self::get("fmt.default.en.{$part}")
            ?? ''
        );

        return [
            'header' => $pick('header'),
            'line' => $pick('line'),
            'footer' => $pick('footer'),
        ];
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = [];
        foreach (Db::fetchAll('SELECT k, v FROM settings') as $row) {
            self::$cache[$row['k']] = $row['v'];
        }
    }
}
