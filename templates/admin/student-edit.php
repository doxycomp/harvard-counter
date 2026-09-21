<?php

declare(strict_types=1);

use App\Csrf;
use App\Web;

/**
 * @var array<string, mixed>       $student
 * @var list<array<string, mixed>> $assigned     for a coach account, only its own assignment
 * @var int                        $sharedCount  how many coaches the student really has
 * @var list<array<string, mixed>> $available    coaches not yet assigned (administrators only)
 * @var list<array{type:string, text:string}> $messages
 * @var string $formError
 * @var bool   $isAdmin
 * @var bool   $canEditBasics  false when a coach account looks at a shared student
 * @var bool   $canDelete
 */
$id = (int) $student['id'];
$token = $student['access_token'] ?? null;
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
    <?php if ($canEditBasics): ?>
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
    <?php else: ?>
        <p class="muted"><?= e(t('student.shared_readonly')) ?></p>
    <?php endif; ?>
</section>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('student.token')) ?></h2>
    <p class="small muted"><?= e(t('student.token.hint')) ?></p>

    <?php if ($token === null): ?>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="token-create">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit"><?= e(t('student.token.create')) ?></button>
        </form>
    <?php else: ?>
        <?php $link = Web::accessLink((string) $token); ?>
        <div class="button-row">
            <button type="button" data-copy="self" data-copy-text="<?= e($link) ?>"
                    data-copied-label="<?= e(t('result.copied')) ?>">
                <span class="mono"><?= e($link) ?></span>
                <span data-copy-status></span>
            </button>
        </div>

        <div class="button-row" style="margin-top:0.75rem">
            <form method="post" onsubmit="return confirm('<?= e(t('coach.token.confirm')) ?>')">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="token-rotate">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="button--quiet"><?= e(t('coach.token.rotate')) ?></button>
            </form>
            <form method="post" onsubmit="return confirm('<?= e(t('student.token.revoke.confirm')) ?>')">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="token-revoke">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="button--danger"><?= e(t('student.token.revoke')) ?></button>
            </form>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('student.coaches')) ?></h2>

    <?php if ($sharedCount > 1): ?>
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
                        <?php if ($isAdmin): ?>
                            <a href="coaches.php?id=<?= (int) $coach['id'] ?>"><?= e((string) $coach['name']) ?></a>
                        <?php else: ?>
                            <?= e((string) $coach['name']) ?>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= e(t('student.display_name')) ?>">
                        <form method="post" class="inline-order">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="rename-link">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="coach_id" value="<?= (int) $coach['id'] ?>">
                            <label class="visually-hidden" for="display-<?= (int) $coach['id'] ?>">
                                <?= e(t('student.display_name')) ?>
                            </label>
                            <input type="text" id="display-<?= (int) $coach['id'] ?>" name="display_name"
                                   maxlength="120" class="display-name-input"
                                   value="<?= e((string) ($coach['display_name'] ?? '')) ?>"
                                   placeholder="<?= e((string) $student['name']) ?>">
                            <button type="submit" class="button--quiet"><?= e(t('common.save')) ?></button>
                        </form>
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

    <?php if ($isAdmin && $available !== []): ?>
        <h3><?= e(t('student.link.add')) ?></h3>
        <form method="post" class="inline-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="link">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="field">
                <label for="link-coach"><?= e(t('users.coach')) ?></label>
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

<?php if ($canEditBasics): ?>
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
<?php endif; ?>
