<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use RuntimeException;

/**
 * The counterpart to Exporter: reads an export file back in.
 *
 * Counters are matched by collection slug and item number, so an instance with
 * different ids works. Existing counters are never silently changed — because
 * a student's counter is shared with their other coaches, blindly adding
 * imported numbers to it would inflate figures that are already correct.
 */
final class ContextImporter
{
    /** @var string[] */
    private array $problems = [];

    /** Parse and sanity-check a file without touching the database. */
    public function parse(string $json): array
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new RuntimeException(t('data.import.error.invalid_json'));
        }
        if (($data['schema'] ?? '') !== Exporter::SCHEMA) {
            throw new RuntimeException(t('data.import.error.schema', [
                'found' => (string) ($data['schema'] ?? ''),
                'expected' => Exporter::SCHEMA,
            ]));
        }
        if (!isset($data['coach']['name']) || trim((string) $data['coach']['name']) === '') {
            throw new RuntimeException(t('data.import.error.no_coach'));
        }

        return $data;
    }

    /**
     * What an import would do, without doing it.
     *
     * @return array{coach:string, coach_exists:bool, students:int, students_existing:int,
     *               counts:int, counts_conflicting:int, events:int, unknown_collections:string[]}
     */
    public function preview(array $data, string $coachName): array
    {
        $students = (array) ($data['students'] ?? []);
        $existingStudents = 0;
        foreach ($students as $student) {
            if (Contexts::findByName((string) ($student['name'] ?? ''), Contexts::STUDENT) !== null) {
                $existingStudents++;
            }
        }

        $unknown = [];
        $conflicting = 0;

        foreach ((array) ($data['counts'] ?? []) as $row) {
            $slug = (string) ($row['collection'] ?? '');
            $collection = Collections::findBySlug($slug);
            if ($collection === null) {
                $unknown[$slug] = true;
                continue;
            }

            // A counter that already exists is a conflict, not an addition.
            $context = (string) ($row['context_kind'] ?? '') === Contexts::COACH
                ? Contexts::findByName($coachName, Contexts::COACH)
                : Contexts::findByName((string) ($row['context'] ?? ''), Contexts::STUDENT);

            if ($context === null) {
                continue;
            }

            $item = Collections::item((int) $collection['id'], (int) ($row['item_no'] ?? 0));
            if ($item === null) {
                continue;
            }

            $current = Db::fetchValue(
                'SELECT uses FROM usage_counts WHERE context_id = ? AND item_id = ?',
                [(int) $context['id'], (int) $item['id']],
            );
            if ($current !== null && (int) $current > 0) {
                $conflicting++;
            }
        }

        return [
            'coach' => $coachName,
            'coach_exists' => Contexts::findByName($coachName, Contexts::COACH) !== null,
            'students' => count($students),
            'students_existing' => $existingStudents,
            'counts' => count((array) ($data['counts'] ?? [])),
            'counts_conflicting' => $conflicting,
            'events' => count((array) ($data['events'] ?? [])),
            'unknown_collections' => array_keys($unknown),
        ];
    }

    /**
     * @param bool $overwrite replace counters that already hold a value
     * @return array{coach_id:int, students:int, counts:int, skipped:int, events:int, problems:string[]}
     */
    public function import(array $data, string $coachName, bool $overwrite = false): array
    {
        $this->problems = [];

        if (Contexts::findByName($coachName, Contexts::COACH) !== null) {
            throw new RuntimeException(t('data.import.name_taken', ['name' => $coachName]));
        }

        return Db::transaction(function () use ($data, $coachName, $overwrite): array {
            $coachId = $this->createCoach($data, $coachName);
            $contexts = [Contexts::COACH . ':' . $coachName => $coachId];

            $students = 0;
            foreach ((array) ($data['students'] ?? []) as $student) {
                $name = trim((string) ($student['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $existing = Contexts::findByName($name, Contexts::STUDENT);
                $studentId = $existing !== null
                    ? (int) $existing['id']
                    : Contexts::createStudent(Str::truncate($name, 120, ''));

                $displayName = $student['display_name'] ?? null;
                Contexts::link($coachId, $studentId, $displayName === null ? null : (string) $displayName);

                $contexts[Contexts::STUDENT . ':' . $name] = $studentId;
                $students++;
            }

            [$counts, $skipped] = $this->importCounts($data, $contexts, $coachName, $overwrite);
            $events = $this->importEvents($data, $contexts, $coachId, $coachName);

            return [
                'coach_id' => $coachId,
                'students' => $students,
                'counts' => $counts,
                'skipped' => $skipped,
                'events' => $events,
                'problems' => $this->problems,
            ];
        });
    }

    private function createCoach(array $data, string $coachName): int
    {
        $coach = (array) $data['coach'];
        $locale = $coach['locale'] ?? null;

        // A fresh token: the link from the old instance must not be revived.
        $coachId = Contexts::createCoach(
            Str::truncate($coachName, 120, ''),
            is_string($locale) && I18n::isSupported($locale) ? $locale : null,
        );

        $format = (array) ($coach['format'] ?? []);
        $defaultCollection = isset($coach['default_collection'])
            ? Collections::findBySlug((string) $coach['default_collection'])
            : null;

        Contexts::update($coachId, [
            'theme' => Theme::isTheme($coach['theme'] ?? null) ? $coach['theme'] : null,
            'color_mode' => Theme::isMode($coach['color_mode'] ?? null) && $coach['color_mode'] !== 'system'
                ? $coach['color_mode']
                : null,
            'default_collection_id' => $defaultCollection === null ? null : (int) $defaultCollection['id'],
            'fmt_header' => Str::truncate((string) ($format['header'] ?? ''), 500, ''),
            'fmt_line' => Str::truncate((string) ($format['line'] ?? ''), 500, ''),
            'fmt_footer' => Str::truncate((string) ($format['footer'] ?? ''), 500, ''),
            'fmt_codeblock' => ($format['codeblock'] ?? false) ? 1 : 0,
            'fmt_codeblock_lang' => Str::truncate((string) ($format['codeblock_lang'] ?? ''), 20, ''),
        ]);

        return $coachId;
    }

    /**
     * @param array<string, int> $contexts
     * @return array{0:int, 1:int} applied and skipped
     */
    private function importCounts(array $data, array $contexts, string $coachName, bool $overwrite): array
    {
        $applied = 0;
        $skipped = 0;

        foreach ((array) ($data['counts'] ?? []) as $row) {
            $itemId = $this->resolveItem($row);
            $contextId = $this->resolveContext($row, $contexts, $coachName);
            $uses = (int) ($row['count'] ?? 0);

            if ($itemId === null || $contextId === null || $uses <= 0) {
                $skipped++;
                continue;
            }

            $current = Db::fetchValue(
                'SELECT uses FROM usage_counts WHERE context_id = ? AND item_id = ?',
                [$contextId, $itemId],
            );

            if ($current !== null && (int) $current > 0 && !$overwrite) {
                $skipped++;
                continue;
            }

            Db::query(
                'INSERT INTO usage_counts (context_id, item_id, uses, updated_at)
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE uses = VALUES(uses), updated_at = NOW()',
                [$contextId, $itemId, $uses],
            );
            $applied++;
        }

        return [$applied, $skipped];
    }

    /** @param array<string, int> $contexts */
    private function importEvents(array $data, array $contexts, int $coachId, string $coachName): int
    {
        $imported = 0;

        foreach ((array) ($data['events'] ?? []) as $row) {
            $itemId = $this->resolveItem($row);
            $contextId = $this->resolveContext($row, $contexts, $coachName);

            if ($itemId === null || $contextId === null) {
                continue;
            }

            try {
                $at = new DateTimeImmutable((string) ($row['at'] ?? 'now'));
            } catch (\Exception) {
                continue;
            }

            Db::query(
                'INSERT INTO usage_events (coach_id, context_id, item_id, counted, created_at)
                 VALUES (?, ?, ?, ?, ?)',
                [
                    $coachId,
                    $contextId,
                    $itemId,
                    ($row['counted'] ?? true) ? 1 : 0,
                    $at->format('Y-m-d H:i:s'),
                ],
            );
            $imported++;
        }

        return $imported;
    }

    private function resolveItem(array $row): ?int
    {
        $slug = (string) ($row['collection'] ?? '');
        $collection = Collections::findBySlug($slug);

        if ($collection === null) {
            $this->problems["collection:{$slug}"] = t('data.import.problem.collection', ['slug' => $slug]);

            return null;
        }

        $item = Collections::item((int) $collection['id'], (int) ($row['item_no'] ?? 0));

        if ($item === null) {
            $this->problems["item:{$slug}"] = t('data.import.problem.items', ['slug' => $slug]);

            return null;
        }

        return (int) $item['id'];
    }

    /** @param array<string, int> $contexts */
    private function resolveContext(array $row, array $contexts, string $coachName): ?int
    {
        $kind = (string) ($row['context_kind'] ?? Contexts::STUDENT);
        $name = $kind === Contexts::COACH ? $coachName : (string) ($row['context'] ?? '');

        return $contexts[$kind . ':' . $name] ?? null;
    }
}
