<?php

declare(strict_types=1);

use App\I18n;
use App\Theme;

/**
 * @var string $content   rendered page body
 * @var string $locale    active UI locale
 * @var string $theme     active colour theme
 * @var string $mode      active colour mode
 * @var string $title     page title
 * @var string $basePath  path prefix for assets and links
 */
$title ??= t('app.name');
$basePath ??= '';
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>" data-theme="<?= e($theme) ?>" data-mode="<?= e($mode) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> · <?= e(t('app.name')) ?></title>
    <link rel="stylesheet" href="<?= e($basePath) ?>assets/themes.css">
    <link rel="stylesheet" href="<?= e($basePath) ?>assets/app.css">
</head>
<body>
<div class="band" aria-hidden="true"></div>

<header class="site-header">
    <p class="site-header__title">
        <a href="<?= e($basePath) ?>"><?= e(t('app.name')) ?></a>
    </p>

    <form class="switcher" method="get" action="" data-autosubmit>
        <?php foreach (($carry ?? []) as $name => $value): ?>
            <input type="hidden" name="<?= e($name) ?>" value="<?= e((string) $value) ?>">
        <?php endforeach; ?>

        <label class="visually-hidden" for="switch-lang"><?= e(t('appearance.language')) ?></label>
        <select id="switch-lang" name="lang">
            <?php foreach (I18n::LOCALES as $option): ?>
                <option value="<?= e($option) ?>"<?= $option === $locale ? ' selected' : '' ?>>
                    <?= e(I18n::LOCALE_NAMES[$option]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="visually-hidden" for="switch-theme"><?= e(t('appearance.theme')) ?></label>
        <select id="switch-theme" name="theme">
            <?php foreach (Theme::THEMES as $option): ?>
                <option value="<?= e($option) ?>"<?= $option === $theme ? ' selected' : '' ?>>
                    <?= e(t(Theme::label($option))) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="visually-hidden" for="switch-mode"><?= e(t('appearance.mode')) ?></label>
        <select id="switch-mode" name="mode">
            <?php foreach (Theme::MODES as $option): ?>
                <option value="<?= e($option) ?>"<?= $option === $mode ? ' selected' : '' ?>>
                    <?= e(t('appearance.mode.' . $option)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="button--quiet" data-autosubmit-button>
            <?= e(t('appearance.apply')) ?>
        </button>
    </form>
</header>

<main class="page">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="site-footer__inner">
        <p class="small"><?= e(t('footer.license')) ?></p>
        <p class="small"><?= e(t('footer.attribution')) ?></p>
    </div>
</footer>

<script src="<?= e($basePath) ?>assets/app.js" defer></script>
</body>
</html>
