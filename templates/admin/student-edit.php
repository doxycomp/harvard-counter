<?php

declare(strict_types=1);

use App\Csrf;

/**
 * @var array  $student
 * @var array  $assigned
 * @var array  $available
 * @var array  $messages
 * @var string $formError
 * @var bool   $canDelete
 */
$id = (int) $student['id'];
?>
<p class="small"><a href="students.php">&larr; <?= e(t('admin.nav.students')) ?></a></p>

<h1><?= e((string) $student['name']) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<?php if ($formError !== ''): ?>
    <div class="notice notice--danger"><p><?= e($formError) ?></p></div>
<?php endif; ?>

<section class="card">
    <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="field">
            <label for="name"><?= e(t('student.name')) ?></label>
            <input type="text" id="name" name="name" maxlength="120" required
                   value="<?= e((string) $student['name']) ?>">
        </div>

        <div class="field">
            <label class="checkbox">
                <input type="checkbox" name="is_active" value="1"
                    <?= (int) $student['is_active'] === 1 ? 'checked' : '' ?>>
                <?= e(t('student.active')) ?>
            </label>
            <p class="field__hint"><?= e(t('student.active.hint')) ?></p>
        </div>

        <div class="button-row">
            <button type="submit"><?= e(t('common.save')) ?></button>
        </div>
    </form>
</section>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('student.coaches')) ?></h2>

    <?php if (count($assigned) > 1): ?>
        <div class="notice"><p><?= e(t('student.shared_warning')) ?></p></div>
    <?php endif; ?>

    <?php if ($assigned === []): ?>
        <p class="muted"><?= e(t('student.coach.none')) ?></p>
    <?php else: ?>
        <table class="data-table">
            <thead>
            <tr>
                <th><?= e(t('coach.name')) ?></th>
                <th><?= e(t('student.display_name')) ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($assigned as $coach): ?>
                <tr<?= (int) $coach['link_active'] === 1 ? '' : ' class="is-inactive"' ?>>
                    <td data-label="<?= e(t('coach.name')) ?>">
                        <a href="coaches.php?id=<?= (int) $coach['id'] ?>"><?= e((string) $coach['name']) ?></a>
                    </td>
                    <td data-label="<?= e(t('student.display_name')) ?>">
                        <?= e((string) ($coach['display_name'] ?? '')) ?: '<span class="muted">—</span>' ?>
                    </td>
                    <td>
                        <div class="button-row">
                            <form method="post">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="toggle-link">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="coach_id" value="<?= (int) $coach['id'] ?>">
                                <input type="hidden" name="state" value="<?= (int) $coach['link_active'] === 1 ? '0' : '1' ?>">
                                <button type="submit" class="button--quiet">
                                    <?= e((int) $coach['link_active'] === 1 ? t('common.deactivate') : t('common.activate')) ?>
                                </button>
                            </form>
                            <form method="post" onsubmit="return confirm('<?= e(t('student.unlink.confirm')) ?>')">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="unlink">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="coach_id" value="<?= (int) $coach['id'] ?>">
                                <button type="submit" class="button--danger"><?= e(t('student.unlink')) ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($available !== []): ?>
        <h3><?= e(t('student.link.add')) ?></h3>
        <form method="post" class="inline-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="link">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="field">
                <label for="link-coach"><?= e(t('coach.name')) ?></label>
                <select id="link-coach" name="coach_id" required>
                    <?php foreach ($available as $coach): ?>
                        <option value="<?= (int) $coach['id'] ?>"><?= e((string) $coach['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="link-display"><?= e(t('student.display_name')) ?></label>
                <input type="text" id="link-display" name="display_name" maxlength="120">
                <p class="field__hint"><?= e(t('student.display_name.hint')) ?></p>
            </div>

            <div class="button-row">
                <button type="submit"><?= e(t('student.link.add')) ?></button>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('coach.section.danger')) ?></h2>
    <?php if ($canDelete): ?>
        <p class="small muted"><?= e(t('student.delete.hint')) ?></p>
        <form method="post" onsubmit="return confirm('<?= e(t('student.delete.confirm')) ?>')">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="button--danger"><?= e(t('student.delete')) ?></button>
        </form>
    <?php else: ?>
        <p class="small muted"><?= e(t('student.delete.blocked')) ?></p>
    <?php endif; ?>
</section>
