<?php

declare(strict_types=1);

use App\Csrf;

/**
 * @var array    $admin
 * @var string[] $pending
 * @var int      $collections
 * @var int      $items
 */
?>
<h1><?= e(t('admin.dashboard.title')) ?></h1>

<p class="muted">
    <?= e(t('admin.dashboard.signed_in_as', ['name' => (string) $admin['username']])) ?>
</p>

<div class="card">
    <h2 style="margin-top:0"><?= e(t('setup.step.schema')) ?></h2>
    <?php if ($pending === []): ?>
        <p class="muted"><?= e(t('setup.schema.current')) ?></p>
    <?php else: ?>
        <div class="notice">
            <p><?= e(tn('setup.schema.pending', count($pending))) ?></p>
        </div>
    <?php endif; ?>

    <p class="muted"><?= e(tn('setup.collections.present', $collections)) ?></p>

    <div class="button-row">
        <a class="button button--quiet" href="setup.php"><?= e(t('admin.dashboard.setup')) ?></a>
    </div>
</div>

<div class="card">
    <p class="muted"><?= e(t('admin.dashboard.todo')) ?></p>
</div>

<form method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="signout">
    <button type="submit" class="button--quiet"><?= e(t('login.signout')) ?></button>
</form>
