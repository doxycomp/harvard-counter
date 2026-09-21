<?php

declare(strict_types=1);

/**
 * Copy this file to config/config.php and fill it in.
 *
 * config/config.php is gitignored and lives outside the document root, so the
 * credentials below are never served by the web server.
 */

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'harvard_counter',
        'user' => 'harvard_counter',
        'pass' => '',
    ],

    /**
     * Required to reach /admin/setup.php before the first admin account
     * exists. Generate one with:
     *
     *   php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
     *
     * Once an admin account exists the setup page is only reachable behind the
     * login and this token is no longer used.
     */
    'setup_token' => '',

    /**
     * Public address of the application, e.g. 'https://counter.example.org/'.
     * Used to build the full access links coaches receive. Leave empty to
     * derive it from each request; set it when running behind a reverse proxy
     * or when links are generated from the command line.
     */
    'base_url' => '',

    /** Fallback UI language when nothing else matches: en, de or fr. */
    'default_locale' => 'en',

    /**
     * Send cookies with the Secure flag. Keep this true in production. Set it
     * to false only for local development over plain HTTP.
     */
    'cookie_secure' => true,

    /** Show stack traces in the browser. Never enable this in production. */
    'debug' => false,
];
