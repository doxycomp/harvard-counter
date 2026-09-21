#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Check every colour theme against WCAG AA.
 *
 * The themes are the one place where a well-meant palette can quietly make
 * text unreadable, and every theme comes in two modes — far too many
 * combinations to judge by eye. This computes the contrast ratios instead.
 *
 * Usage: php bin/check_contrast.php [--verbose]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line.\n");
}

require dirname(__DIR__) . '/src/bootstrap.php';

const AA_TEXT = 4.5;   // normal body text
const AA_LARGE = 3.0;  // large text, icons and UI boundaries

/** Foreground, background, minimum ratio, description. */
const PAIRS = [
    ['fg', 'bg', AA_TEXT, 'body text on the page'],
    ['fg', 'surface', AA_TEXT, 'body text on a card'],
    ['fg', 'sunken', AA_TEXT, 'body text on a sentence row'],
    ['muted', 'bg', AA_TEXT, 'muted text on the page'],
    ['muted', 'surface', AA_TEXT, 'muted text on a card'],
    ['muted', 'sunken', AA_TEXT, 'muted text on a sentence row'],
    ['accent-fg', 'accent', AA_TEXT, 'button label'],
    ['accent', 'surface', AA_TEXT, 'link on a card'],
    ['accent', 'accent-soft', AA_TEXT, 'active navigation item'],
    ['danger', 'surface', AA_TEXT, 'error text'],
    ['success', 'surface', AA_TEXT, 'success text'],
    ['focus', 'bg', AA_LARGE, 'focus ring on the page'],
    ['focus', 'surface', AA_LARGE, 'focus ring on a card'],
];

$css = file_get_contents(APP_ROOT . '/public/assets/themes.css');
if ($css === false) {
    fwrite(STDERR, "Cannot read public/assets/themes.css\n");
    exit(1);
}

/** @return array<string, array<string, array<string, string>>> theme => mode => token => hex */
function parseThemes(string $css): array
{
    $themes = [];

    // Each theme is one block; the default one is written as a two-selector rule.
    $pattern = '/(?::root(?:\s*,\s*:root)?\[data-theme="([a-z]+)"\][^{]*|:root\s*,\s*:root\[data-theme="(default)"\])\{([^}]*)\}/';
    preg_match_all($pattern, $css, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $name = $match[1] !== '' ? $match[1] : $match[2];
        $body = $match[3];

        preg_match_all('/--([ld])-([a-z-]+):\s*(#[0-9a-fA-F]{3,8})\s*;/', $body, $declarations, PREG_SET_ORDER);

        foreach ($declarations as $declaration) {
            $mode = $declaration[1] === 'l' ? 'light' : 'dark';
            $themes[$name][$mode][$declaration[2]] = strtolower($declaration[3]);
        }
    }

    return $themes;
}

function channel(float $value): float
{
    return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
}

function luminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    [$r, $g, $b] = array_map(
        static fn(string $part): float => channel(hexdec($part) / 255),
        str_split(substr($hex, 0, 6), 2),
    );

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

function ratio(string $a, string $b): float
{
    $first = luminance($a);
    $second = luminance($b);
    [$light, $dark] = $first > $second ? [$first, $second] : [$second, $first];

    return ($light + 0.05) / ($dark + 0.05);
}

$verbose = in_array('--verbose', $_SERVER['argv'], true);
$themes = parseThemes($css);
$failures = 0;
$checked = 0;

if ($themes === []) {
    fwrite(STDERR, "No themes found — has the structure of themes.css changed?\n");
    exit(1);
}

foreach ($themes as $theme => $modes) {
    foreach ($modes as $mode => $tokens) {
        foreach (PAIRS as [$foreground, $background, $minimum, $description]) {
            if (!isset($tokens[$foreground], $tokens[$background])) {
                printf("  %-8s %-5s MISSING token for %s\n", $theme, $mode, $description);
                $failures++;
                continue;
            }

            $checked++;
            $value = ratio($tokens[$foreground], $tokens[$background]);

            if ($value + 0.005 < $minimum) {
                printf(
                    "  %-8s %-5s %.2f:1 (needs %.1f) — %s  [%s on %s]\n",
                    $theme,
                    $mode,
                    $value,
                    $minimum,
                    $description,
                    $tokens[$foreground],
                    $tokens[$background],
                );
                $failures++;
            } elseif ($verbose) {
                printf("  %-8s %-5s %.2f:1  %s\n", $theme, $mode, $value, $description);
            }
        }
    }
}

printf(
    "\n%d combinations checked across %d themes: %s\n",
    $checked,
    count($themes),
    $failures === 0 ? 'all meet WCAG AA' : "{$failures} below the minimum",
);

exit($failures === 0 ? 0 : 1);
