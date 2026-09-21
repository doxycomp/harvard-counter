<?php

declare(strict_types=1);

use App\Auth;
use App\Csrf;
use App\I18n;

/**
 * @var list<array<string, mixed>> $users
 * @var list<array<string, mixed>> $coaches
 * @var int|null $currentId
 * @var string   $formError
 * @var array{username:string, role:string, coach_id:int|null} $form
 * @var list<array{type:string, text:string}> $messages
 */
?>
<h1><?= e(t('admin.nav.users')) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('users.add')) ?></h2>
    <p class="small muted"><?= e(t('users.intro')) ?></p>

    <?php if ($formError !== ''): ?>
        <div class="notice notice--danger"><p><?= e($formError) ?></p></div>
    <?php endif; ?>

    <form method="post" class="stack">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="create">

        <div class="grid-2">
            <div class="field">
                <label for="u-name"><?= e(t('setup.admin.username')) ?></label>
                <input type="text" id="u-name" name="username" maxlength="64" required
                       autocomplete="off" value="<?= e($form['username']) ?>">
            </div>

            <div class="field">
                <label for="u-role"><?= e(t('users.role')) ?></label>
                <select id="u-role" name="role">
                    <option value="<?= e(Auth::ROLE_COACH) ?>"<?= $form['role'] === Auth::ROLE_COACH ? ' selected' : '' ?>>
                        <?= e(t('users.role.coach')) ?>
                    </option>
                    <option value="<?= e(Auth::ROLE_ADMIN) ?>"<?= $form['role'] === Auth::ROLE_ADMIN ? ' selected' : '' ?>>
                        <?= e(t('users.role.admin')) ?>
                    </option>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="u-coach"><?= e(t('users.coach')) ?></label>
            <select id="u-coach" name="coach_id">
                <option value=""><?= e(t('users.coach.none')) ?></option>
                <?php foreach ($coaches as $coach): ?>
                    <option value="<?= (int) $coach['id'] ?>"
                        <?= $form['coach_id'] === (int) $coach['id'] ? 'selected' : '' ?>>
                        <?= e((string) $coach['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="field__hint"><?= e(t('users.coach.hint')) ?></p>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="u-pw"><?= e(t('setup.admin.password')) ?></label>
                <input type="password" id="u-pw" name="password" autocomplete="new-password"
                       required minlength="<?= Auth::minPasswordLength() ?>">
                <p class="field__hint"><?= e(t('setup.admin.password_hint')) ?></p>
            </div>
            <div class="field">
                <label for="u-pw2"><?= e(t('setup.admin.password_repeat')) ?></label>
                <input type="password" id="u-pw2" name="password_repeat" autocomplete="new-password"
                       required minlength="<?= Auth::minPasswordLength() ?>">
            </div>
        </div>

        <div class="button-row">
            <button type="submit"><?= e(t('users.add')) ?></button>
        </div>
    </form>
</section>

<div class="card">
    <table class="data-table">
        <thead>
        <tr>
            <th><?= e(t('setup.admin.username')) ?></th>
            <th><?= e(t('users.role')) ?></th>
            <th><?= e(t('users.last_login')) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td data-label="<?= e(t('setup.admin.username')) ?>">
                    <?= e((string) $user['username']) ?>
                    <?php if ((int) $user['id'] === $currentId): ?>
                        <span class="badge"><?= e(t('users.you')) ?></span>
                    <?php endif; ?>
                </td>
                <td data-label="<?= e(t('users.role')) ?>">
                    <?php if ($user['role'] === Auth::ROLE_ADMIN): ?>
                        <?= e(t('users.role.admin')) ?>
                    <?php else: ?>
                        <?= e(t('users.role.coach')) ?>:
                        <strong><?= e((string) ($user['coach_name'] ?? '—')) ?></strong>
                    <?php endif; ?>
                </td>
                <td data-label="<?= e(t('users.last_login')) ?>" class="small muted">
                    <?= $user['last_login_at'] === null
                        ? e(t('users.never'))
                        : e(I18n::date(new DateTimeImmutable((string) $user['last_login_at']), true)) ?>
                </td>
                <td>
                    <?php if ((int) $user['id'] === $currentId): ?>
                        <?php // Your own password goes through the page that asks for the current one. ?>
                        <a class="button button--quiet" href="account.php"><?= e(t('users.password.own')) ?></a>
                    <?php else: ?>
                    <details>
                        <summary class="small"><?= e(t('users.password.reset')) ?></summary>
                        <form method="post" class="stack" style="margin-top:0.5rem">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="password">
                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                            <label class="visually-hidden" for="pw-<?= (int) $user['id'] ?>"><?= e(t('account.password.new')) ?></label>
                            <input type="password" id="pw-<?= (int) $user['id'] ?>" name="password"
                                   autocomplete="new-password" placeholder="<?= e(t('account.password.new')) ?>"
                                   required minlength="<?= Auth::minPasswordLength() ?>">
                            <label class="visually-hidden" for="pw2-<?= (int) $user['id'] ?>"><?= e(t('setup.admin.password_repeat')) ?></label>
                            <input type="password" id="pw2-<?= (int) $user['id'] ?>" name="password_repeat"
                                   autocomplete="new-password" placeholder="<?= e(t('setup.admin.password_repeat')) ?>"
                                   required minlength="<?= Auth::minPasswordLength() ?>">
                            <button type="submit" class="button--quiet"><?= e(t('common.save')) ?></button>
                        </form>
                    </details>
                    <?php endif; ?>
                    <?php if ((int) $user['id'] !== $currentId): ?>
                        <form method="post" onsubmit="return confirm('<?= e(t('users.delete.confirm')) ?>')">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                            <button type="submit" class="button--danger"><?= e(t('users.delete')) ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
