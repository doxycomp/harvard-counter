<?php

declare(strict_types=1);

namespace App;

/**
 * Counting for a student practising on their own, through their own link.
 *
 * Kept in self_counts / self_events rather than the lesson tables, so homework
 * never changes what "least used" or a coach's statistics say about lessons.
 */
final class SelfCounterStore implements CounterStore
{
    public function __construct(
        private readonly int $studentId,
    ) {}

    /** Self-practice figure for one student and item, for display elsewhere. */
    public static function usesFor(int $studentId, int $itemId): int
    {
        return (int) Db::fetchValue(
            'SELECT uses FROM self_counts WHERE context_id = ? AND item_id = ?',
            [$studentId, $itemId],
        );
    }

    public function uses(int $itemId): int
    {
        return self::usesFor($this->studentId, $itemId);
    }

    public function total(int $itemId): int
    {
        return $this->uses($itemId);
    }

    public function allUses(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $counts = [];

        foreach (Db::fetchAll(
            "SELECT item_id, uses FROM self_counts
             WHERE context_id = ? AND item_id IN ({$placeholders})",
            [$this->studentId, ...$itemIds],
        ) as $row) {
            $counts[(int) $row['item_id']] = (int) $row['uses'];
        }

        return $counts;
    }

    public function allTotals(array $itemIds): array
    {
        return $this->allUses($itemIds);
    }

    public function increment(int $itemId): string
    {
        return Db::transaction(function () use ($itemId): string {
            Db::query(
                'INSERT INTO self_counts (context_id, item_id, uses, updated_at)
                 VALUES (?, ?, 1, NOW())
                 ON DUPLICATE KEY UPDATE uses = uses + 1, updated_at = NOW()',
                [$this->studentId, $itemId],
            );

            return (string) Db::insert(
                'INSERT INTO self_events (context_id, item_id, counted, created_at)
                 VALUES (?, ?, 1, NOW())',
                [$this->studentId, $itemId],
            );
        });
    }

    public function undo(string $handle): bool
    {
        $eventId = filter_var($handle, FILTER_VALIDATE_INT);
        if ($eventId === false) {
            return false;
        }

        return (bool) Db::transaction(function () use ($eventId): bool {
            // Only this student's own, still-counted event.
            $event = Db::fetchOne(
                'SELECT id, item_id FROM self_events
                 WHERE id = ? AND context_id = ? AND counted = 1',
                [$eventId, $this->studentId],
            );

            if ($event === null) {
                return false;
            }

            Db::query(
                'UPDATE self_counts SET uses = GREATEST(uses, 1) - 1, updated_at = NOW()
                 WHERE context_id = ? AND item_id = ?',
                [$this->studentId, (int) $event['item_id']],
            );
            Db::query('UPDATE self_events SET counted = 0 WHERE id = ?', [$eventId]);

            return true;
        });
    }

    public function isPersistent(): bool
    {
        return true;
    }
}
