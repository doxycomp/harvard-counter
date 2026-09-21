<?php

declare(strict_types=1);

use App\Csrf;

/**
 * Navigation differs by role. This is presentation only — every page checks
 * the role and the scope itself.
 *
 * @var string $active
 * @var bool   $isAdmin
 */
$items = $isAdmin
    ? [
        'dashboard' => ['index.php', 'admin.nav.overview'],
        'coaches' => ['coaches.php', 'admin.nav.coaches'],
        'students' => ['students.php', 'admin.nav.students'],
        'counters' => ['counters.php', 'admin.nav.counters'],
        'stats' => ['stats.php', 'admin.nav.stats'],
        'collections' => ['collections.php', 'admin.nav.collections'],
        'data' => ['data.php', 'admin.nav.data'],
        // An administrator reaches their own password through the users page.
        'users' => ['users.php', 'admin.nav.users'],
    ]
    : [
        'dashboard' => ['index.php', 'admin.nav.overview'],
        'coaches' => ['coaches.php', 'admin.nav.profile'],
        'students' => ['students.php', 'admin.nav.students'],
        'counters' => ['counters.php', 'admin.nav.counters'],
        'stats' => ['stats.php', 'admin.nav.stats'],
        'data' => ['data.php', 'admin.nav.export'],
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
