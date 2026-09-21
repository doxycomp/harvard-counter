#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Import the sentence collections from data/collections into the database.
 *
 * Safe to run repeatedly: items keep their ids, so counters survive.
 *
 * Usage: php bin/import_collections.php [--dry-run]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line.\n");
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\CollectionImporter;
use App\Config;

if (!Config::exists()) {
    fwrite(STDERR, "config/config.php is missing. Copy config/config.example.php first.\n");
    exit(1);
}

$importer = new CollectionImporter();
$dryRun = in_array('--dry-run', $_SERVER['argv'], true);

try {
    $paths = $importer->discover();
    if ($paths === []) {
        fwrite(STDERR, "No collections found under data/collections.\n");
        exit(1);
    }

    if ($dryRun) {
        foreach ($paths as $path) {
            echo 'Would import ' . basename($path) . "\n";
        }
        exit(0);
    }

    foreach ($paths as $path) {
        $result = $importer->import($path);
        printf(
            "Imported %s: %d items, %d lines\n",
            $result['slug'],
            $result['items'],
            $result['lines'],
        );
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Import failed: ' . $e->getMessage() . "\n");
    exit(1);
}
