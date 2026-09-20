<?php

declare(strict_types=1);

use App\Collections;
use App\Contexts;
use App\I18n;

/**
 * @var array      $coaches
 * @var array      $collections
 * @var array|null $coach
 * @var array|null $collection
 * @var array|null $data
 * @var array      $messages
 */
?>
<h1><?= e(t('admin.nav.stats')) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<section class="card">
    <form method="get" class="inline-form" data-autosubmit>
        <div class="field">
            <label for="coach"><?= e(t('coach.name')) ?></label>
            <select id="coach" name="coach">
                <?php foreach ($coaches as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"
                        <?= (int) ($coach['id'] ?? 0) === (int) $option['id'] ? 'selected' : '' ?>>
                        <?= e((string) $option['name']) ?>
                    </option>
                <?php endforeach; ?>
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

<?php if ($data === null): ?>
    <div class="card"><p class="muted"><?= e(t('stats.nothing')) ?></p></div>
<?php else: ?>

    <section class="card">
        <div class="stat-row">
            <div class="stat">
                <span class="stat__value"><?= e(I18n::number($data['summary']['total'])) ?></span>
                <span class="stat__label"><?= e(t('stats.total')) ?></span>
            </div>
            <div class="stat">
                <span class="stat__value">
                    <?= $data['summary']['last'] === null
                        ? '—'
                        : e(I18n::date(new DateTimeImmutable((string) $data['summary']['last']), true)) ?>
                </span>
                <span class="stat__label"><?= e(t('stats.last')) ?></span>
            </div>
            <div class="stat">
                <span class="stat__value"><?= e(I18n::number($data['summary']['undone'])) ?></span>
                <span class="stat__label"><?= e(t('stats.undone')) ?></span>
            </div>
            <div class="stat">
                <span class="stat__value"><?= e(I18n::number($data['summary']['students'])) ?></span>
                <span class="stat__label"><?= e(t('coach.students')) ?></span>
            </div>
        </div>
    </section>

    <section class="card">
        <h2 style="margin-top:0"><?= e(t('stats.monthly')) ?></h2>

        <?php if ($data['monthlyPeak'] === 0): ?>
            <p class="muted"><?= e(t('stats.nothing_yet')) ?></p>
        <?php else: ?>
            <ol class="bars">
                <?php foreach ($data['monthly'] as $month => $uses): ?>
                    <?php $height = (int) round($uses / $data['monthlyPeak'] * 100); ?>
                    <li class="bars__item">
                        <span class="bars__value"><?= (int) $uses ?></span>
                        <span class="bars__bar" style="height: <?= max($height, $uses > 0 ? 4 : 1) ?>%"></span>
                        <span class="bars__label"><?= e(substr((string) $month, 5, 2)) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
            <p class="small muted"><?= e(t('stats.monthly.hint')) ?></p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 style="margin-top:0"><?= e(t('stats.by_student')) ?></h2>

        <?php if ($data['byStudent'] === []): ?>
            <p class="muted"><?= e(t('stats.nothing_yet')) ?></p>
        <?php else: ?>
            <table class="data-table">
                <tbody>
                <?php foreach ($data['byStudent'] as $entry): ?>
                    <tr>
                        <td>
                            <?= e($entry['name']) ?>
                            <?php if ($entry['kind'] === Contexts::COACH): ?>
                                <span class="badge"><?= e(t('stats.own_row')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="numeric"><?= e(I18n::number($entry['uses'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <div class="grid-2">
        <section class="card">
            <h2 style="margin-top:0"><?= e(t('stats.top')) ?></h2>
            <ol class="rank-list">
                <?php foreach ($data['top'] as $itemNo => $uses): ?>
                    <li>
                        <span><?= e($data['itemLabel']) ?> <?= (int) $itemNo ?></span>
                        <span class="numeric"><?= (int) $uses ?>×</span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <section class="card">
            <h2 style="margin-top:0"><?= e(t('stats.bottom')) ?></h2>
            <ol class="rank-list">
                <?php foreach ($data['bottom'] as $itemNo => $uses): ?>
                    <li>
                        <span><?= e($data['itemLabel']) ?> <?= (int) $itemNo ?></span>
                        <span class="numeric"><?= (int) $uses ?>×</span>
                    </li>
                <?php endforeach; ?>
            </ol>
            <p class="small muted"><?= e(t('stats.bottom.hint')) ?></p>
        </section>
    </div>

<?php endif; ?>
