<?php

declare(strict_types=1);

use App\Csrf;
use App\I18n;

/**
 * @var array  $coaches
 * @var array  $messages
 * @var string $formError
 */
?>
<h1><?= e(t('admin.nav.coaches')) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('coach.add')) ?></h2>

    <?php if ($formError !== ''): ?>
        <div class="notice notice--danger"><p><?= e($formError) ?></p></div>
    <?php endif; ?>

    <form method="post" class="inline-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="create">

        <div class="field">
            <label for="new-name"><?= e(t('coach.name')) ?></label>
            <input type="text" id="new-name" name="name" maxlength="120" required>
        </div>

        <div class="field">
            <label for="new-locale"><?= e(t('coach.locale')) ?></label>
            <select id="new-locale" name="locale">
                <option value=""><?= e(t('coach.locale.inherit')) ?></option>
                <?php foreach (I18n::LOCALES as $option): ?>
                    <option value="<?= e($option) ?>"><?= e(I18n::LOCALE_NAMES[$option]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="button-row">
            <button type="submit"><?= e(t('coach.add')) ?></button>
        </div>
    </form>
</section>

<?php if ($coaches === []): ?>
    <div class="card"><p class="muted"><?= e(t('coach.none')) ?></p></div>
<?php else: ?>
    <div class="card">
        <table class="data-table">
            <thead>
            <tr>
                <th><?= e(t('coach.name')) ?></th>
                <th><?= e(t('coach.students')) ?></th>
                <th><?= e(t('coach.link')) ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($coaches as $coach): ?>
                <tr<?= (int) $coach['is_active'] === 1 ? '' : ' class="is-inactive"' ?>>
                    <td data-label="<?= e(t('coach.name')) ?>">
                        <a href="coaches.php?id=<?= (int) $coach['id'] ?>"><?= e((string) $coach['name']) ?></a>
                        <?php if ((int) $coach['is_active'] !== 1): ?>
                            <span class="badge"><?= e(t('common.inactive')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= e(t('coach.students')) ?>"><?= (int) $coach['student_count'] ?></td>
                    <td data-label="<?= e(t('coach.link')) ?>">
                        <?php $link = '/?t=' . (string) $coach['access_token']; ?>
                        <button type="button" class="button--quiet token-button"
                                data-copy="self" data-copy-text="<?= e($link) ?>"
                                data-copied-label="<?= e(t('result.copied')) ?>">
                            <span class="mono"><?= e(substr((string) $coach['access_token'], 0, 6)) ?>…</span>
                            <span data-copy-status><?= e(t('coach.link.copy')) ?></span>
                        </button>
                    </td>
                    <td>
                        <a class="button button--quiet" href="coaches.php?id=<?= (int) $coach['id'] ?>">
                            <?= e(t('common.edit')) ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
