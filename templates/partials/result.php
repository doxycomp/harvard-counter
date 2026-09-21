<?php

declare(strict_types=1);

use App\Csrf;

/**
 * @var App\Visitor $visitor
 * @var array  $result
 * @var string $itemLabel
 * @var string $collectionName
 * @var string|null $coachTotalLabel
 * @var int    $studentCoachCount
 */
$blockId = 'discord-block';
?>
<section class="card result">
    <h2 class="result__title">
        <?= e($itemLabel) ?> <?= (int) $result['item_no'] ?>
        <span class="result__collection muted"><?= e($collectionName) ?></span>
    </h2>

    <p class="result__counts">
        <?php if ($visitor->isSelfPractice()): ?>
            <strong><?= e(t('result.self_uses', ['count' => $result['primary']])) ?></strong>
            <span class="muted">·</span>
            <span class="muted"><?= e(t('result.lesson_uses', ['count' => (int) $result['lessonUses']])) ?></span>
        <?php elseif ($visitor->hasStudentSelected()): ?>
            <strong><?= e(t('result.uses_for', [
                'name' => $visitor->selected['label'],
                'count' => $result['uses'],
            ])) ?></strong>
            <?php if ($coachTotalLabel !== null): ?>
                <span class="muted">·</span>
                <span class="muted"><?= e(t('result.total_for', [
                    'name' => $coachTotalLabel,
                    'count' => $result['total'],
                ])) ?></span>
            <?php endif; ?>
        <?php else: ?>
            <strong><?= e(tn('result.uses', (int) $result['primary'])) ?></strong>
        <?php endif; ?>
    </p>

    <?php if (($result['selfUses'] ?? 0) > 0): ?>
        <p class="small muted"><?= e(t('result.self_uses_student', ['count' => (int) $result['selfUses']])) ?></p>
    <?php endif; ?>

    <?php if ($studentCoachCount > 1): ?>
        <p class="small muted"><?= e(t('result.shared_counter')) ?></p>
    <?php endif; ?>

    <p class="small muted"><?= e(t('result.copy_hint')) ?></p>

    <ol class="sentences">
        <?php foreach ($result['lines'] as $line): ?>
            <li>
                <button type="button" class="sentence"
                        data-copy="self"
                        data-copy-text="<?= e((string) $line['text']) ?>"
                        data-copied-label="<?= e(t('result.copied')) ?>">
                    <span class="sentence__text"><?= e((string) $line['text']) ?></span>
                    <span class="sentence__status" data-copy-status aria-live="polite"></span>
                </button>
            </li>
        <?php endforeach; ?>
    </ol>

    <div class="button-row result__actions">
        <button type="button" data-copy="<?= $blockId ?>"
                data-copied-label="<?= e(t('result.copied')) ?>">
            <span data-copy-status aria-live="polite"><?= e(t('result.copy_all')) ?></span>
        </button>

        <?php if ($result['canUndo']): ?>
            <form method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="undo">
                <button type="submit" class="button--danger"><?= e(t('result.dont_count')) ?></button>
            </form>
        <?php endif; ?>
    </div>

    <details class="result__block">
        <summary><?= e(t('result.show_block')) ?></summary>
        <pre id="<?= $blockId ?>" class="discord-block"><?= e($result['discord']) ?></pre>
    </details>
</section>
