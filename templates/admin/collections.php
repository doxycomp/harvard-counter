<?php

declare(strict_types=1);

use App\Csrf;

/**
 * @var array    $collections
 * @var string[] $onDisk
 * @var array    $messages
 */
?>
<h1><?= e(t('admin.nav.collections')) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<section class="card">
    <p class="small muted"><?= e(t('collection.intro')) ?></p>
    <p class="small mono muted"><?= e(implode(', ', $onDisk)) ?></p>

    <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="import">
        <button type="submit"><?= e(t('setup.collections.import')) ?></button>
    </form>
</section>

<?php foreach ($collections as $collection): ?>
    <section class="card<?= (int) $collection['is_active'] === 1 ? '' : ' is-inactive' ?>">
        <h2 style="margin-top:0">
            <?= e((string) $collection['display_name']) ?>
            <?php if ((int) $collection['is_active'] !== 1): ?>
                <span class="badge"><?= e(t('common.inactive')) ?></span>
            <?php endif; ?>
        </h2>

        <p class="small mono muted"><?= e((string) $collection['slug']) ?> · <?= e((string) $collection['content_lang']) ?></p>

        <?php if ($collection['description'] !== null): ?>
            <p><?= e((string) $collection['description']) ?></p>
        <?php endif; ?>

        <p class="small muted">
            <?= e(t('collection.size', [
                'items' => (int) $collection['item_count'],
                'label' => (string) $collection['display_label'],
                'lines' => (int) $collection['line_count'],
            ])) ?>
        </p>

        <?php if (($collection['attribution'] ?? '') !== ''): ?>
            <p class="small muted"><?= e((string) $collection['attribution']) ?></p>
        <?php endif; ?>
        <?php if (($collection['license_note'] ?? '') !== ''): ?>
            <p class="small muted"><strong><?= e(t('collection.license')) ?>:</strong>
                <?= e((string) $collection['license_note']) ?></p>
        <?php endif; ?>
        <?php if (($collection['source_url'] ?? '') !== ''): ?>
            <p class="small muted"><a href="<?= e((string) $collection['source_url']) ?>"
                                      rel="noopener noreferrer"><?= e(t('collection.source')) ?></a></p>
        <?php endif; ?>

        <div class="button-row">
            <form method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $collection['id'] ?>">
                <button type="submit" class="button--quiet">
                    <?= e((int) $collection['is_active'] === 1 ? t('common.deactivate') : t('common.activate')) ?>
                </button>
            </form>

            <form method="post" class="inline-order">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="order">
                <input type="hidden" name="id" value="<?= (int) $collection['id'] ?>">
                <label class="visually-hidden" for="order-<?= (int) $collection['id'] ?>">
                    <?= e(t('collection.order')) ?>
                </label>
                <input type="number" id="order-<?= (int) $collection['id'] ?>" name="sort_order"
                       value="<?= (int) $collection['sort_order'] ?>" min="-999" max="999">
                <button type="submit" class="button--quiet"><?= e(t('collection.order.save')) ?></button>
            </form>
        </div>
    </section>
<?php endforeach; ?>
