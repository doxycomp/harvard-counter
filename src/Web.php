<?php

declare(strict_types=1);

namespace App;

/**
 * Shared per-request setup: security headers, session, locale and appearance.
 *
 * Appearance and language arrive as query parameters, are stored in cookies
 * and are then redirected away, so they never stick to links that get pasted
 * into Discord.
 */
final class Web
{
    public const COOKIE_LOCALE = 'hc_lang';

    /**
     * @param string[] $domains translation files to load
     * @return array{locale:string, theme:string, mode:string, basePath:string, carry:array}
     */
    public static function boot(array $domains = ['frontend'], string $basePath = ''): array
    {
        self::sendHeaders();
        Session::start();

        $applied = self::applyAppearanceRequest();
        if ($applied) {
            self::redirectWithoutAppearanceParams();
        }

        $locale = I18n::negotiate(
            self::stringParam('lang'),
            $_COOKIE[self::COOKIE_LOCALE] ?? null,
            null,
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
            (string) Config::get('default_locale', 'en'),
        );
        I18n::boot($locale, $domains);

        return [
            'locale' => $locale,
            'theme' => Theme::resolveTheme(
                self::stringParam('theme'),
                $_COOKIE[Theme::COOKIE_THEME] ?? null,
            ),
            'mode' => Theme::resolveMode(
                self::stringParam('mode'),
                $_COOKIE[Theme::COOKIE_MODE] ?? null,
            ),
            'basePath' => $basePath,
            'carry' => self::carriedParams(),
        ];
    }

    /**
     * Re-apply a coach's stored preferences when the visitor has not chosen
     * any of their own. Called once the access token has been resolved.
     */
    public static function applyContextPreferences(
        array $vars,
        ?string $locale,
        ?string $theme,
        ?string $mode,
        array $domains = ['frontend'],
    ): array {
        if (!isset($_COOKIE[self::COOKIE_LOCALE]) && $locale !== null && I18n::isSupported($locale)) {
            $vars['locale'] = $locale;
            I18n::boot($locale, $domains);
        }
        if (!isset($_COOKIE[Theme::COOKIE_THEME]) && Theme::isTheme($theme)) {
            $vars['theme'] = $theme;
        }
        if (!isset($_COOKIE[Theme::COOKIE_MODE]) && Theme::isMode($mode)) {
            $vars['mode'] = $mode;
        }

        return $vars;
    }

    public static function sendHeaders(): void
    {
        // Access tokens live in the query string, so nothing may leak through
        // a Referer header to a third party.
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header(
            "Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
            . "style-src 'self'; script-src 'self'; form-action 'self'; "
            . "base-uri 'none'; frame-ancestors 'none'",
        );
    }

    public static function redirect(string $location, int $status = 303): never
    {
        header('Location: ' . $location, true, $status);
        exit;
    }

    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function clientIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /** A GET/POST parameter as a trimmed string, or null when absent or empty. */
    public static function stringParam(string $name, ?array $source = null): ?string
    {
        $source ??= $_GET;
        $value = $source[$name] ?? null;
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** Store appearance choices in cookies. Returns true if anything changed. */
    private static function applyAppearanceRequest(): bool
    {
        $changed = false;

        $lang = self::stringParam('lang');
        if ($lang !== null && I18n::isSupported($lang)) {
            Session::setPreference(self::COOKIE_LOCALE, $lang);
            $changed = true;
        }

        $theme = self::stringParam('theme');
        if (Theme::isTheme($theme)) {
            Session::setPreference(Theme::COOKIE_THEME, (string) $theme);
            $changed = true;
        }

        $mode = self::stringParam('mode');
        if (Theme::isMode($mode)) {
            Session::setPreference(Theme::COOKIE_MODE, (string) $mode);
            $changed = true;
        }

        return $changed;
    }

    /** Query parameters other than the appearance ones, for the switcher form. */
    private static function carriedParams(): array
    {
        $carry = $_GET;
        unset($carry['lang'], $carry['theme'], $carry['mode']);

        return array_filter($carry, static fn ($value): bool => is_scalar($value));
    }

    private static function redirectWithoutAppearanceParams(): never
    {
        $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?');
        $query = self::carriedParams();
        $target = $path . ($query === [] ? '' : '?' . http_build_query($query));

        self::redirect($target);
    }
}
