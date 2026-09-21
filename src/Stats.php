<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/**
 * Statistics for the admin area.
 *
 * Everything here reads usage_events rather than usage_counts where it can,
 * because the event log is the part that stays attributed to the teaching
 * coach. The counter itself is shared between a student's coaches, so it
 * cannot answer "how much of this was mine".
 *
 * Every method takes a coach id or null; null means across all coaches.
 */
final class Stats
{
    /**
     * @return array{total:int, undone:int, last:?string, students:int}
     */
    public static function summaryForCoach(?int $coachId): array
    {
        [$where, $params] = self::coachFilter($coachId);

        $row = Db::fetchOne(
            "SELECT
                COALESCE(SUM(counted = 1), 0) AS total,
                COALESCE(SUM(counted = 0), 0) AS undone,
                MAX(CASE WHEN counted = 1 THEN created_at END) AS last_used
             FROM usage_events WHERE {$where}",
            $params,
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'undone' => (int) ($row['undone'] ?? 0),
            'last' => $row['last_used'] ?? null,
            'students' => $coachId === null
                ? count(Contexts::students())
                : Contexts::studentCount($coachId),
        ];
    }

    /**
     * Uses per month for the last $months months, oldest first and with empty
     * months present, so the bar chart does not silently skip a quiet period.
     *
     * @return array<string, int> 'YYYY-MM' => count
     */
    public static function monthly(?int $coachId, int $months = 12): array
    {
        $buckets = [];
        $cursor = new DateTimeImmutable('first day of this month 00:00:00');
        for ($i = $months - 1; $i >= 0; $i--) {
            $buckets[$cursor->modify("-{$i} months")->format('Y-m')] = 0;
        }

        [$where, $params] = self::coachFilter($coachId);

        $rows = Db::fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS bucket, COUNT(*) AS uses
             FROM usage_events
             WHERE {$where} AND counted = 1
               AND created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL ? MONTH)
             GROUP BY bucket",
            [...$params, $months - 1],
        );

        foreach ($rows as $row) {
            if (array_key_exists($row['bucket'], $buckets)) {
                $buckets[$row['bucket']] = (int) $row['uses'];
            }
        }

        return $buckets;
    }

    /**
     * How sessions split across students (and coaches' own rows).
     *
     * @return array<int, array{name:string, kind:string, uses:int}>
     */
    public static function byStudent(?int $coachId): array
    {
        [$where, $params] = self::coachFilter($coachId, 'e.');

        $rows = Db::fetchAll(
            "SELECT e.context_id, c.name, c.kind, COUNT(*) AS uses
             FROM usage_events e
             JOIN contexts c ON c.id = e.context_id
             WHERE {$where} AND e.counted = 1
             GROUP BY e.context_id, c.name, c.kind
             ORDER BY uses DESC, c.name",
            $params,
        );

        return array_map(static fn(array $row): array => [
            'name' => (string) $row['name'],
            'kind' => (string) $row['kind'],
            'uses' => (int) $row['uses'],
        ], $rows);
    }

    /**
     * Self-practice per student, kept apart from the lesson figures above.
     * For a coach: their students; for null: every student.
     *
     * @return list<array{id:int, name:string, uses:int, last:?string}>
     */
    public static function selfPractice(?int $coachId): array
    {
        $join = $coachId === null ? '' : 'JOIN context_links l ON l.student_id = c.id AND l.coach_id = ?';
        $params = $coachId === null ? [] : [$coachId];

        $rows = Db::fetchAll(
            "SELECT c.id, c.name, COUNT(*) AS uses, MAX(s.created_at) AS last_used
             FROM self_events s
             JOIN contexts c ON c.id = s.context_id
             {$join}
             WHERE s.counted = 1
             GROUP BY c.id, c.name
             ORDER BY uses DESC, c.name",
            $params,
        );

        return array_map(static fn(array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'uses' => (int) $row['uses'],
            'last' => $row['last_used'] ?? null,
        ], $rows);
    }

    /**
     * One student's practice on their own, per item of a collection, with the
     * lesson counter beside it for comparison.
     *
     * Only items that came up at all, alone or in a lesson, are returned; the
     * rest would be a long column of zeros. The self figure is the counter,
     * so undone uses are already out of it; the date is the last use that
     * stayed counted.
     *
     * Not scoped by coach: the lesson counter is shared between a student's
     * coaches, and the caller decides who may see the student at all.
     *
     * @return list<array{item_no: int, self: int, lesson: int, last: ?string}>
     */
    public static function selfBreakdown(int $studentId, int $collectionId): array
    {
        $rows = Db::fetchAll(
            'SELECT i.item_no, COALESCE(s.uses, 0) AS self_uses, COALESCE(u.uses, 0) AS lesson_uses, e.last_used
             FROM collection_items i
             LEFT JOIN self_counts s ON s.item_id = i.id AND s.context_id = ?
             LEFT JOIN usage_counts u ON u.item_id = i.id AND u.context_id = ?
             LEFT JOIN (
                 SELECT item_id, MAX(created_at) AS last_used
                 FROM self_events
                 WHERE context_id = ? AND counted = 1
                 GROUP BY item_id
             ) e ON e.item_id = i.id
             WHERE i.collection_id = ? AND (s.uses > 0 OR u.uses > 0)
             ORDER BY i.item_no',
            [$studentId, $studentId, $studentId, $collectionId],
        );

        return array_map(static fn(array $row): array => [
            'item_no' => (int) $row['item_no'],
            'self' => (int) $row['self_uses'],
            'lesson' => (int) $row['lesson_uses'],
            'last' => isset($row['last_used']) ? (string) $row['last_used'] : null,
        ], $rows);
    }

    /**
     * Item counters rolled up over a coach's own row and students — or, for
     * null, over every context there is — with unused items included as zero.
     *
     * Summing every row does not double count: each use increments exactly
     * one row, and a shared student's row exists once, not once per coach.
     *
     * @return array<int, int> item_no => uses
     */
    public static function itemCounts(?int $coachId, int $collectionId): array
    {
        $itemNoMap = Collections::itemNoMap($collectionId);
        if ($itemNoMap === []) {
            return [];
        }

        $itemIds = array_keys($itemNoMap);
        $itemPlaceholders = implode(',', array_fill(0, count($itemIds), '?'));

        if ($coachId === null) {
            $contextClause = '';
            $contextParams = [];
        } else {
            $rollup = Contexts::rollupIds($coachId);
            $contextClause = 'context_id IN (' . implode(',', array_fill(0, count($rollup), '?')) . ') AND ';
            $contextParams = $rollup;
        }

        $sums = [];
        foreach (Db::fetchAll(
            "SELECT item_id, SUM(uses) AS total FROM usage_counts
             WHERE {$contextClause}item_id IN ({$itemPlaceholders})
             GROUP BY item_id",
            [...$contextParams, ...$itemIds],
        ) as $row) {
            $sums[(int) $row['item_id']] = (int) $row['total'];
        }

        $counts = [];
        foreach ($itemNoMap as $itemId => $itemNo) {
            $counts[$itemNo] = $sums[$itemId] ?? 0;
        }
        ksort($counts);

        return $counts;
    }

    /**
     * @param array<int, int> $counts item_no => uses
     * @return array<int, int> the $limit highest or lowest, item_no => uses
     */
    public static function extremes(array $counts, int $limit, bool $lowest): array
    {
        if ($counts === []) {
            return [];
        }

        $sorted = $counts;
        // Ties keep their item order, which reads better than an arbitrary one.
        uksort($sorted, static function (int $a, int $b) use ($counts, $lowest): int {
            $comparison = $lowest
                ? $counts[$a] <=> $counts[$b]
                : $counts[$b] <=> $counts[$a];

            return $comparison !== 0 ? $comparison : $a <=> $b;
        });

        return array_slice($sorted, 0, $limit, true);
    }

    /** @return array{0:string, 1:list<int>} a WHERE fragment and its parameters */
    private static function coachFilter(?int $coachId, string $prefix = ''): array
    {
        return $coachId === null
            ? ['1 = 1', []]
            : ["{$prefix}coach_id = ?", [$coachId]];
    }
}
