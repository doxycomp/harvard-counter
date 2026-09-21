<?php

declare(strict_types=1);

/**
 * Shared entry point for every request and CLI script.
 *
 * Registers the autoloader, loads the configuration and exposes the handful of
 * short helpers that templates rely on.
 */

// Static analysis assumes the declared minimum version; this guard is for the
// server that does not meet it, where a readable message beats a parse error
// three files later.
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    http_response_code(500);
    exit('This application requires PHP 8.2 or newer (tested on 8.5).');
}

define('APP_ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, 4));
    $file = APP_ROOT . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\I18n;

/** Escape for HTML output. Deliberately terse — it appears in every template. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Translate a key, replacing {placeholders} from $vars.
 *
 * @param array<string, scalar|null> $vars
 */
function t(string $key, array $vars = []): string
{
    return I18n::t($key, $vars);
}

/**
 * Translate with a plural form chosen by $count.
 *
 * @param array<string, scalar|null> $vars
 */
function tn(string $key, int $count, array $vars = []): string
{
    return I18n::tn($key, $count, $vars);
}

App\Config::load();
App\ErrorHandler::install();
