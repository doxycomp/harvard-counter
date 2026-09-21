<?php

declare(strict_types=1);

/**
 * The counter matrix: one editable number per item for a chosen context.
 *
 * Only the context's own row is editable. A coach's total is the sum over
 * their own row plus their students and is therefore shown read-only — it is
 * derived, and making it editable would be offering to create a figure that
 * the next page load would contradict.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\AdminPage;
use App\Collections;
use App\Contexts;
use App\Csrf;
use App\Db;
use App\Web;

[$vars, $view] = AdminPage::start('counters');

const MAX_USES = 100000;

$scope = AdminPage::coachScope();

// A coach account chooses between its own row and its own students.
if ($scope === null) {
    $coaches = Contexts::coaches(false);
    $students = Contexts::students();
} else {
    $own = Contexts::find($scope);
    $coaches = $own === null ? [] : [$own];
    $students = Contexts::studentsLinkedTo($scope);
}
$collections = Collections::active();

$contextId = AdminPage::id('ctx') ?? AdminPage::id('ctx', $_POST);
$collectionId = AdminPage::id('c') ?? AdminPage::id('c', $_POST);

if ($contextId !== null && !AdminPage::mayAccessContext($contextId)) {
    AdminPage::deny($view);
}

$context = $contextId === null ? null : Contexts::find($contextId);
$collection = $collectionId === null
    ? ($collections[0] ?? null)
    : (Collections::find($collectionId) ?? ($collections[0] ?? null));

if (Web::isPost() && $context !== null && $collection !== null) {
    Csrf::verify();

    if (Web::stringParam('action', $_POST) === 'save') {
        $submitted = $_POST['uses'] ?? [];
        $itemNoMap = Collections::itemNoMap((int) $collection['id']);
        $byItemNo = array_flip($itemNoMap);

        $changed = 0;

        Db::transaction(static function () use ($submitted, $byItemNo, $context, &$changed): void {
            foreach ((array) $submitted as $itemNo => $raw) {
                $itemId = $byItemNo[(int) $itemNo] ?? null;
                if ($itemId === null) {
                    continue;
                }

                $uses = filter_var((string) $raw, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 0, 'max_range' => MAX_USES],
                ]);
                if ($uses === false) {
                    continue;
                }

                $current = (int) Db::fetchValue(
                    'SELECT uses FROM usage_counts WHERE context_id = ? AND item_id = ?',
                    [(int) $context['id'], $itemId],
                );
                if ($current === $uses) {
                    continue;
                }

                $changed++;

                // Rows are created lazily by the frontend, so a zero goes back
                // to not existing rather than sitting there as a zero.
                if ($uses === 0) {
                    Db::query(
                        'DELETE FROM usage_counts WHERE context_id = ? AND item_id = ?',
                        [(int) $context['id'], $itemId],
                    );
                    continue;
                }

                Db::query(
                    'INSERT INTO usage_counts (context_id, item_id, uses, updated_at)
                     VALUES (?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE uses = VALUES(uses), updated_at = NOW()',
                    [(int) $context['id'], $itemId, $uses],
                );
            }
        });

        AdminPage::flash('success', tn('counters.saved', $changed));
        Web::redirect('counters.php?ctx=' . (int) $context['id'] . '&c=' . (int) $collection['id']);
    }
}

$rows = [];

if ($context !== null && $collection !== null) {
    $itemNoMap = Collections::itemNoMap((int) $collection['id']);
    $itemIds = array_keys($itemNoMap);

    $own = [];
    if ($itemIds !== []) {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        foreach (Db::fetchAll(
            "SELECT item_id, uses FROM usage_counts
             WHERE context_id = ? AND item_id IN ({$placeholders})",
            [(int) $context['id'], ...$itemIds],
        ) as $row) {
            $own[(int) $row['item_id']] = (int) $row['uses'];
        }
    }

    $totals = [];
    if ($context['kind'] === Contexts::COACH) {
        $rollup = Contexts::rollupIds((int) $context['id']);
        $ctxPlaceholders = implode(',', array_fill(0, count($rollup), '?'));
        $itemPlaceholders = implode(',', array_fill(0, count($itemIds), '?'));
        foreach (Db::fetchAll(
            "SELECT item_id, SUM(uses) AS total FROM usage_counts
             WHERE context_id IN ({$ctxPlaceholders}) AND item_id IN ({$itemPlaceholders})
             GROUP BY item_id",
            [...$rollup, ...$itemIds],
        ) as $row) {
            $totals[(int) $row['item_id']] = (int) $row['total'];
        }
    }

    $isCoach = $context['kind'] === Contexts::COACH;

    foreach ($itemNoMap as $itemId => $itemNo) {
        $rows[] = [
            'item_no' => $itemNo,
            'uses' => $own[$itemId] ?? 0,
            // Coaches get the roll-up on every cell, including the zeros —
            // a column that appears on some rows and not others is harder to
            // read than one that is simply always there.
            'total' => $isCoach ? ($totals[$itemId] ?? 0) : null,
        ];
    }
}

echo $view->page('admin/counters', [
    'title' => t('admin.nav.counters'),
    'messages' => AdminPage::takeFlash(),
    'coaches' => $coaches,
    'students' => $students,
    'collections' => $collections,
    'context' => $context,
    'collection' => $collection,
    'rows' => $rows,
    'itemLabel' => $collection === null ? '' : Collections::itemLabel($collection, $vars['locale']),
    'isCoach' => $context !== null && $context['kind'] === Contexts::COACH,
]);
