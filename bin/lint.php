#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Syntax-check every PHP file in the repository with the running interpreter.
 *
 * Run it under the oldest supported PHP to catch syntax the minimum version
 * does not understand — CI does exactly that.
 *
 * Usage: php bin/lint.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line.\n");
}

$root = dirname(__DIR__);
$skip = ['/vendor/', '/node_modules/', '/.git/'];

$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
    $path = str_replace('\\', '/', $file->getPathname());
    if ($file->getExtension() !== 'php') {
        continue;
    }
    foreach ($skip as $fragment) {
        if (str_contains($path, $fragment)) {
            continue 2;
        }
    }
    $files[] = $file->getPathname();
}

sort($files);
$failed = 0;

foreach ($files as $file) {
    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);

    if ($status !== 0) {
        $failed++;
        echo implode("\n", $output), "\n";
    }
}

printf(
    "%d files checked with PHP %s: %s\n",
    count($files),
    PHP_VERSION,
    $failed === 0 ? 'no syntax errors' : "{$failed} with errors",
);

exit($failed === 0 ? 0 : 1);
