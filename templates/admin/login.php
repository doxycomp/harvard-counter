<?php

declare(strict_types=1);

use App\Csrf;

/** @var string $loginError */
?>
<h1><?= e(t('login.title')) ?></h1>

<?php if ($loginError !== ''): ?>
    <div class="notice notice--danger"><p><?= e($loginError) ?></p></div>
<?php endif; ?>

<div class="card">
    <form method="post" class="stack">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="signin">

        <div class="field">
            <label for="username"><?= e(t('login.username')) ?></label>
            <input type="text" id="username" name="username"
                   autocomplete="username" autofocus required>
        </div>

        <div class="field">
            <label for="password"><?= e(t('login.password')) ?></label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required>
        </div>

        <div class="button-row">
            <button type="submit"><?= e(t('login.submit')) ?></button>
        </div>
    </form>
</div>
