<?php

declare(strict_types=1);

namespace App;

/**
 * Access tokens for coach links.
 *
 * 22 base62 characters is roughly 131 bits of entropy — short enough to paste
 * into Discord, far too long to guess.
 */
final class Token
{
    public const LENGTH = 22;

    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public static function generate(int $length = self::LENGTH): string
    {
        $alphabetSize = strlen(self::ALPHABET);
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= self::ALPHABET[random_int(0, $alphabetSize - 1)];
        }

        return $token;
    }

    /** Cheap shape check before touching the database. */
    public static function looksValid(?string $candidate): bool
    {
        return is_string($candidate)
            && strlen($candidate) === self::LENGTH
            && preg_match('/^[A-Za-z0-9]+$/', $candidate) === 1;
    }
}
