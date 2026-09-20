<?php

declare(strict_types=1);

namespace App;

use Throwable;

/**
 * Turns uncaught errors into a short, path-free page.
 *
 * Without this, PHP's default display_errors setting decides what a visitor
 * sees — which on a misconfigured server means absolute paths and stack
 * traces. Details go to the error log instead; set 'debug' => true in
 * config/config.php to see them in the browser during development.
 */
final class ErrorHandler
{
    private static bool $installed = false;

    public static function install(): void
    {
        if (self::$installed) {
            return;
        }
        self::$installed = true;

        $debug = (bool) Config::get('debug', false);

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_exception_handler(static function (Throwable $e) use ($debug): void {
            error_log(sprintf(
                '%s: %s in %s:%d',
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            ));

            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, $e->getMessage() . "\n");
                exit(1);
            }

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
            }

            echo self::page($debug ? $e : null);
        });

        // An uncaught fatal (out of memory, a parse error in an included
        // file) never reaches the exception handler.
        register_shutdown_function(static function () use ($debug): void {
            $error = error_get_last();
            if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                return;
            }

            if (PHP_SAPI === 'cli') {
                return;
            }

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
                echo self::page(null);
            } elseif ($debug) {
                echo '<pre>' . htmlspecialchars($error['message'], ENT_QUOTES) . '</pre>';
            }
        });
    }

    /**
     * Deliberately self-contained: it must render even when the template
     * engine or the database is the thing that failed.
     */
    private static function page(?Throwable $e): string
    {
        $detail = '';
        if ($e !== null) {
            $detail = '<pre style="white-space:pre-wrap;overflow-x:auto">'
                . htmlspecialchars(
                    $e::class . ': ' . $e->getMessage() . "\n\n" . $e->getTraceAsString(),
                    ENT_QUOTES,
                )
                . '</pre>';
        }

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Something went wrong</title>
                <style>
                    body { font-family: system-ui, sans-serif; line-height: 1.5;
                           margin: 0; padding: 2rem 1rem; }
                    main { max-width: 36rem; margin: 0 auto; }
                </style>
            </head>
            <body>
            <main>
                <h1>Something went wrong</h1>
                <p>The request could not be completed. The details have been written
                   to the server's error log.</p>
                {$detail}
            </main>
            </body>
            </html>
            HTML;
    }
}
