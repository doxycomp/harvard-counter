<?php

declare(strict_types=1);

namespace App;

/**
 * The few multibyte string operations this application needs, with fallbacks
 * for installations without ext-mbstring.
 *
 * The application deliberately depends on no PHP extension beyond PDO, so that
 * a shared host without mbstring or intl still runs it.
 */
final class Str
{
    /** Character count, not byte count. */
    public static function length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        // preg_match_all with /u counts code points; it returns false on
        // invalid UTF-8, where falling back to the byte count is the safe
        // answer for a "long enough?" check.
        $count = preg_match_all('/./us', $value);

        return $count === false ? strlen($value) : $count;
    }

    /** Shorten to $limit characters, appending an ellipsis when cut. */
    public static function truncate(string $value, int $limit, string $ellipsis = '…'): string
    {
        if (self::length($value) <= $limit) {
            return $value;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit, 'UTF-8') . $ellipsis;
        }

        preg_match('/^.{0,' . $limit . '}/us', $value, $matches);

        return ($matches[0] ?? '') . $ellipsis;
    }
}
