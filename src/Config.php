<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Access to config/config.php.
 *
 * A missing file is not fatal on its own: the front controller turns it into a
 * readable "copy config.example.php" page rather than a stack trace.
 */
final class Config
{
    private static ?array $values = null;
    private static bool $loaded = false;

    public static function load(?string $path = null): void
    {
        $path ??= APP_ROOT . '/config/config.php';
        self::$loaded = true;
        self::$values = is_file($path) ? require $path : null;
    }

    public static function exists(): bool
    {
        self::ensureLoaded();

        return is_array(self::$values);
    }

    /** Read a value with dot notation, e.g. Config::get('db.host'). */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureLoaded();

        $value = self::$values;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** Same as get(), but fails loudly when the key is missing or empty. */
    public static function require(string $key): mixed
    {
        $value = self::get($key);
        if ($value === null || $value === '') {
            throw new RuntimeException("Missing configuration key: {$key}");
        }

        return $value;
    }

    private static function ensureLoaded(): void
    {
        if (!self::$loaded) {
            self::load();
        }
    }
}
