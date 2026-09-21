<?php

declare(strict_types=1);

use App\Contexts;
use App\Csrf;

/**
 * Two forms on purpose.
 *
 * The identity form is a GET: switching collection or context re-renders with
 * that row's counters and never counts anything. The number form is a POST and
 * is the only thing in the application that increments.
 *
 * @var App\Visitor $visitor
 * @var array    $collection
 * @var array    $collections
 * @var int      $itemCount
 * @var string   $itemLabel
 * @var int|null $number
 * @var string   $formError
 * @var int[]    $leastUsed
 * @var int      $lowestCount
 * @var callable $link
 * @var string|null $carryToken
 */
$showCollectionPicker = count($collections) > 1;
$showContextPicker = !$visitor->isDemo() && count($visitor->selectable) > 0;
?>
<div class="card">
    <?php if ($showCollectionPicker || $showContextPicker): ?>
        <form method="get" class="picker-identity" data-autosubmit>
            <?php if ($carryToken !== null): ?>
                <input type="hidden" name="t" value="<?= e($carryToken) ?>">
            <?php endif; ?>
            <?php if ($number !== null): ?>
                <input type="hidden" name="n" value="<?= (int) $number ?>">
            <?php endif; ?>

            <?php if ($showCollectionPicker): ?>
                <div class="field">
                    <label for="pick-collection"><?= e(t('picker.collection')) ?></label>
                    <select id="pick-collection" name="c">
                        <?php foreach ($collections as $option): ?>
                            <option value="<?= e((string) $option['slug']) ?>"
                                <?= $option['slug'] === $collection['slug'] ? 'selected' : '' ?>>
                                <?= e(App\Collections::name($option, $locale)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" name="c" value="<?= e((string) $collection['slug']) ?>">
            <?php endif; ?>

            <?php if ($showContextPicker): ?>
                <div class="field">
                    <label for="pick-ctx"><?= e(t('picker.context')) ?></label>
                    <select id="pick-ctx" name="ctx">
                        <?php foreach ($visitor->selectable as $option): ?>
                            <option value="<?= (int) $option['id'] ?>"
                                <?= $option['id'] === ($visitor->selected['id'] ?? null) ? 'selected' : '' ?>>
                                <?= e($option['kind'] === Contexts::COACH
                                    ? t('picker.context.coach_total', ['name' => $option['label']])
                                    : $option['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="button-row">
                <button type="submit" class="button--quiet" data-autosubmit-button>
                    <?= e(t('appearance.apply')) ?>
                </button>
            </div>
        </form>
    <?php endif; ?>

    <form method="post" class="picker-number">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="show">

        <?php if ($itemCount > 1): ?>
            <div class="field">
                <label for="pick-n"><?= e(t('picker.number')) ?></label>
                <input type="number" id="pick-n" name="n" inputmode="numeric"
                       min="1" max="<?= $itemCount ?>" autocomplete="off"
                       placeholder="1–<?= $itemCount ?>">
                <p class="field__hint"><?= e(t('picker.number.hint', [
                    'label' => $itemLabel,
                    'max' => $itemCount,
                ])) ?></p>
                <?php // Inside the field, so it does not become a cell of its own in the grid. ?>
                <?php if ($formError !== ''): ?>
                    <p class="field__hint field__hint--error"><?= e($formError) ?></p>
                <?php endif; ?>
            </div>
        <?php elseif ($formError !== ''): ?>
            <p class="field__hint field__hint--error"><?= e($formError) ?></p>
        <?php endif; ?>

        <div class="button-row">
            <button type="submit"><?= e(t('picker.submit')) ?></button>
            <button type="submit" name="random" value="1" class="button--quiet">
                <?= e(t('picker.random')) ?>
            </button>
            <?php if ($number !== null || $formError !== ''): ?>
                <?php // Back to the empty picker; coach, context and collection stay. ?>
                <a class="button button--quiet" href="<?= e($link()) ?>"><?= e(t('picker.reset')) ?></a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($leastUsed !== []): ?>
        <p class="small muted picker-hint">
            <?= e(t('picker.least_used', ['count' => $lowestCount])) ?>
            <?php foreach ($leastUsed as $index => $itemNo): ?>
                <a href="<?= e($link(['n' => $itemNo])) ?>"><?= (int) $itemNo ?></a><?= $index < count($leastUsed) - 1 ? ',' : '' ?>
            <?php endforeach; ?>
        </p>
    <?php endif; ?>

    <?php if ($visitor->coach !== null): ?>
        <p class="small muted picker-hint">
            <a href="export.php<?= $carryToken === null ? '' : '?t=' . e($carryToken) ?>">
                <?= e(t('export.link')) ?>
            </a>
        </p>
    <?php endif; ?>
</div>
