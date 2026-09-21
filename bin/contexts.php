#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Manage coaches and students from the command line — handy for setting a
 * server up in one go. The admin area offers the same through its interface.
 *
 * Links are printed in full when config 'base_url' is set; there is no request
 * to derive a host from here.
 *
 * Usage:
 *   php bin/contexts.php list
 *   php bin/contexts.php coach:add "Robin" [--locale=de] [--theme=trans]
 *   php bin/contexts.php student:add "Alex" --coach="Robin" [--as="Al"]
 *   php bin/contexts.php link "Alex" "Robin" [--as="Al"]
 *   php bin/contexts.php token:show "Robin"
 *   php bin/contexts.php token:rotate "Robin"
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line.\n");
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Config;
use App\Contexts;
use App\Db;
use App\I18n;
use App\Theme;

/** Full link when base_url is configured; a relative one otherwise. */
$link = static function (string $token): string {
    $base = trim((string) Config::get('base_url', ''));

    return ($base === '' ? '/' : rtrim($base, '/') . '/') . '?t=' . $token;
};

if (!Config::exists()) {
    fwrite(STDERR, "config/config.php is missing.\n");
    exit(1);
}

$args = array_slice($_SERVER['argv'], 1);
$command = array_shift($args) ?? 'list';

$options = [];
$positional = [];
foreach ($args as $arg) {
    if (str_starts_with($arg, '--')) {
        [$name, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, '');
        $options[$name] = $value;
        continue;
    }
    $positional[] = $arg;
}

$fail = static function (string $message): never {
    fwrite(STDERR, $message . "\n");
    exit(1);
};

$requireCoach = static function (string $name) use ($fail): array {
    $coach = Contexts::findByName($name, Contexts::COACH);
    if ($coach === null) {
        $fail("No coach named \"{$name}\".");
    }

    return $coach;
};

try {
    switch ($command) {
        case 'list':
            foreach (Contexts::coaches(false) as $coach) {
                printf(
                    "coach   %-24s token=%s%s\n",
                    $coach['name'],
                    $coach['access_token'] ?? '(none)',
                    (int) $coach['is_active'] === 1 ? '' : '  [inactive]',
                );
                foreach (Contexts::studentsOf((int) $coach['id']) as $student) {
                    $shared = Contexts::coachCountFor((int) $student['id']);
                    printf(
                        "  student %-22s%s\n",
                        $student['label'],
                        $shared > 1 ? "  [shared with {$shared} coaches]" : '',
                    );
                }
            }
            break;

        case 'coach:add':
            $name = $positional[0] ?? $fail('Usage: coach:add "<name>"');
            if (Contexts::findByName($name, Contexts::COACH) !== null) {
                $fail("A coach named \"{$name}\" already exists.");
            }

            $locale = $options['locale'] ?? null;
            if ($locale !== null && !I18n::isSupported($locale)) {
                $fail("Unsupported locale: {$locale}");
            }

            $id = Contexts::createCoach($name, $locale);

            if (isset($options['theme'])) {
                if (!Theme::isTheme($options['theme'])) {
                    $fail("Unknown theme: {$options['theme']}");
                }
                Db::query('UPDATE contexts SET theme = ? WHERE id = ?', [$options['theme'], $id]);
            }

            $coach = Contexts::find($id);
            printf("Created coach \"%s\".\n", $name);
            printf("Access link: %s\n", $link((string) $coach['access_token']));
            break;

        case 'student:add':
            $name = $positional[0] ?? $fail('Usage: student:add "<name>" --coach="<coach>"');
            $coachName = $options['coach'] ?? $fail('Missing --coach="<name>"');
            $coach = $requireCoach($coachName);

            $student = Contexts::findByName($name, Contexts::STUDENT);
            if ($student !== null) {
                printf("A student named \"%s\" already exists; linking the existing record.\n", $name);
                $studentId = (int) $student['id'];
            } else {
                $studentId = Contexts::createStudent($name);
            }

            Contexts::link((int) $coach['id'], $studentId, $options['as'] ?? null);
            printf("Linked \"%s\" to coach \"%s\".\n", $name, $coach['name']);

            $shared = Contexts::coachCountFor($studentId);
            if ($shared > 1) {
                printf(
                    "Note: this student now has %d coaches and their counter is shared between them.\n",
                    $shared,
                );
            }
            break;

        case 'link':
            $studentName = $positional[0] ?? $fail('Usage: link "<student>" "<coach>"');
            $coachName = $positional[1] ?? $fail('Usage: link "<student>" "<coach>"');
            $coach = $requireCoach($coachName);
            $student = Contexts::findByName($studentName, Contexts::STUDENT)
                ?? $fail("No student named \"{$studentName}\".");

            Contexts::link((int) $coach['id'], (int) $student['id'], $options['as'] ?? null);
            printf("Linked \"%s\" to coach \"%s\".\n", $studentName, $coach['name']);
            break;

        case 'token:show':
            $coach = $requireCoach($positional[0] ?? $fail('Usage: token:show "<coach>"'));
            printf("%s\n", $link((string) $coach['access_token']));
            break;

        case 'token:rotate':
            $coach = $requireCoach($positional[0] ?? $fail('Usage: token:rotate "<coach>"'));
            $token = Contexts::rotateToken((int) $coach['id']);
            printf("New link: %s\n", $link($token));
            printf("The previous link no longer works.\n");
            break;

        default:
            $fail("Unknown command: {$command}");
    }
} catch (Throwable $e) {
    $fail('Failed: ' . $e->getMessage());
}
