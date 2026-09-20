<?php

declare(strict_types=1);

/**
 * All items with their counters. Opening one from here is a GET and therefore
 * does not count — this is the "let me look something up" view.
 *
 * @var array<int,int> $countsByItemNo
 * @var string   $itemLabel
 * @var int|null $number
 * @var callable $link
 */
if ($countsByItemNo === []) {
    return;
}
?>
<details class="card overview">
    <summary><?= e(t('overview.title', ['count' => count($countsByItemNo)])) ?></summary>

    <p class="small muted"><?= e(t('overview.hint')) ?></p>

    <div class="item-grid">
        <?php foreach ($countsByItemNo as $itemNo => $uses): ?>
            <a class="item-chip<?= $uses === 0 ? ' item-chip--unused' : '' ?><?= $itemNo === $number ? ' item-chip--current' : '' ?>"
               href="<?= e($link(['n' => $itemNo])) ?>"
               title="<?= e(tn('result.uses', (int) $uses)) ?>">
                <span class="item-chip__no"><?= (int) $itemNo ?></span>
                <span class="item-chip__uses"><?= (int) $uses ?>×</span>
            </a>
        <?php endforeach; ?>
    </div>
</details>
