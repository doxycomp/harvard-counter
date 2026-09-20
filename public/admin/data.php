<?php

declare(strict_types=1);

/**
 * Export and import of a coach's data.
 *
 * The import previews before it writes: an operator should see "creates one
 * coach and two students, applies 148 counters, skips 3" before anything
 * happens, not afterwards.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\AdminPage;
use App\ContextImporter;
use App\Contexts;
use App\Csrf;
use App\Exporter;
use App\Session;
use App\Str;
use App\Web;

[$vars, $view] = AdminPage::start('data');

const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

$coaches = Contexts::coaches(false);
$importError = '';
$preview = null;

if (Web::isPost()) {
    Csrf::verify();
    $action = Web::stringParam('action', $_POST);

    if ($action === 'export') {
        $coachId = AdminPage::id('coach_id', $_POST);
        $coach = $coachId === null ? null : Contexts::find($coachId);

        if ($coach === null || $coach['kind'] !== Contexts::COACH) {
            AdminPage::flash('danger', t('coach.error.missing'));
            Web::redirect('data.php');
        }

        $export = Exporter::exportCoach($coachId, isset($_POST['include_events']));

        if (Web::stringParam('format', $_POST) === 'csv') {
            Exporter::download(
                Exporter::toCsv($export),
                Exporter::filename((string) $coach['name'], 'csv'),
                'text/csv',
            );
        }

        Exporter::download(
            Exporter::toJson($export),
            Exporter::filename((string) $coach['name'], 'json'),
            'application/json',
        );
    }

    if ($action === 'preview') {
        try {
            $json = readUpload();
            $importer = new ContextImporter();
            $data = $importer->parse($json);

            $name = trim((string) ($_POST['coach_name'] ?? ''));
            if ($name === '') {
                $name = (string) $data['coach']['name'];
            }

            // Hold the parsed file for the confirm step rather than asking for
            // a second upload.
            Session::set('import_payload', $json);
            $preview = $importer->preview($data, $name) + ['name' => $name];
        } catch (Throwable $e) {
            $importError = $e->getMessage();
        }
    }

    if ($action === 'import') {
        $json = (string) Session::get('import_payload', '');

        try {
            if ($json === '') {
                throw new RuntimeException(t('data.import.expired'));
            }

            $importer = new ContextImporter();
            $data = $importer->parse($json);
            $name = trim((string) ($_POST['coach_name'] ?? '')) ?: (string) $data['coach']['name'];

            $result = $importer->import(
                $data,
                Str::truncate($name, 120, ''),
                isset($_POST['overwrite']),
            );

            Session::forget('import_payload');

            AdminPage::flash('success', t('data.import.done', [
                'students' => $result['students'],
                'counts' => $result['counts'],
                'skipped' => $result['skipped'],
                'events' => $result['events'],
            ]));
            foreach ($result['problems'] as $problem) {
                AdminPage::flash('danger', $problem);
            }

            Web::redirect('coaches.php?id=' . $result['coach_id']);
        } catch (Throwable $e) {
            $importError = $e->getMessage();
        }
    }
}

/** Read the uploaded file, refusing anything implausible. */
function readUpload(): string
{
    $file = $_FILES['file'] ?? null;

    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException(t('data.import.no_file'));
    }
    if ((int) $file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException(t('data.import.too_large'));
    }
    if (!is_uploaded_file((string) $file['tmp_name'])) {
        throw new RuntimeException(t('data.import.no_file'));
    }

    $content = file_get_contents((string) $file['tmp_name']);
    if ($content === false) {
        throw new RuntimeException(t('data.import.no_file'));
    }

    return $content;
}

echo $view->page('admin/data', [
    'title' => t('admin.nav.data'),
    'messages' => AdminPage::takeFlash(),
    'coaches' => $coaches,
    'preview' => $preview,
    'importError' => $importError,
]);
