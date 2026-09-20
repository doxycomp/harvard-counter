<?php

declare(strict_types=1);

use App\Auth;
use App\Csrf;
use App\I18n;

/**
 * @var array  $admin
 * @var string $formError
 * @var array  $messages
 */
?>
<h1><?= e(t('admin.nav.account')) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<section class="card">
    <p class="muted">
        <?= e(t('admin.dashboard.signed_in_as', ['name' => (string) $admin['username']])) ?>
        <?php if (($admin['last_login_at'] ?? null) !== null): ?>
            <br>
            <span class="small"><?= e(t('account.last_login', [
                'when' => I18n::date(new DateTimeImmutable((string) $admin['last_login_at']), true),
            ])) ?></span>
        <?php endif; ?>
    </p>
</section>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('account.password')) ?></h2>

    <?php if ($formError !== ''): ?>
        <div class="notice notice--danger"><p><?= e($formError) ?></p></div>
    <?php endif; ?>

    <form method="post" class="stack">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="password">

        <div class="field">
            <label for="current"><?= e(t('account.password.current')) ?></label>
            <input type="password" id="current" name="current"
                   autocomplete="current-password" required>
        </div>

        <div class="field">
            <label for="password"><?= e(t('account.password.new')) ?></label>
            <input type="password" id="password" name="password" autocomplete="new-password"
                   required minlength="<?= Auth::minPasswordLength() ?>">
            <p class="field__hint"><?= e(t('setup.admin.password_hint')) ?></p>
        </div>

        <div class="field">
            <label for="password_repeat"><?= e(t('setup.admin.password_repeat')) ?></label>
            <input type="password" id="password_repeat" name="password_repeat"
                   autocomplete="new-password" required
                   minlength="<?= Auth::minPasswordLength() ?>">
        </div>

        <div class="button-row">
            <button type="submit"><?= e(t('account.password.change')) ?></button>
        </div>
    </form>
</section>
