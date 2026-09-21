<?php

declare(strict_types=1);

use App\Csrf;

/**
 * @var array      $coaches
 * @var bool       $isAdmin
 * @var array|null $preview
 * @var string     $importError
 * @var array      $messages
 */
?>
<h1><?= e(t('admin.nav.data')) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('data.export')) ?></h2>
    <p class="small muted"><?= e(t('data.export.intro')) ?></p>

    <?php if ($coaches === []): ?>
        <p class="muted"><?= e(t('coach.none')) ?></p>
    <?php else: ?>
        <form method="post" class="inline-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="export">

            <div class="field">
                <label for="export-coach"><?= e(t('coach.name')) ?></label>
                <select id="export-coach" name="coach_id" required>
                    <?php foreach ($coaches as $coach): ?>
                        <option value="<?= (int) $coach['id'] ?>"><?= e((string) $coach['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label class="checkbox">
                    <input type="checkbox" name="include_events" value="1">
                    <?= e(t('data.export.events')) ?>
                </label>
                <p class="field__hint"><?= e(t('data.export.events.hint')) ?></p>
            </div>

            <div class="button-row">
                <button type="submit" name="format" value="json"><?= e(t('data.export.json')) ?></button>
                <button type="submit" name="format" value="csv" class="button--quiet">
                    <?= e(t('data.export.csv')) ?>
                </button>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php if ($isAdmin): ?>
<section class="card">
    <h2 style="margin-top:0"><?= e(t('data.import')) ?></h2>
    <p class="small muted"><?= e(t('data.import.intro')) ?></p>

    <?php if ($importError !== ''): ?>
        <div class="notice notice--danger"><p><?= e($importError) ?></p></div>
    <?php endif; ?>

    <?php if ($preview === null): ?>
        <form method="post" enctype="multipart/form-data" class="inline-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="preview">

            <div class="field">
                <label for="import-file"><?= e(t('data.import.file')) ?></label>
                <input type="file" id="import-file" name="file" accept="application/json,.json" required>
            </div>

            <div class="field">
                <label for="import-name"><?= e(t('data.import.name')) ?></label>
                <input type="text" id="import-name" name="coach_name" maxlength="120">
                <p class="field__hint"><?= e(t('data.import.name.hint')) ?></p>
            </div>

            <div class="button-row">
                <button type="submit"><?= e(t('data.import.preview')) ?></button>
            </div>
        </form>
    <?php else: ?>
        <div class="notice">
            <p><strong><?= e(t('data.import.preview.title', ['name' => $preview['name']])) ?></strong></p>
            <ul class="preview-list">
                <li><?= e(tn('data.import.preview.students', (int) $preview['students'])) ?>
                    <?php if ($preview['students_existing'] > 0): ?>
                        <span class="muted">·
                            <?= e(t('data.import.preview.existing', ['count' => (int) $preview['students_existing']])) ?>
                        </span>
                    <?php endif; ?>
                </li>
                <li><?= e(tn('data.import.preview.counts', (int) $preview['counts'])) ?></li>
                <?php if ($preview['counts_conflicting'] > 0): ?>
                    <li class="preview-list__warn">
                        <?= e(t('data.import.preview.conflicts', ['count' => (int) $preview['counts_conflicting']])) ?>
                    </li>
                <?php endif; ?>
                <?php if ($preview['events'] > 0): ?>
                    <li><?= e(tn('data.import.preview.events', (int) $preview['events'])) ?></li>
                <?php endif; ?>
                <?php if ($preview['unknown_collections'] !== []): ?>
                    <li class="preview-list__warn">
                        <?= e(t('data.import.preview.unknown', [
                            'slugs' => implode(', ', $preview['unknown_collections']),
                        ])) ?>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <?php if ($preview['coach_exists']): ?>
            <div class="notice notice--danger">
                <p><?= e(t('data.import.name_taken', ['name' => $preview['name']])) ?></p>
            </div>
        <?php endif; ?>

        <form method="post" class="stack">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="import">

            <div class="field">
                <label for="confirm-name"><?= e(t('data.import.name')) ?></label>
                <input type="text" id="confirm-name" name="coach_name" maxlength="120"
                       value="<?= e((string) $preview['name']) ?>" required>
            </div>

            <?php if ($preview['counts_conflicting'] > 0): ?>
                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox" name="overwrite" value="1">
                        <?= e(t('data.import.overwrite')) ?>
                    </label>
                    <p class="field__hint"><?= e(t('data.import.overwrite.hint')) ?></p>
                </div>
            <?php endif; ?>

            <div class="button-row">
                <button type="submit"><?= e(t('data.import.confirm')) ?></button>
                <a class="button button--quiet" href="data.php"><?= e(t('data.import.cancel')) ?></a>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php endif; ?>
