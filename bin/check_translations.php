#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Keep the locales aligned.
 *
 * Reports three kinds of drift:
 *   - keys present in the reference locale but missing from another one
 *   - keys present in another locale but gone from the reference
 *   - keys used in the code via t()/tn() that no locale defines
 *
 * Exits non-zero when anything is off, so it can run as a smoke test.
 *
 * Usage: php bin/check_translations.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line.\n");
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\I18n;

const REFERENCE = 'en';
const DOMAINS = ['frontend', 'admin'];

$problems = 0;

/** @return string[] */
$loadKeys = static function (string $locale, string $domain): array {
    $file = APP_ROOT . "/lang/{$locale}/{$domain}.php";
    if (!is_file($file)) {
        return [];
    }

    return array_keys((array) require $file);
};

$referenceKeys = [];

foreach (DOMAINS as $domain) {
    $reference = $loadKeys(REFERENCE, $domain);
    $referenceKeys = [...$referenceKeys, ...$reference];

    if ($reference === []) {
        fwrite(STDERR, "Reference locale has no {$domain} strings.\n");
        $problems++;
        continue;
    }

    foreach (I18n::LOCALES as $locale) {
        if ($locale === REFERENCE) {
            continue;
        }

        $keys = $loadKeys($locale, $domain);

        $missing = array_diff($reference, $keys);
        $extra = array_diff($keys, $reference);

        foreach ($missing as $key) {
            echo "missing  {$locale}/{$domain}: {$key}\n";
            $problems++;
        }
        foreach ($extra as $key) {
            echo "orphaned {$locale}/{$domain}: {$key}\n";
            $problems++;
        }
    }
}

// Keys referenced in code but defined nowhere. Only literal calls are found,
// which is the common case; dynamic keys are built from a prefix and checked
// by hand.
$used = [];
$directory = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(APP_ROOT, FilesystemIterator::SKIP_DOTS),
);

foreach ($directory as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    if (str_contains($path, '/lang/') || str_contains($path, '/bin/')) {
        continue;
    }

    $source = (string) file_get_contents($file->getPathname());

    // The trailing [,)] requirement skips keys assembled by concatenation,
    // such as t('appearance.mode.' . $mode) — those are checked by hand.
    if (preg_match_all('/\btn?\(\s*[\'"]([a-z0-9._]+)[\'"]\s*[,)]/i', $source, $matches)) {
        foreach (array_filter($matches[1]) as $key) {
            $used[$key] = $path;
        }
    }
}

$known = array_flip($referenceKeys);

foreach ($used as $key => $path) {
    if (isset($known[$key])) {
        continue;
    }
    // Plural keys are stored with .one/.other suffixes.
    if (isset($known[$key . '.one']) || isset($known[$key . '.other'])) {
        continue;
    }

    echo "undefined key {$key} (used in " . str_replace(APP_ROOT . '/', '', $path) . ")\n";
    $problems++;
}

if ($problems === 0) {
    echo "Translations are consistent.\n";
    exit(0);
}

echo "\n{$problems} problem(s) found.\n";
exit(1);
