<?php

declare(strict_types=1);

namespace App;

/**
 * Where a use gets counted.
 *
 * Two implementations: one writing to the database for a visitor holding a
 * coach token, one keeping the numbers in the PHP session for the demo mode.
 * The frontend talks only to this interface, so the demo path never becomes a
 * second code path through the page.
 */
interface CounterStore
{
    /** Uses recorded on the selected context row. */
    public function uses(int $itemId): int;

    /**
     * The roll-up the coach sees: their own row plus every assigned student.
     * Equal to uses() in demo mode, where there is nothing to roll up.
     */
    public function total(int $itemId): int;

    /**
     * @param int[] $itemIds
     * @return array<int, int> item id => uses, for the selected context row
     */
    public function allUses(array $itemIds): array;

    /**
     * Like allUses(), but rolled up the way total() is: the coach's own row
     * plus every assigned student. Equal to allUses() in demo mode.
     *
     * @param int[] $itemIds
     * @return array<int, int> item id => uses
     */
    public function allTotals(array $itemIds): array;

    /**
     * Record one use.
     *
     * @return string a handle for undo()
     */
    public function increment(int $itemId): string;

    /** Reverse an increment. Returns false when the handle is unknown or spent. */
    public function undo(string $handle): bool;

    /** Whether counts survive beyond this session. */
    public function isPersistent(): bool;
}
