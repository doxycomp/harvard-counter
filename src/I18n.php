<?php

declare(strict_types=1);

namespace App;

/**
 * Translation and locale negotiation.
 *
 * Strings live in lang/<locale>/<domain>.php as plain arrays, so no gettext
 * extension is required on the target server. Plural keys are stored as
 * 'key.one' and 'key.other'.
 */
final class I18n
{
    public const LOCALES = ['en', 'de', 'fr'];

    /** Shown in the language switcher, in each language's own words. */
    public const LOCALE_NAMES = [
        'en' => 'English',
        'de' => 'Deutsch',
        'fr' => 'Français',
    ];

    private static string $locale = 'en';
    private static string $fallback = 'en';
    private static array $strings = [];
    private static array $missing = [];

    /**
     * @param string[] $domains which lang files to load ('frontend', 'admin')
     */
    public static function boot(string $locale, array $domains = ['frontend']): void
    {
        self::$locale = self::isSupported($locale) ? $locale : self::$fallback;
        self::$strings = [];

        foreach ($domains as $domain) {
            // Load the fallback first so a half-translated locale degrades to
            // English instead of showing raw keys.
            self::$strings += self::loadFile(self::$fallback, $domain);
            self::$strings = self::loadFile(self::$locale, $domain) + self::$strings;
        }
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::LOCALES, true);
    }

    /**
     * Pick a locale from, in order: an explicit request, the visitor's cookie,
     * the coach the token belongs to, the browser's Accept-Language header and
     * finally the configured default.
     */
    public static function negotiate(
        ?string $requested,
        ?string $cookie,
        ?string $contextLocale,
        ?string $acceptLanguage,
        string $default,
    ): string {
        foreach ([$requested, $cookie, $contextLocale] as $candidate) {
            if ($candidate !== null && self::isSupported($candidate)) {
                return $candidate;
            }
        }

        foreach (self::parseAcceptLanguage($acceptLanguage) as $candidate) {
            if (self::isSupported($candidate)) {
                return $candidate;
            }
        }

        return self::isSupported($default) ? $default : self::$fallback;
    }

    public static function t(string $key, array $vars = []): string
    {
        $string = self::$strings[$key] ?? null;

        if ($string === null) {
            self::$missing[$key] = true;

            return $key;
        }

        return self::interpolate($string, $vars);
    }

    public static function tn(string $key, int $count, array $vars = []): string
    {
        $suffix = self::isPluralOne($count) ? 'one' : 'other';
        $vars += ['count' => self::number($count)];

        return self::t("{$key}.{$suffix}", $vars);
    }

    /** Keys requested during this request that had no translation. */
    public static function missingKeys(): array
    {
        return array_keys(self::$missing);
    }

    public static function number(int|float $value): string
    {
        return match (self::$locale) {
            'de' => number_format((float) $value, is_int($value) ? 0 : 2, ',', '.'),
            'fr' => number_format((float) $value, is_int($value) ? 0 : 2, ',', "\u{202F}"),
            default => number_format((float) $value, is_int($value) ? 0 : 2, '.', ','),
        };
    }

    /** Locale-aware date formatting, with a fallback when ext-intl is absent. */
    public static function date(\DateTimeInterface $date, bool $withTime = false): string
    {
        if (class_exists(\IntlDateFormatter::class)) {
            $formatter = new \IntlDateFormatter(
                self::$locale,
                \IntlDateFormatter::MEDIUM,
                $withTime ? \IntlDateFormatter::SHORT : \IntlDateFormatter::NONE,
            );

            return (string) $formatter->format($date);
        }

        $pattern = match (self::$locale) {
            'de' => $withTime ? 'd.m.Y H:i' : 'd.m.Y',
            'fr' => $withTime ? 'd/m/Y H:i' : 'd/m/Y',
            default => $withTime ? 'Y-m-d H:i' : 'Y-m-d',
        };

        return $date->format($pattern);
    }

    /**
     * French treats 0 as singular, English and German do not. Every locale we
     * ship uses a simple one/other split beyond that.
     */
    private static function isPluralOne(int $count): bool
    {
        return self::$locale === 'fr' ? abs($count) <= 1 : abs($count) === 1;
    }

    private static function interpolate(string $string, array $vars): string
    {
        if ($vars === []) {
            return $string;
        }

        $replacements = [];
        foreach ($vars as $name => $value) {
            $replacements['{' . $name . '}'] = (string) $value;
        }

        return strtr($string, $replacements);
    }

    private static function loadFile(string $locale, string $domain): array
    {
        $file = APP_ROOT . "/lang/{$locale}/{$domain}.php";

        return is_file($file) ? (array) require $file : [];
    }

    /** @return string[] language tags, highest quality first */
    private static function parseAcceptLanguage(?string $header): array
    {
        if ($header === null || trim($header) === '') {
            return [];
        }

        $candidates = [];
        foreach (explode(',', $header) as $part) {
            $bits = explode(';q=', trim($part));
            $tag = strtolower(trim($bits[0]));
            if ($tag === '') {
                continue;
            }
            $quality = isset($bits[1]) ? (float) $bits[1] : 1.0;
            // "de-AT" should still match our "de" bundle.
            $candidates[] = ['tag' => explode('-', $tag)[0], 'q' => $quality];
        }

        usort($candidates, static fn (array $a, array $b): int => $b['q'] <=> $a['q']);

        return array_column($candidates, 'tag');
    }
}
