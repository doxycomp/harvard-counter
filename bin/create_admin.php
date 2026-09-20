#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Create an administrator account, or reset an existing one's password.
 *
 * The password is read from stdin rather than taken as an argument so it does
 * not end up in the shell history or the process list.
 *
 * Usage: php bin/create_admin.php <username>
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line.\n");
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Auth;
use App\Config;
use App\Db;

if (!Config::exists()) {
    fwrite(STDERR, "config/config.php is missing. Copy config/config.example.php first.\n");
    exit(1);
}

$username = $argv[1] ?? null;
if ($username === null || mb_strlen($username) < 3) {
    fwrite(STDERR, "Usage: php bin/create_admin.php <username>\n");
    exit(1);
}

/** Read a line without echoing it, where the platform allows. */
$readSecret = static function (string $prompt): string {
    fwrite(STDOUT, $prompt);

    if (DIRECTORY_SEPARATOR !== '\\' && stream_isatty(STDIN)) {
        shell_exec('stty -echo');
        $value = (string) fgets(STDIN);
        shell_exec('stty echo');
        fwrite(STDOUT, "\n");
    } else {
        // Windows consoles cannot suppress the echo this way; warn instead of
        // pretending the input is hidden.
        $value = (string) fgets(STDIN);
    }

    return rtrim($value, "\r\n");
};

if (DIRECTORY_SEPARATOR === '\\') {
    fwrite(STDOUT, "Note: the password will be visible while typing on this platform.\n");
}

$password = $readSecret('Password: ');
$repeat = $readSecret('Repeat password: ');

if (($problem = Auth::validatePassword($password, $repeat)) !== null) {
    $message = $problem === 'setup.admin.error.password_short'
        ? sprintf('The password must be at least %d characters long.', Auth::minPasswordLength())
        : 'The two passwords do not match.';
    fwrite(STDERR, $message . "\n");
    exit(1);
}

try {
    $existing = Db::fetchValue('SELECT id FROM admin_users WHERE username = ?', [$username]);

    if ($existing !== null) {
        Auth::changePassword((int) $existing, $password);
        echo "Password updated for {$username}.\n";
        exit(0);
    }

    Auth::createAdmin($username, $password);
    echo "Created administrator {$username}.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Failed: ' . $e->getMessage() . "\n");
    exit(1);
}
