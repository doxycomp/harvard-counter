<?php

declare(strict_types=1);

/**
 * Coaches: create, edit, access links, and the Discord template.
 *
 * The template is four free-text fields rather than a switch per feature, so
 * numbering, headings, bold, quote style and plain sentences all come out of
 * the same form. Presets fill the fields without saving, and the preview is
 * rendered by the same Formatter the frontend uses.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\AdminPage;
use App\Collections;
use App\Contexts;
use App\Csrf;
use App\Formatter;
use App\I18n;
use App\Settings;
use App\Str;
use App\Theme;
use App\Web;

[$vars, $view] = AdminPage::start('coaches');

$collections = Collections::active();

/** The item used for the preview: the first one of the chosen collection. */
$sampleFor = static function (?int $collectionId) use ($collections): array {
    $collection = $collectionId !== null
        ? (Collections::find($collectionId) ?? ($collections[0] ?? null))
        : ($collections[0] ?? null);

    if ($collection === null) {
        return ['collection' => null, 'lines' => [], 'item_no' => 1];
    }

    $item = Collections::item((int) $collection['id'], 1);

    return [
        'collection' => $collection,
        'lines' => $item === null ? [] : array_slice(Collections::lines((int) $item['id']), 0, 4),
        'item_no' => 1,
    ];
};

/** Read the template out of a posted form. */
$templateFromPost = static fn (): array => [
    'header' => Str::truncate((string) ($_POST['fmt_header'] ?? ''), 500, ''),
    'line' => Str::truncate((string) ($_POST['fmt_line'] ?? ''), 500, ''),
    'footer' => Str::truncate((string) ($_POST['fmt_footer'] ?? ''), 500, ''),
    'codeblock' => isset($_POST['fmt_codeblock']),
    'codeblock_lang' => Str::truncate((string) ($_POST['fmt_codeblock_lang'] ?? ''), 20, ''),
];

$editing = AdminPage::id('id');
$formTemplate = null;
$formError = '';

if (Web::isPost()) {
    Csrf::verify();
    $action = Web::stringParam('action', $_POST);
    $id = AdminPage::id('id', $_POST);

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if (Str::length($name) < 2) {
            $formError = t('coach.error.name');
        } elseif (Contexts::findByName($name, Contexts::COACH) !== null) {
            $formError = t('coach.error.exists', ['name' => $name]);
        } else {
            $locale = Web::stringParam('locale', $_POST);
            $newId = Contexts::createCoach(
                $name,
                $locale !== null && I18n::isSupported($locale) ? $locale : null,
            );
            AdminPage::flash('success', t('coach.created', ['name' => $name]));
            Web::redirect('coaches.php?id=' . $newId);
        }
    }

    if ($action === 'preset' && $id !== null) {
        // Fill the form but do not save: the wording is the coach's to review.
        $preset = Web::stringParam('preset', $_POST);
        if (isset(Formatter::PRESETS[$preset])) {
            $formTemplate = Formatter::PRESETS[$preset];
        }
        $editing = $id;
    }

    if ($action === 'save' && $id !== null) {
        $coach = Contexts::find($id);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($coach === null || $coach['kind'] !== Contexts::COACH) {
            $formError = t('coach.error.missing');
        } elseif (Str::length($name) < 2) {
            $formError = t('coach.error.name');
            $formTemplate = $templateFromPost();
            $editing = $id;
        } else {
            $template = $templateFromPost();
            $locale = Web::stringParam('locale', $_POST);
            $theme = Web::stringParam('theme', $_POST);
            $mode = Web::stringParam('color_mode', $_POST);
            $defaultCollection = AdminPage::id('default_collection_id', $_POST);

            Contexts::update($id, [
                'name' => Str::truncate($name, 120, ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'locale' => $locale !== null && I18n::isSupported($locale) ? $locale : null,
                'theme' => Theme::isTheme($theme) ? $theme : null,
                'color_mode' => Theme::isMode($mode) && $mode !== 'system' ? $mode : null,
                'default_collection_id' => $defaultCollection,
                'fmt_header' => $template['header'],
                'fmt_line' => $template['line'],
                'fmt_footer' => $template['footer'],
                'fmt_codeblock' => $template['codeblock'] ? 1 : 0,
                'fmt_codeblock_lang' => $template['codeblock_lang'],
            ]);

            AdminPage::flash('success', t('coach.saved'));
            Web::redirect('coaches.php?id=' . $id);
        }
    }

    if ($action === 'rotate' && $id !== null) {
        Contexts::rotateToken($id);
        AdminPage::flash('success', t('coach.token.rotated'));
        Web::redirect('coaches.php?id=' . $id);
    }

    if ($action === 'delete' && $id !== null) {
        if (Contexts::isUnused($id)) {
            Contexts::delete($id);
            AdminPage::flash('success', t('coach.deleted'));
        } else {
            AdminPage::flash('danger', t('coach.error.in_use'));
        }
        Web::redirect('coaches.php');
    }
}

// ------------------------------------------------------------------ render

if ($editing !== null) {
    $coach = Contexts::find($editing);

    if ($coach === null || $coach['kind'] !== Contexts::COACH) {
        http_response_code(404);
        echo $view->page('error', [
            'title' => t('coach.error.missing'),
            'heading' => t('coach.error.missing'),
            'body' => t('coach.error.missing'),
            'linkHref' => 'coaches.php',
            'linkLabel' => t('admin.nav.coaches'),
        ]);
        exit;
    }

    $template = $formTemplate ?? Formatter::templateFor($coach, $vars['locale']);
    $sample = $sampleFor(
        isset($coach['default_collection_id']) ? (int) $coach['default_collection_id'] : null,
    );

    $previewVars = [
        'item_no' => $sample['item_no'],
        'count' => 7,
        'context' => (string) $coach['name'],
        'coach' => (string) $coach['name'],
        'student' => t('coach.preview.student'),
        'collection' => $sample['collection'] === null
            ? '—'
            : Collections::name($sample['collection'], $vars['locale']),
        'item_label' => $sample['collection'] === null
            ? '—'
            : Collections::itemLabel($sample['collection'], $vars['locale']),
    ];

    echo $view->page('admin/coach-edit', [
        'title' => (string) $coach['name'],
        'messages' => AdminPage::takeFlash(),
        'coach' => $coach,
        'template' => $template,
        'unsavedPreset' => $formTemplate !== null,
        'formError' => $formError,
        'collections' => $collections,
        'preview' => Formatter::render($template, $previewVars, $sample['lines']),
        'previewVars' => $previewVars,
        'previewLines' => array_map(static fn (array $l): string => (string) $l['text'], $sample['lines']),
        'markdownWarning' => Formatter::markdownInCodeblock($template),
        'studentCount' => Contexts::studentCount((int) $coach['id']),
        'canDelete' => Contexts::isUnused((int) $coach['id']),
    ]);
    exit;
}

$rows = [];
foreach (Contexts::coaches(false) as $coach) {
    $rows[] = $coach + ['student_count' => Contexts::studentCount((int) $coach['id'])];
}

echo $view->page('admin/coaches', [
    'title' => t('admin.nav.coaches'),
    'messages' => AdminPage::takeFlash(),
    'coaches' => $rows,
    'formError' => $formError,
    'defaultLocale' => Settings::get('default_locale') ?? $vars['locale'],
]);
