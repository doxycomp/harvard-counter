<?php

declare(strict_types=1);

/**
 * Placeholder until the sentence picker lands. It shows that the installation
 * works end to end: schema, collections and sentences are all in place.
 *
 * @var int $collections
 * @var int $items
 */
?>
<h1><?= e(t('home.title')) ?></h1>
<p><?= e(t('home.intro')) ?></p>

<div class="card">
    <p class="muted"><?= e(tn('home.ready.collections', $collections)) ?></p>
    <p class="muted"><?= e(tn('home.ready.items', $items)) ?></p>
    <p class="small muted"><?= e(t('home.ready.next')) ?></p>
</div>
