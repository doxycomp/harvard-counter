<?php

declare(strict_types=1);

namespace App;

/**
 * Session cookie policy in one place.
 *
 * SameSite is Lax rather than Strict on purpose: coaches open their link from
 * Discord, which is a cross-site navigation, and Strict would drop the cookie
 * on exactly that first request. Lax still blocks cross-site POSTs, and every
 * writing request carries a CSRF token on top.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (bool) Config::get('cookie_secure', true),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('hc_session');
        session_start();
    }

    /** Regenerate the id, e.g. after a successful sign-in. */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Read a one-shot value and remove it. */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);

        return $value;
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]);
        session_destroy();
    }

    /** A long-lived preference cookie (language, theme, access token). */
    public static function setPreference(string $name, string $value, int $days = 365): void
    {
        setcookie($name, $value, [
            'expires' => time() + ($days * 86400),
            'path' => '/',
            'secure' => (bool) Config::get('cookie_secure', true),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$name] = $value;
    }
}
