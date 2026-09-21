#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Apply pending database migrations.
 *
 * The admin setup page does the same thing, so a server without shell access
 * is not stuck — this is the convenient path, not the only one.
 *
 * Usage: php bin/migrate.php [--status]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line.\n");
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Config;
use App\Migrator;

if (!Config::exists()) {
    fwrite(STDERR, "config/config.php is missing. Copy config/config.example.php first.\n");
    exit(1);
}

$migrator = new Migrator();

try {
    $pending = $migrator->pending();

    if (in_array('--status', $_SERVER['argv'], true)) {
        $applied = $migrator->applied();
        printf("Applied:  %s\n", $applied === [] ? '(none)' : implode(', ', $applied));
        printf("Pending:  %s\n", $pending === [] ? '(none)' : implode(', ', $pending));
        exit(0);
    }

    if ($pending === []) {
        echo "Schema is up to date.\n";
        exit(0);
    }

    foreach ($migrator->migrate() as $version) {
        echo "Applied {$version}\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n");
    exit(1);
}
