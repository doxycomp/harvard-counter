<?php

declare(strict_types=1);

namespace App;

/**
 * Counting for a visitor holding a coach token.
 *
 * Exactly one row is incremented per use — the selected context. The coach's
 * total is summed at query time across their own row and their students, so
 * editing a single counter by hand in the admin area can never put the two out
 * of step.
 */
final class DbCounterStore implements CounterStore
{
    /**
     * @param int   $coachId    who is teaching; recorded on the event
     * @param int   $contextId  what gets counted: the coach's own row or a student
     * @param int[] $rollupIds  the coach plus their students
     */
    public function __construct(
        private readonly int $coachId,
        private readonly int $contextId,
        private readonly array $rollupIds,
    ) {
    }

    public function uses(int $itemId): int
    {
        return (int) Db::fetchValue(
            'SELECT uses FROM usage_counts WHERE context_id = ? AND item_id = ?',
            [$this->contextId, $itemId],
        );
    }

    public function total(int $itemId): int
    {
        if ($this->rollupIds === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($this->rollupIds), '?'));

        return (int) Db::fetchValue(
            "SELECT COALESCE(SUM(uses), 0) FROM usage_counts
             WHERE item_id = ? AND context_id IN ({$placeholders})",
            [$itemId, ...$this->rollupIds],
        );
    }

    public function allUses(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $rows = Db::fetchAll(
            "SELECT item_id, uses FROM usage_counts
             WHERE context_id = ? AND item_id IN ({$placeholders})",
            [$this->contextId, ...$itemIds],
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['item_id']] = (int) $row['uses'];
        }

        return $counts;
    }

    public function increment(int $itemId): ?string
    {
        return Db::transaction(function () use ($itemId): string {
            Db::query(
                'INSERT INTO usage_counts (context_id, item_id, uses, updated_at)
                 VALUES (?, ?, 1, NOW())
                 ON DUPLICATE KEY UPDATE uses = uses + 1, updated_at = NOW()',
                [$this->contextId, $itemId],
            );

            $eventId = Db::insert(
                'INSERT INTO usage_events (coach_id, context_id, item_id, counted, created_at)
                 VALUES (?, ?, ?, 1, NOW())',
                [$this->coachId, $this->contextId, $itemId],
            );

            return (string) $eventId;
        });
    }

    public function undo(string $handle): bool
    {
        $eventId = filter_var($handle, FILTER_VALIDATE_INT);
        if ($eventId === false) {
            return false;
        }

        return (bool) Db::transaction(function () use ($eventId): bool {
            // Only an event that is still counted, and only one belonging to
            // this visitor's coach — a handle from someone else's session must
            // not decrement anything.
            $event = Db::fetchOne(
                'SELECT id, context_id, item_id FROM usage_events
                 WHERE id = ? AND coach_id = ? AND counted = 1',
                [$eventId, $this->coachId],
            );

            if ($event === null) {
                return false;
            }

            Db::query(
                'UPDATE usage_counts SET uses = GREATEST(uses, 1) - 1, updated_at = NOW()
                 WHERE context_id = ? AND item_id = ?',
                [(int) $event['context_id'], (int) $event['item_id']],
            );
            Db::query('UPDATE usage_events SET counted = 0 WHERE id = ?', [$eventId]);

            return true;
        });
    }

    public function isPersistent(): bool
    {
        return true;
    }
}
