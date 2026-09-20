<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/**
 * Exports everything that hangs off one coach, so they can fork the repository
 * and keep going on their own server.
 *
 * Counters are referenced by collection slug and item number, never by
 * internal id, so the file also fits an instance where the ids came out
 * differently.
 */
final class Exporter
{
    public const SCHEMA = 'harvard-counter/export@1';

    /**
     * @param bool $includeEvents the session history, off by default — the
     *                            counters are what a move needs, and the
     *                            timestamps should not travel by accident
     */
    public static function exportCoach(int $coachId, bool $includeEvents = false): array
    {
        $coach = Contexts::find($coachId);
        if ($coach === null || $coach['kind'] !== Contexts::COACH) {
            throw new \RuntimeException('Not a coach: ' . $coachId);
        }

        $students = Contexts::studentsOf($coachId);
        $contexts = [$coachId => ['name' => (string) $coach['name'], 'kind' => Contexts::COACH]];
        foreach ($students as $student) {
            $contexts[(int) $student['id']] = [
                'name' => (string) $student['name'],
                'kind' => Contexts::STUDENT,
            ];
        }

        $defaultCollection = null;
        if (($coach['default_collection_id'] ?? null) !== null) {
            $collection = Collections::find((int) $coach['default_collection_id']);
            $defaultCollection = $collection === null ? null : (string) $collection['slug'];
        }

        $export = [
            'schema' => self::SCHEMA,
            'exported_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            'coach' => [
                'name' => (string) $coach['name'],
                'locale' => $coach['locale'],
                'theme' => $coach['theme'],
                'color_mode' => $coach['color_mode'],
                'default_collection' => $defaultCollection,
                'format' => [
                    'header' => (string) ($coach['fmt_header'] ?? ''),
                    'line' => (string) ($coach['fmt_line'] ?? ''),
                    'footer' => (string) ($coach['fmt_footer'] ?? ''),
                    'codeblock' => (bool) ($coach['fmt_codeblock'] ?? false),
                    'codeblock_lang' => (string) ($coach['fmt_codeblock_lang'] ?? ''),
                ],
            ],
            'students' => array_map(static fn (array $s): array => [
                'name' => (string) $s['name'],
                'display_name' => $s['label'] === $s['name'] ? null : (string) $s['label'],
            ], $students),
            'counts' => self::counts(array_keys($contexts), $contexts),
            'note' => 'Student counters are shared with that student\'s other coaches, '
                . 'so these figures may include sessions taught by someone else.',
        ];

        if ($includeEvents) {
            $export['events'] = self::events($coachId, $contexts);
        }

        return $export;
    }

    /** @param array<int, array{name:string, kind:string}> $contexts */
    private static function counts(array $contextIds, array $contexts): array
    {
        if ($contextIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($contextIds), '?'));
        $rows = Db::fetchAll(
            "SELECT uc.context_id, uc.uses, ci.item_no, c.slug
             FROM usage_counts uc
             JOIN collection_items ci ON ci.id = uc.item_id
             JOIN collections c ON c.id = ci.collection_id
             WHERE uc.context_id IN ({$placeholders}) AND uc.uses > 0
             ORDER BY c.slug, ci.item_no",
            $contextIds,
        );

        return array_map(static fn (array $row): array => [
            'collection' => (string) $row['slug'],
            'item_no' => (int) $row['item_no'],
            'context' => $contexts[(int) $row['context_id']]['name'],
            'context_kind' => $contexts[(int) $row['context_id']]['kind'],
            'count' => (int) $row['uses'],
        ], $rows);
    }

    /** Only this coach's own sessions, never another coach's. */
    private static function events(int $coachId, array $contexts): array
    {
        $rows = Db::fetchAll(
            'SELECT e.context_id, e.counted, e.created_at, ci.item_no, c.slug
             FROM usage_events e
             JOIN collection_items ci ON ci.id = e.item_id
             JOIN collections c ON c.id = ci.collection_id
             WHERE e.coach_id = ?
             ORDER BY e.created_at',
            [$coachId],
        );

        $events = [];
        foreach ($rows as $row) {
            $context = $contexts[(int) $row['context_id']] ?? null;
            if ($context === null) {
                continue;
            }
            $events[] = [
                'collection' => (string) $row['slug'],
                'item_no' => (int) $row['item_no'],
                'context' => $context['name'],
                'context_kind' => $context['kind'],
                'counted' => (int) $row['counted'] === 1,
                'at' => (new DateTimeImmutable((string) $row['created_at']))->format(DATE_ATOM),
            ];
        }

        return $events;
    }

    public static function toJson(array $export): string
    {
        return json_encode(
            $export,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    /** One row per context and item, for a spreadsheet. */
    public static function toCsv(array $export): string
    {
        $handle = fopen('php://temp', 'r+b');
        fputcsv($handle, ['collection', 'item_no', 'context', 'context_kind', 'count'], ',', '"', '');

        foreach ($export['counts'] as $row) {
            fputcsv($handle, [
                $row['collection'],
                $row['item_no'],
                $row['context'],
                $row['context_kind'],
                $row['count'],
            ], ',', '"', '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /** A filename that is safe on every platform. */
    public static function filename(string $coachName, string $extension): string
    {
        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $coachName));
        $slug = trim($slug, '-');

        return sprintf(
            'harvard-counter-%s-%s.%s',
            $slug === '' ? 'export' : $slug,
            date('Y-m-d'),
            $extension,
        );
    }

    /** Send the file as a download and stop. */
    public static function download(string $body, string $filename, string $contentType): never
    {
        header('Content-Type: ' . $contentType . '; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($body));
        header('X-Content-Type-Options: nosniff');
        echo $body;
        exit;
    }
}
