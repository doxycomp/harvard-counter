<?php

declare(strict_types=1);

use App\Collections;
use App\Csrf;

/**
 * @var array      $coaches
 * @var array      $students
 * @var array      $collections
 * @var array|null $context
 * @var array|null $collection
 * @var array      $rows
 * @var string     $itemLabel
 * @var bool       $isCoach
 * @var array      $messages
 */
?>
<h1><?= e(t('admin.nav.counters')) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<section class="card">
    <form method="get" class="inline-form" data-autosubmit>
        <div class="field">
            <label for="ctx"><?= e(t('counters.context')) ?></label>
            <select id="ctx" name="ctx">
                <option value=""><?= e(t('counters.context.choose')) ?></option>
                <optgroup label="<?= e(t('admin.nav.coaches')) ?>">
                    <?php foreach ($coaches as $coach): ?>
                        <option value="<?= (int) $coach['id'] ?>"
                            <?= (int) ($context['id'] ?? 0) === (int) $coach['id'] ? 'selected' : '' ?>>
                            <?= e((string) $coach['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="<?= e(t('admin.nav.students')) ?>">
                    <?php foreach ($students as $student): ?>
                        <option value="<?= (int) $student['id'] ?>"
                            <?= (int) ($context['id'] ?? 0) === (int) $student['id'] ? 'selected' : '' ?>>
                            <?= e((string) $student['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>

        <?php if (count($collections) > 1): ?>
            <div class="field">
                <label for="c"><?= e(t('picker.collection')) ?></label>
                <select id="c" name="c">
                    <?php foreach ($collections as $option): ?>
                        <option value="<?= (int) $option['id'] ?>"
                            <?= (int) ($collection['id'] ?? 0) === (int) $option['id'] ? 'selected' : '' ?>>
                            <?= e(Collections::name($option, $locale)) ?>
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
</section>

<?php if ($context === null || $collection === null): ?>
    <div class="card"><p class="muted"><?= e(t('counters.choose_first')) ?></p></div>
<?php else: ?>
    <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="ctx" value="<?= (int) $context['id'] ?>">
        <input type="hidden" name="c" value="<?= (int) $collection['id'] ?>">

        <section class="card">
            <h2 style="margin-top:0"><?= e((string) $context['name']) ?></h2>

            <?php if ($isCoach): ?>
                <p class="small muted"><?= e(t('counters.coach_hint')) ?></p>
            <?php else: ?>
                <p class="small muted"><?= e(t('counters.student_hint')) ?></p>
            <?php endif; ?>

            <div class="counter-grid">
                <?php foreach ($rows as $row): ?>
                    <div class="counter-cell">
                        <label for="uses-<?= (int) $row['item_no'] ?>">
                            <?= e($itemLabel) ?> <?= (int) $row['item_no'] ?>
                        </label>
                        <input type="number" id="uses-<?= (int) $row['item_no'] ?>"
                               name="uses[<?= (int) $row['item_no'] ?>]"
                               value="<?= (int) $row['uses'] ?>" min="0" max="100000"
                               inputmode="numeric">
                        <?php if ($isCoach && $row['total'] !== null): ?>
                            <span class="counter-cell__total"
                                  title="<?= e(t('counters.total_hint')) ?>">
                                Σ <?= (int) $row['total'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="button-row">
                <button type="submit"><?= e(t('common.save')) ?></button>
            </div>
        </section>
    </form>
<?php endif; ?>
