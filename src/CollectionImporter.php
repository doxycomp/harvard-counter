<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Imports sentence collections from data/collections/<slug>/.
 *
 * The import is idempotent and, crucially, id-preserving: existing items keep
 * their primary key so that usage_counts and usage_events survive a re-import.
 * Only items that disappeared from the CSV are removed.
 */
final class CollectionImporter
{
    public function __construct(
        private readonly string $directory = APP_ROOT . '/data/collections',
    ) {
    }

    /** @return string[] absolute paths of the collection directories on disk */
    public function discover(): array
    {
        $found = glob($this->directory . '/*/meta.json') ?: [];
        sort($found, SORT_STRING);

        return array_map(dirname(...), $found);
    }

    /**
     * Import every collection found on disk.
     *
     * @return array{collections:int, items:int, lines:int, slugs:string[]}
     */
    public function importAll(): array
    {
        $totals = ['collections' => 0, 'items' => 0, 'lines' => 0, 'slugs' => []];

        foreach ($this->discover() as $path) {
            $result = $this->import($path);
            $totals['collections']++;
            $totals['items'] += $result['items'];
            $totals['lines'] += $result['lines'];
            $totals['slugs'][] = $result['slug'];
        }

        return $totals;
    }

    /**
     * @return array{slug:string, items:int, lines:int}
     */
    public function import(string $path): array
    {
        $meta = $this->readMeta($path);
        $items = $this->readItems($path . '/' . ($meta['items_file'] ?? 'items.csv'));
        $this->validate($meta, $items);

        return Db::transaction(function () use ($meta, $items): array {
            $collectionId = $this->upsertCollection($meta, count($items));
            $lineCount = $this->syncItems($collectionId, $items);

            return [
                'slug' => $meta['slug'],
                'items' => count($items),
                'lines' => $lineCount,
            ];
        });
    }

    private function readMeta(string $path): array
    {
        $file = $path . '/meta.json';
        $raw = file_get_contents($file);
        if ($raw === false) {
            throw new RuntimeException("Cannot read {$file}");
        }

        $meta = json_decode($raw, true);
        if (!is_array($meta)) {
            throw new RuntimeException("Invalid JSON in {$file}");
        }

        foreach (['slug', 'names', 'item_labels'] as $required) {
            if (!isset($meta[$required])) {
                throw new RuntimeException("Missing \"{$required}\" in {$file}");
            }
        }

        if (preg_match('/^[a-z0-9][a-z0-9-]{1,62}$/', (string) $meta['slug']) !== 1) {
            throw new RuntimeException("Invalid slug in {$file}: {$meta['slug']}");
        }

        return $meta;
    }

    /**
     * @return array<int, string[]> item_no => [position => text], both 1-based
     */
    private function readItems(string $file): array
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Cannot read {$file}");
        }

        $items = [];
        $lineNumber = 0;

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $lineNumber++;

            // Skip the header and blank lines.
            if ($lineNumber === 1 && ($row[0] ?? '') === 'item_no') {
                continue;
            }
            if ($row === [null] || $row === []) {
                continue;
            }

            if (count($row) < 3) {
                fclose($handle);
                throw new RuntimeException("{$file}:{$lineNumber} expected 3 columns");
            }

            $itemNo = (int) $row[0];
            $position = (int) $row[1];
            $text = trim((string) $row[2]);

            if ($itemNo < 1 || $position < 1 || $text === '') {
                fclose($handle);
                throw new RuntimeException("{$file}:{$lineNumber} invalid row");
            }
            if (isset($items[$itemNo][$position])) {
                fclose($handle);
                throw new RuntimeException("{$file}:{$lineNumber} duplicate item {$itemNo} position {$position}");
            }

            $items[$itemNo][$position] = $text;
        }

        fclose($handle);
        ksort($items);
        foreach ($items as &$lines) {
            ksort($lines);
        }
        unset($lines);

        return $items;
    }

    /** @param array<int, string[]> $items */
    private function validate(array $meta, array $items): void
    {
        if ($items === []) {
            throw new RuntimeException("Collection {$meta['slug']} has no items");
        }

        $errors = [];

        // Positions must be contiguous and start at 1, or {n} in a Discord
        // template would silently skip numbers.
        foreach ($items as $itemNo => $lines) {
            $expected = range(1, count($lines));
            if (array_keys($lines) !== $expected) {
                $errors[] = "item {$itemNo} has non-contiguous positions";
            }
        }

        $expect = $meta['expect'] ?? null;
        if (is_array($expect)) {
            if (isset($expect['items']) && count($items) !== (int) $expect['items']) {
                $errors[] = sprintf(
                    'expected %d items, found %d',
                    (int) $expect['items'],
                    count($items),
                );
            }
            if (isset($expect['lines_per_item'])) {
                foreach ($items as $itemNo => $lines) {
                    if (count($lines) !== (int) $expect['lines_per_item']) {
                        $errors[] = sprintf(
                            'item %d has %d lines, expected %d',
                            $itemNo,
                            count($lines),
                            (int) $expect['lines_per_item'],
                        );
                    }
                }
            }
        }

        if ($errors !== []) {
            throw new RuntimeException(
                "Collection {$meta['slug']} failed validation:\n - " . implode("\n - ", $errors),
            );
        }
    }

    private function upsertCollection(array $meta, int $itemCount): int
    {
        $existing = Db::fetchValue('SELECT id FROM collections WHERE slug = ?', [$meta['slug']]);

        $values = [
            'content_lang' => (string) ($meta['content_lang'] ?? 'en'),
            'names' => $this->encode($meta['names']),
            'item_labels' => $this->encode($meta['item_labels']),
            'descriptions' => isset($meta['descriptions']) ? $this->encode($meta['descriptions']) : null,
            'source_url' => $meta['source_url'] ?? null,
            'attribution' => $meta['attribution'] ?? null,
            'license_note' => $meta['license_note'] ?? null,
            'item_count' => $itemCount,
            'sort_order' => (int) ($meta['sort_order'] ?? 0),
        ];

        // Columns are derived from the keys rather than written out a second
        // time, so the statement and its parameters cannot drift apart.
        if ($existing !== null) {
            $assignments = implode(', ', array_map(
                static fn (string $column): string => "{$column} = ?",
                array_keys($values),
            ));

            // is_active is deliberately not overwritten: whether a collection
            // is offered is an operator decision, not a property of the file.
            // Anything outside 0/1 is repaired, though.
            Db::query(
                "UPDATE collections SET {$assignments}, updated_at = NOW(),
                    is_active = CASE WHEN is_active IN (0, 1) THEN is_active ELSE 1 END
                 WHERE id = ?",
                [...array_values($values), (int) $existing],
            );

            return (int) $existing;
        }

        $row = [
            'slug' => $meta['slug'],
            'is_active' => ($meta['is_active'] ?? true) ? 1 : 0,
            ...$values,
        ];

        $columns = implode(', ', array_keys($row));
        $placeholders = implode(', ', array_fill(0, count($row), '?'));

        return Db::insert(
            "INSERT INTO collections ({$columns}, created_at, updated_at)
             VALUES ({$placeholders}, NOW(), NOW())",
            array_values($row),
        );
    }

    /**
     * @param array<int, string[]> $items
     * @return int number of lines written or kept
     */
    private function syncItems(int $collectionId, array $items): int
    {
        $existing = [];
        foreach (Db::fetchAll(
            'SELECT id, item_no FROM collection_items WHERE collection_id = ?',
            [$collectionId],
        ) as $row) {
            $existing[(int) $row['item_no']] = (int) $row['id'];
        }

        $lineCount = 0;

        foreach ($items as $itemNo => $lines) {
            $itemId = $existing[$itemNo] ?? null;
            if ($itemId === null) {
                $itemId = Db::insert(
                    'INSERT INTO collection_items (collection_id, item_no) VALUES (?, ?)',
                    [$collectionId, $itemNo],
                );
            }
            unset($existing[$itemNo]);

            $this->syncLines($itemId, $lines);
            $lineCount += count($lines);
        }

        // Whatever is left was removed from the CSV.
        foreach ($existing as $itemId) {
            Db::query('DELETE FROM collection_items WHERE id = ?', [$itemId]);
        }

        return $lineCount;
    }

    /** @param string[] $lines position => text */
    private function syncLines(int $itemId, array $lines): void
    {
        $current = [];
        foreach (Db::fetchAll(
            'SELECT id, position, text FROM collection_lines WHERE item_id = ?',
            [$itemId],
        ) as $row) {
            $current[(int) $row['position']] = ['id' => (int) $row['id'], 'text' => $row['text']];
        }

        foreach ($lines as $position => $text) {
            if (!isset($current[$position])) {
                Db::query(
                    'INSERT INTO collection_lines (item_id, position, text) VALUES (?, ?, ?)',
                    [$itemId, $position, $text],
                );
                continue;
            }

            if ($current[$position]['text'] !== $text) {
                Db::query(
                    'UPDATE collection_lines SET text = ? WHERE id = ?',
                    [$text, $current[$position]['id']],
                );
            }
            unset($current[$position]);
        }

        foreach ($current as $stale) {
            Db::query('DELETE FROM collection_lines WHERE id = ?', [$stale['id']]);
        }
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
