<?php

declare(strict_types=1);

/**
 * Sentence collections: what is offered, in which order, and where it came
 * from. The content itself comes from the repository — this page decides
 * whether a collection is visible and re-runs the import after a git pull.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\AdminPage;
use App\CollectionImporter;
use App\Collections;
use App\Csrf;
use App\Db;
use App\Web;

[$vars, $view] = AdminPage::start('collections', adminOnly: true);

if (Web::isPost()) {
    Csrf::verify();
    $action = Web::stringParam('action', $_POST);
    $id = AdminPage::id('id', $_POST);

    if ($action === 'import') {
        try {
            $result = (new CollectionImporter())->importAll();
            AdminPage::flash('success', t('setup.collections.imported', [
                'collections' => $result['collections'],
                'items' => $result['items'],
                'lines' => $result['lines'],
            ]));
        } catch (Throwable $e) {
            AdminPage::flash('danger', $e->getMessage());
        }
        Web::redirect('collections.php');
    }

    if ($action === 'toggle' && $id !== null) {
        Db::query(
            'UPDATE collections SET is_active = 1 - is_active, updated_at = NOW() WHERE id = ?',
            [$id],
        );
        Web::redirect('collections.php');
    }

    if ($action === 'order' && $id !== null) {
        $order = filter_var(
            (string) ($_POST['sort_order'] ?? '0'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => -999, 'max_range' => 999]],
        );
        if ($order !== false) {
            Db::query(
                'UPDATE collections SET sort_order = ?, updated_at = NOW() WHERE id = ?',
                [$order, $id],
            );
            AdminPage::flash('success', t('collection.saved'));
        }
        Web::redirect('collections.php');
    }
}

$rows = Db::fetchAll('SELECT * FROM collections ORDER BY sort_order, slug');

$collections = [];
foreach ($rows as $row) {
    $collections[] = $row + [
        'display_name' => Collections::name($row, $vars['locale']),
        'display_label' => Collections::itemLabel($row, $vars['locale']),
        'description' => Collections::description($row, $vars['locale']),
        'line_count' => (int) Db::fetchValue(
            'SELECT COUNT(*) FROM collection_lines l
             JOIN collection_items i ON i.id = l.item_id
             WHERE i.collection_id = ?',
            [(int) $row['id']],
        ),
    ];
}

echo $view->page('admin/collections', [
    'title' => t('admin.nav.collections'),
    'messages' => AdminPage::takeFlash(),
    'collections' => $collections,
    'onDisk' => array_map(basename(...), (new CollectionImporter())->discover()),
]);
