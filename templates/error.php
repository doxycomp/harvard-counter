<?php

declare(strict_types=1);

/**
 * @var string      $heading
 * @var string      $body
 * @var string|null $linkHref
 * @var string|null $linkLabel
 */
$linkHref ??= null;
$linkLabel ??= null;
?>
<h1><?= e($heading) ?></h1>

<div class="card">
    <p><?= e($body) ?></p>

    <?php if ($linkHref !== null && $linkLabel !== null): ?>
        <p><a class="button" href="<?= e($linkHref) ?>"><?= e($linkLabel) ?></a></p>
    <?php endif; ?>
</div>
