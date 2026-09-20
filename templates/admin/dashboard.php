<?php

declare(strict_types=1);

use App\I18n;

/**
 * @var array    $admin
 * @var string[] $pending
 * @var int      $collections
 * @var int      $items
 * @var int      $coachCount
 * @var int      $studentCount
 * @var int      $totalUses
 */
?>
<h1><?= e(t('admin.dashboard.title')) ?></h1>

<p class="muted">
    <?= e(t('admin.dashboard.signed_in_as', ['name' => (string) $admin['username']])) ?>
</p>

<?php if ($pending !== []): ?>
    <div class="notice notice--danger">
        <p><?= e(tn('setup.schema.pending', count($pending))) ?></p>
        <p><a class="button" href="setup.php"><?= e(t('setup.schema.apply')) ?></a></p>
    </div>
<?php endif; ?>

<section class="card">
    <div class="stat-row">
        <div class="stat">
            <span class="stat__value"><?= e(I18n::number($coachCount)) ?></span>
            <span class="stat__label"><a href="coaches.php"><?= e(t('admin.nav.coaches')) ?></a></span>
        </div>
        <div class="stat">
            <span class="stat__value"><?= e(I18n::number($studentCount)) ?></span>
            <span class="stat__label"><a href="students.php"><?= e(t('admin.nav.students')) ?></a></span>
        </div>
        <div class="stat">
            <span class="stat__value"><?= e(I18n::number($items)) ?></span>
            <span class="stat__label"><a href="collections.php"><?= e(t('admin.nav.collections')) ?></a></span>
        </div>
        <div class="stat">
            <span class="stat__value"><?= e(I18n::number($totalUses)) ?></span>
            <span class="stat__label"><a href="stats.php"><?= e(t('stats.total')) ?></a></span>
        </div>
    </div>
</section>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('setup.step.schema')) ?></h2>
    <?php if ($pending === []): ?>
        <p class="muted"><?= e(t('setup.schema.current')) ?></p>
    <?php endif; ?>
    <p class="muted"><?= e(tn('setup.collections.present', $collections)) ?></p>

    <div class="button-row">
        <a class="button button--quiet" href="setup.php"><?= e(t('admin.dashboard.setup')) ?></a>
        <a class="button button--quiet" href="../"><?= e(t('admin.dashboard.frontend')) ?></a>
    </div>
</section>
