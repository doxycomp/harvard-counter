<?php

declare(strict_types=1);

namespace App;

/**
 * One CSRF token per session, checked on every writing request — including in
 * the public frontend, where counting is a POST.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf';
    public const FIELD = '_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /** Hidden input for a form. */
    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::FIELD,
            e(self::token()),
        );
    }

    public static function isValid(?string $candidate): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($expected)
            && is_string($candidate)
            && hash_equals($expected, $candidate);
    }

    /** Abort the request unless the posted token matches. */
    public static function verify(): void
    {
        $posted = $_POST[self::FIELD] ?? null;
        if (!self::isValid(is_string($posted) ? $posted : null)) {
            http_response_code(419);
            exit('Session expired. Please reload the page and try again.');
        }
    }
}
