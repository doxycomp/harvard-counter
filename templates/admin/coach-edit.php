<?php

declare(strict_types=1);

use App\Collections;
use App\Csrf;
use App\Formatter;
use App\I18n;
use App\Theme;

/**
 * @var array  $coach
 * @var array  $template
 * @var bool   $unsavedPreset
 * @var string $formError
 * @var array  $collections
 * @var string $preview
 * @var array  $previewVars
 * @var string[] $previewLines
 * @var bool   $markdownWarning
 * @var int    $studentCount
 * @var bool   $canDelete
 * @var bool   $isAdmin
 * @var array  $messages
 */
$id = (int) $coach['id'];
$accessLink = App\Web::accessLink((string) $coach['access_token']);
?>
<?php if ($isAdmin): ?>
    <p class="small"><a href="coaches.php">&larr; <?= e(t('admin.nav.coaches')) ?></a></p>
<?php endif; ?>

<h1><?= e((string) $coach['name']) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<?php if ($formError !== ''): ?>
    <div class="notice notice--danger"><p><?= e($formError) ?></p></div>
<?php endif; ?>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('coach.link')) ?></h2>

    <div class="button-row">
        <button type="button" data-copy="self" data-copy-text="<?= e($accessLink) ?>"
                data-copied-label="<?= e(t('result.copied')) ?>">
            <span class="mono"><?= e($accessLink) ?></span>
            <span data-copy-status></span>
        </button>
    </div>

    <p class="small muted"><?= e(t('coach.link.hint')) ?></p>

    <form method="post" onsubmit="return confirm('<?= e(t('coach.token.confirm')) ?>')">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="rotate">
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="button--danger"><?= e(t('coach.token.rotate')) ?></button>
    </form>
</section>

<form method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <section class="card">
        <h2 style="margin-top:0"><?= e(t('coach.section.basics')) ?></h2>

        <div class="field">
            <label for="name"><?= e(t('coach.name')) ?></label>
            <input type="text" id="name" name="name" maxlength="120" required
                   value="<?= e((string) $coach['name']) ?>">
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="locale"><?= e(t('coach.locale')) ?></label>
                <select id="locale" name="locale">
                    <option value=""><?= e(t('coach.locale.inherit')) ?></option>
                    <?php foreach (I18n::LOCALES as $option): ?>
                        <option value="<?= e($option) ?>"
                            <?= ($coach['locale'] ?? '') === $option ? 'selected' : '' ?>>
                            <?= e(I18n::LOCALE_NAMES[$option]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="default_collection_id"><?= e(t('coach.default_collection')) ?></label>
                <select id="default_collection_id" name="default_collection_id">
                    <option value=""><?= e(t('coach.default_collection.none')) ?></option>
                    <?php foreach ($collections as $option): ?>
                        <option value="<?= (int) $option['id'] ?>"
                            <?= (int) ($coach['default_collection_id'] ?? 0) === (int) $option['id'] ? 'selected' : '' ?>>
                            <?= e(Collections::name($option, $locale)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="theme"><?= e(t('appearance.theme')) ?></label>
                <select id="theme" name="theme">
                    <option value=""><?= e(t('coach.appearance.inherit')) ?></option>
                    <?php foreach (Theme::THEMES as $option): ?>
                        <option value="<?= e($option) ?>"
                            <?= ($coach['theme'] ?? '') === $option ? 'selected' : '' ?>>
                            <?= e(t(Theme::label($option))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="color_mode"><?= e(t('appearance.mode')) ?></label>
                <select id="color_mode" name="color_mode">
                    <option value=""><?= e(t('coach.appearance.inherit')) ?></option>
                    <?php foreach (['light', 'dark'] as $option): ?>
                        <option value="<?= e($option) ?>"
                            <?= ($coach['color_mode'] ?? '') === $option ? 'selected' : '' ?>>
                            <?= e(t('appearance.mode.' . $option)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($isAdmin): ?>
            <div class="field">
                <label class="checkbox">
                    <input type="checkbox" name="is_active" value="1"
                        <?= (int) $coach['is_active'] === 1 ? 'checked' : '' ?>>
                    <?= e(t('coach.active')) ?>
                </label>
                <p class="field__hint"><?= e(t('coach.active.hint')) ?></p>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 style="margin-top:0"><?= e(t('coach.section.template')) ?></h2>
        <p class="small muted"><?= e(t('coach.template.intro')) ?></p>

        <?php if ($unsavedPreset): ?>
            <div class="notice"><p><?= e(t('coach.preset.unsaved')) ?></p></div>
        <?php endif; ?>

        <div class="field">
            <label for="fmt_header"><?= e(t('coach.template.header')) ?></label>
            <input type="text" id="fmt_header" name="fmt_header" maxlength="500"
                   data-preview-field value="<?= e($template['header']) ?>">
        </div>

        <div class="field">
            <label for="fmt_line"><?= e(t('coach.template.line')) ?></label>
            <input type="text" id="fmt_line" name="fmt_line" maxlength="500"
                   data-preview-field value="<?= e($template['line']) ?>">
        </div>

        <div class="field">
            <label for="fmt_footer"><?= e(t('coach.template.footer')) ?></label>
            <input type="text" id="fmt_footer" name="fmt_footer" maxlength="500"
                   data-preview-field value="<?= e($template['footer']) ?>">
        </div>

        <div class="grid-2">
            <div class="field">
                <label class="checkbox">
                    <input type="checkbox" id="fmt_codeblock" name="fmt_codeblock" value="1"
                           data-preview-field <?= $template['codeblock'] ? 'checked' : '' ?>>
                    <?= e(t('coach.template.codeblock')) ?>
                </label>
            </div>

            <div class="field">
                <label for="fmt_codeblock_lang"><?= e(t('coach.template.codeblock_lang')) ?></label>
                <input type="text" id="fmt_codeblock_lang" name="fmt_codeblock_lang" maxlength="20"
                       data-preview-field value="<?= e($template['codeblock_lang']) ?>">
            </div>
        </div>

        <p class="small muted"><?= e(t('coach.template.placeholders')) ?></p>
        <p class="small mono placeholder-list">
            <?php foreach (Formatter::PLACEHOLDERS as $placeholder): ?>
                <code><?= e($placeholder) ?></code>
            <?php endforeach; ?>
        </p>

        <h3><?= e(t('coach.preview')) ?></h3>

        <div class="notice notice--danger<?= $markdownWarning ? '' : ' hidden' ?>"
             data-preview-warning>
            <p><?= e(t('coach.template.codeblock_warning')) ?></p>
        </div>

        <pre class="discord-block" data-preview
             data-preview-vars="<?= e(json_encode($previewVars, JSON_UNESCAPED_UNICODE)) ?>"
             data-preview-lines="<?= e(json_encode($previewLines, JSON_UNESCAPED_UNICODE)) ?>"><?= e($preview) ?></pre>

        <div class="button-row">
            <button type="submit" name="action" value="save"><?= e(t('common.save')) ?></button>
        </div>
    </section>
</form>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('coach.presets')) ?></h2>
    <p class="small muted"><?= e(t('coach.presets.hint')) ?></p>

    <form method="post" class="button-row">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="preset">
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php foreach (array_keys(Formatter::PRESETS) as $preset): ?>
            <button type="submit" name="preset" value="<?= e($preset) ?>" class="button--quiet">
                <?= e(t('coach.preset.' . $preset)) ?>
            </button>
        <?php endforeach; ?>
    </form>
</section>

<?php if ($isAdmin): ?>
<section class="card">
    <h2 style="margin-top:0"><?= e(t('coach.section.danger')) ?></h2>

    <?php if ($canDelete): ?>
        <p class="small muted"><?= e(t('coach.delete.hint')) ?></p>
        <form method="post" onsubmit="return confirm('<?= e(t('coach.delete.confirm')) ?>')">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="button--danger"><?= e(t('coach.delete')) ?></button>
        </form>
    <?php else: ?>
        <p class="small muted"><?= e(t('coach.delete.blocked')) ?></p>
    <?php endif; ?>

    <?php if ($studentCount > 0): ?>
        <p class="small muted">
            <?= e(tn('coach.students.count', $studentCount)) ?>
            <a href="students.php"><?= e(t('admin.nav.students')) ?></a>
        </p>
    <?php endif; ?>
</section>
<?php endif; ?>
