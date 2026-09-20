<?php

declare(strict_types=1);

use App\Csrf;

/** @var string $active */
$items = [
    'dashboard' => ['index.php', 'admin.nav.overview'],
    'coaches' => ['coaches.php', 'admin.nav.coaches'],
    'students' => ['students.php', 'admin.nav.students'],
    'counters' => ['counters.php', 'admin.nav.counters'],
    'stats' => ['stats.php', 'admin.nav.stats'],
    'collections' => ['collections.php', 'admin.nav.collections'],
    'account' => ['account.php', 'admin.nav.account'],
];
?>
<nav class="admin-nav" aria-label="<?= e(t('admin.title')) ?>">
    <div class="admin-nav__inner">
        <ul class="admin-nav__list">
            <?php foreach ($items as $key => [$href, $label]): ?>
                <li>
                    <a href="<?= e($href) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>>
                        <?= e(t($label)) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <form method="post" action="index.php" class="admin-nav__signout">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="signout">
            <button type="submit" class="button--quiet"><?= e(t('login.signout')) ?></button>
        </form>
    </div>
</nav>
