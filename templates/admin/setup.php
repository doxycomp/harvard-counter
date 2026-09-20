<?php

declare(strict_types=1);

use App\Auth;
use App\Csrf;

/**
 * @var bool     $unlocked
 * @var bool     $tokenConfigured
 * @var string   $tokenError
 * @var array    $messages       list of ['type' => 'success'|'danger', 'text' => string]
 * @var string[] $pending        pending migration versions
 * @var int      $collections
 * @var int      $items
 * @var bool     $hasAdmin
 * @var string   $formUsername
 * @var string   $adminError
 */
?>
<h1><?= e(t('setup.title')) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>">
        <p><?= e($message['text']) ?></p>
    </div>
<?php endforeach; ?>

<?php if (!$unlocked): ?>

    <p><?= e(t('setup.intro')) ?></p>

    <div class="card">
        <?php if (!$tokenConfigured): ?>
            <div class="notice notice--danger">
                <p><?= e(t('setup.token.missing')) ?></p>
            </div>
        <?php else: ?>
            <form method="post" class="stack">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="unlock">

                <div class="field">
                    <label for="setup-token"><?= e(t('setup.token.label')) ?></label>
                    <input type="password" id="setup-token" name="setup_token"
                           autocomplete="off" autofocus required>
                    <p class="field__hint"><?= e(t('setup.token.hint')) ?></p>
                    <?php if ($tokenError !== ''): ?>
                        <p class="field__hint" style="color: var(--danger)"><?= e($tokenError) ?></p>
                    <?php endif; ?>
                </div>

                <div class="button-row">
                    <button type="submit"><?= e(t('setup.unlock')) ?></button>
                </div>
            </form>
        <?php endif; ?>
    </div>

<?php else: ?>

    <section class="card">
        <h2 style="margin-top:0"><?= e(t('setup.step.schema')) ?></h2>

        <?php if ($pending === []): ?>
            <p class="muted"><?= e(t('setup.schema.current')) ?></p>
        <?php else: ?>
            <p><?= e(tn('setup.schema.pending', count($pending))) ?></p>
            <p class="small mono muted"><?= e(implode(', ', $pending)) ?></p>
            <form method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="migrate">
                <button type="submit"><?= e(t('setup.schema.apply')) ?></button>
            </form>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 style="margin-top:0"><?= e(t('setup.step.collections')) ?></h2>

        <?php if ($collections === 0): ?>
            <p><?= e(t('setup.collections.none')) ?></p>
        <?php else: ?>
            <p><?= e(tn('setup.collections.present', $collections)) ?></p>
        <?php endif; ?>

        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="import">
            <button type="submit" <?= $pending === [] ? '' : 'disabled' ?>>
                <?= e(t('setup.collections.import')) ?>
            </button>
        </form>
    </section>

    <section class="card">
        <h2 style="margin-top:0"><?= e(t('setup.step.admin')) ?></h2>

        <?php if ($hasAdmin): ?>
            <p class="muted"><?= e(t('setup.done.body')) ?></p>
            <p><a class="button" href="index.php"><?= e(t('setup.done.link')) ?></a></p>
        <?php else: ?>
            <p><?= e(t('setup.admin.none')) ?></p>

            <?php if ($adminError !== ''): ?>
                <div class="notice notice--danger"><p><?= e($adminError) ?></p></div>
            <?php endif; ?>

            <form method="post" class="stack">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="create-admin">

                <div class="field">
                    <label for="admin-username"><?= e(t('setup.admin.username')) ?></label>
                    <input type="text" id="admin-username" name="username"
                           value="<?= e($formUsername) ?>" autocomplete="username" required>
                </div>

                <div class="field">
                    <label for="admin-password"><?= e(t('setup.admin.password')) ?></label>
                    <input type="password" id="admin-password" name="password"
                           autocomplete="new-password" required
                           minlength="<?= Auth::minPasswordLength() ?>">
                    <p class="field__hint"><?= e(t('setup.admin.password_hint')) ?></p>
                </div>

                <div class="field">
                    <label for="admin-password2"><?= e(t('setup.admin.password_repeat')) ?></label>
                    <input type="password" id="admin-password2" name="password_repeat"
                           autocomplete="new-password" required
                           minlength="<?= Auth::minPasswordLength() ?>">
                </div>

                <div class="button-row">
                    <button type="submit" <?= $pending === [] ? '' : 'disabled' ?>>
                        <?= e(t('setup.admin.create')) ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </section>

<?php endif; ?>
