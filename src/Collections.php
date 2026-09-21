<?php

declare(strict_types=1);

namespace App;

/**
 * Reading side of the sentence collections.
 *
 * Names and item labels are stored as per-locale JSON, so "List 23" and
 * "Liste 23" come from the data rather than from the translation files — a new
 * collection brings its own wording.
 */
final class Collections
{
    /** @return list<array<string, mixed>> active collections in display order */
    public static function active(): array
    {
        return Db::fetchAll(
            'SELECT * FROM collections WHERE is_active = 1 ORDER BY sort_order, slug',
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::fetchOne('SELECT * FROM collections WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        return Db::fetchOne('SELECT * FROM collections WHERE slug = ?', [$slug]);
    }

    /**
     * The collection to start from: an explicit slug, the coach's default, or the first one.
     *
     * @return array<string, mixed>|null
     */
    public static function choose(?string $slug, ?int $defaultId): ?array
    {
        if ($slug !== null) {
            $collection = self::findBySlug($slug);
            if ($collection !== null && (int) $collection['is_active'] === 1) {
                return $collection;
            }
        }

        if ($defaultId !== null) {
            $collection = self::find($defaultId);
            if ($collection !== null && (int) $collection['is_active'] === 1) {
                return $collection;
            }
        }

        return self::active()[0] ?? null;
    }

    /** @return array<string, mixed>|null */
    public static function item(int $collectionId, int $itemNo): ?array
    {
        return Db::fetchOne(
            'SELECT * FROM collection_items WHERE collection_id = ? AND item_no = ?',
            [$collectionId, $itemNo],
        );
    }

    /** @return array<int, array{position:int, text:string}> */
    public static function lines(int $itemId): array
    {
        return Db::fetchAll(
            'SELECT position, text FROM collection_lines WHERE item_id = ? ORDER BY position',
            [$itemId],
        );
    }

    /** @return array<int, int> item id => item_no, for the whole collection */
    public static function itemNoMap(int $collectionId): array
    {
        $map = [];
        foreach (Db::fetchAll(
            'SELECT id, item_no FROM collection_items WHERE collection_id = ? ORDER BY item_no',
            [$collectionId],
        ) as $row) {
            $map[(int) $row['id']] = (int) $row['item_no'];
        }

        return $map;
    }

    /**
     * How many lines precede this item in the collection, so {global_no} can
     * be offered without assuming every item has the same number of lines.
     */
    public static function lineOffset(int $collectionId, int $itemNo): int
    {
        return (int) Db::fetchValue(
            'SELECT COUNT(*) FROM collection_lines l
             JOIN collection_items i ON i.id = l.item_id
             WHERE i.collection_id = ? AND i.item_no < ?',
            [$collectionId, $itemNo],
        );
    }

    /** @param array<string, mixed> $collection */
    public static function name(array $collection, string $locale): string
    {
        return self::localised($collection['names'] ?? null, $locale, (string) $collection['slug']);
    }

    /** @param array<string, mixed> $collection */
    public static function itemLabel(array $collection, string $locale): string
    {
        return self::localised($collection['item_labels'] ?? null, $locale, 'Item');
    }

    /** @param array<string, mixed> $collection */
    public static function description(array $collection, string $locale): ?string
    {
        $value = self::localised($collection['descriptions'] ?? null, $locale, '');

        return $value === '' ? null : $value;
    }

    /** Pick the requested locale, else English, else whatever is there. */
    private static function localised(?string $json, string $locale, string $fallback): string
    {
        if ($json === null || $json === '') {
            return $fallback;
        }

        $values = json_decode($json, true);
        if (!is_array($values) || $values === []) {
            return $fallback;
        }

        foreach ([$locale, 'en'] as $candidate) {
            if (isset($values[$candidate]) && $values[$candidate] !== '') {
                return (string) $values[$candidate];
            }
        }

        return (string) (reset($values) ?: $fallback);
    }
}
