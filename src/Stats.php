<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/**
 * Statistics for the admin area.
 *
 * Everything here reads usage_events rather than usage_counts, because the
 * event log is the part that stays attributed to the teaching coach. The
 * counter itself is shared between a student's coaches, so it cannot answer
 * "how much of this was mine".
 */
final class Stats
{
    /**
     * @return array{total:int, undone:int, last:?string, students:int}
     */
    public static function summaryForCoach(int $coachId): array
    {
        $row = Db::fetchOne(
            'SELECT
                COALESCE(SUM(counted = 1), 0) AS total,
                COALESCE(SUM(counted = 0), 0) AS undone,
                MAX(CASE WHEN counted = 1 THEN created_at END) AS last_used
             FROM usage_events WHERE coach_id = ?',
            [$coachId],
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'undone' => (int) ($row['undone'] ?? 0),
            'last' => $row['last_used'] ?? null,
            'students' => Contexts::studentCount($coachId),
        ];
    }

    /**
     * Uses per month for the last $months months, oldest first and with empty
     * months present, so the bar chart does not silently skip a quiet period.
     *
     * @return array<string, int> 'YYYY-MM' => count
     */
    public static function monthly(int $coachId, int $months = 12): array
    {
        $buckets = [];
        $cursor = new DateTimeImmutable('first day of this month 00:00:00');
        for ($i = $months - 1; $i >= 0; $i--) {
            $buckets[$cursor->modify("-{$i} months")->format('Y-m')] = 0;
        }

        $rows = Db::fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS bucket, COUNT(*) AS uses
             FROM usage_events
             WHERE coach_id = ? AND counted = 1
               AND created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL ? MONTH)
             GROUP BY bucket",
            [$coachId, $months - 1],
        );

        foreach ($rows as $row) {
            if (array_key_exists($row['bucket'], $buckets)) {
                $buckets[$row['bucket']] = (int) $row['uses'];
            }
        }

        return $buckets;
    }

    /**
     * How the coach's own sessions split across their students.
     *
     * @return array<int, array{name:string, uses:int}>
     */
    public static function byStudent(int $coachId): array
    {
        $rows = Db::fetchAll(
            'SELECT e.context_id, c.name, c.kind, COUNT(*) AS uses
             FROM usage_events e
             JOIN contexts c ON c.id = e.context_id
             WHERE e.coach_id = ? AND e.counted = 1
             GROUP BY e.context_id, c.name, c.kind
             ORDER BY uses DESC, c.name',
            [$coachId],
        );

        return array_map(static fn (array $row): array => [
            'name' => (string) $row['name'],
            'kind' => (string) $row['kind'],
            'uses' => (int) $row['uses'],
        ], $rows);
    }

    /**
     * Item counters for a coach, rolled up over their own row and students,
     * with unused items included as zero.
     *
     * @return array<int, int> item_no => uses
     */
    public static function itemCounts(int $coachId, int $collectionId): array
    {
        $itemNoMap = Collections::itemNoMap($collectionId);
        if ($itemNoMap === []) {
            return [];
        }

        $rollup = Contexts::rollupIds($coachId);
        $ctxPlaceholders = implode(',', array_fill(0, count($rollup), '?'));
        $itemIds = array_keys($itemNoMap);
        $itemPlaceholders = implode(',', array_fill(0, count($itemIds), '?'));

        $sums = [];
        foreach (Db::fetchAll(
            "SELECT item_id, SUM(uses) AS total FROM usage_counts
             WHERE context_id IN ({$ctxPlaceholders}) AND item_id IN ({$itemPlaceholders})
             GROUP BY item_id",
            [...$rollup, ...$itemIds],
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
}
