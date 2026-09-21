<?php

declare(strict_types=1);

namespace App;

/**
 * Colour theme and light/dark mode.
 *
 * Both are resolved on the server and rendered into the <html> element, so the
 * correct palette is in place before the first paint — no flash of the wrong
 * theme while JavaScript boots.
 */
final class Theme
{
    /** Flag themes grouped together, then the purely aesthetic ones. */
    public const THEMES = [
        'default', 'pride', 'trans', 'nonbinary', 'sapphic', 'ace', 'pastel', 'mono',
    ];
    public const MODES = ['system', 'light', 'dark'];

    public const DEFAULT_THEME = 'default';
    public const DEFAULT_MODE = 'system';

    public const COOKIE_THEME = 'hc_theme';
    public const COOKIE_MODE = 'hc_mode';

    public static function isTheme(?string $value): bool
    {
        return $value !== null && in_array($value, self::THEMES, true);
    }

    public static function isMode(?string $value): bool
    {
        return $value !== null && in_array($value, self::MODES, true);
    }

    /** First supported value wins: request, cookie, the coach's preference. */
    public static function resolveTheme(?string ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            if (self::isTheme($candidate)) {
                return $candidate;
            }
        }

        return self::DEFAULT_THEME;
    }

    public static function resolveMode(?string ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            if (self::isMode($candidate)) {
                return $candidate;
            }
        }

        return self::DEFAULT_MODE;
    }

    /** Translation key for a theme's label in the picker. */
    public static function label(string $theme): string
    {
        return 'theme.name.' . $theme;
    }
}
