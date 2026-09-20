<?php

declare(strict_types=1);

namespace App;

/**
 * Counting for a visitor without an access token.
 *
 * The numbers start at zero and live in the PHP session, so trying the
 * application out neither touches anybody's real counters nor reveals them.
 */
final class SessionCounterStore implements CounterStore
{
    private const KEY = 'demo_counts';

    public function uses(int $itemId): int
    {
        return (int) (Session::get(self::KEY, [])[$itemId] ?? 0);
    }

    public function total(int $itemId): int
    {
        return $this->uses($itemId);
    }

    public function allUses(array $itemIds): array
    {
        $counts = [];
        foreach (Session::get(self::KEY, []) as $itemId => $uses) {
            if (in_array((int) $itemId, $itemIds, true) && (int) $uses > 0) {
                $counts[(int) $itemId] = (int) $uses;
            }
        }

        return $counts;
    }

    public function increment(int $itemId): ?string
    {
        $counts = Session::get(self::KEY, []);
        $counts[$itemId] = ($counts[$itemId] ?? 0) + 1;
        Session::set(self::KEY, $counts);

        // The handle carries the item, since there is no event row to point at.
        return 'demo:' . $itemId;
    }

    public function undo(string $handle): bool
    {
        if (!str_starts_with($handle, 'demo:')) {
            return false;
        }

        $itemId = (int) substr($handle, 5);
        $counts = Session::get(self::KEY, []);
        if (($counts[$itemId] ?? 0) < 1) {
            return false;
        }

        $counts[$itemId]--;
        if ($counts[$itemId] === 0) {
            unset($counts[$itemId]);
        }
        Session::set(self::KEY, $counts);

        return true;
    }

    public function isPersistent(): bool
    {
        return false;
    }
}
