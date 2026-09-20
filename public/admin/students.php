<?php

declare(strict_types=1);

/**
 * Students and their coach assignments.
 *
 * Students are their own list rather than being nested under a coach, because
 * one student may work with several coaches. Creating one checks for an
 * existing record of the same name first, so two different people do not get
 * silently merged into one counter.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\AdminPage;
use App\Contexts;
use App\Csrf;
use App\Str;
use App\Web;

[$vars, $view] = AdminPage::start('students');

$editing = AdminPage::id('id');
$formError = '';
$duplicateOf = null;

if (Web::isPost()) {
    Csrf::verify();
    $action = Web::stringParam('action', $_POST);
    $id = AdminPage::id('id', $_POST);
    $coachId = AdminPage::id('coach_id', $_POST);

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $existing = Contexts::findByName($name, Contexts::STUDENT);

        if (Str::length($name) < 2) {
            $formError = t('student.error.name');
        } elseif ($existing !== null && !isset($_POST['confirm_existing'])) {
            // Same name, different person? Let the operator decide.
            $duplicateOf = $existing;
            $formError = t('student.error.duplicate', ['name' => $name]);
        } else {
            $studentId = $existing !== null && isset($_POST['confirm_existing'])
                ? (int) $existing['id']
                : Contexts::createStudent(Str::truncate($name, 120, ''));

            if ($coachId !== null) {
                Contexts::link($coachId, $studentId);
            }

            AdminPage::flash('success', t('student.created', ['name' => $name]));
            Web::redirect('students.php?id=' . $studentId);
        }
    }

    if ($action === 'save' && $id !== null) {
        $name = trim((string) ($_POST['name'] ?? ''));
        if (Str::length($name) < 2) {
            $formError = t('student.error.name');
            $editing = $id;
        } else {
            Contexts::update($id, [
                'name' => Str::truncate($name, 120, ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ]);
            AdminPage::flash('success', t('student.saved'));
            Web::redirect('students.php?id=' . $id);
        }
    }

    if ($action === 'link' && $id !== null && $coachId !== null) {
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        Contexts::link($coachId, $id, $displayName === '' ? null : Str::truncate($displayName, 120, ''));

        if (Contexts::coachCountFor($id) > 1) {
            AdminPage::flash('info', t('student.shared_warning'));
        }
        AdminPage::flash('success', t('student.linked'));
        Web::redirect('students.php?id=' . $id);
    }

    if ($action === 'unlink' && $id !== null && $coachId !== null) {
        Contexts::unlink($coachId, $id);
        AdminPage::flash('success', t('student.unlinked'));
        Web::redirect('students.php?id=' . $id);
    }

    if ($action === 'toggle-link' && $id !== null && $coachId !== null) {
        Contexts::setLinkActive($coachId, $id, Web::stringParam('state', $_POST) === '1');
        Web::redirect('students.php?id=' . $id);
    }

    if ($action === 'delete' && $id !== null) {
        if (Contexts::isUnused($id)) {
            Contexts::delete($id);
            AdminPage::flash('success', t('student.deleted'));
        } else {
            AdminPage::flash('danger', t('student.error.in_use'));
        }
        Web::redirect('students.php');
    }
}

$coaches = Contexts::coaches(false);

if ($editing !== null) {
    $student = Contexts::find($editing);

    if ($student === null || $student['kind'] !== Contexts::STUDENT) {
        http_response_code(404);
        echo $view->page('error', [
            'title' => t('student.error.missing'),
            'heading' => t('student.error.missing'),
            'body' => t('student.error.missing'),
            'linkHref' => 'students.php',
            'linkLabel' => t('admin.nav.students'),
        ]);
        exit;
    }

    $assigned = Contexts::coachesOf($editing);
    $assignedIds = array_map(static fn (array $c): int => (int) $c['id'], $assigned);

    echo $view->page('admin/student-edit', [
        'title' => (string) $student['name'],
        'messages' => AdminPage::takeFlash(),
        'student' => $student,
        'assigned' => $assigned,
        'available' => array_values(array_filter(
            $coaches,
            static fn (array $c): bool => !in_array((int) $c['id'], $assignedIds, true),
        )),
        'formError' => $formError,
        'canDelete' => Contexts::isUnused($editing),
    ]);
    exit;
}

$rows = [];
foreach (Contexts::students() as $student) {
    $rows[] = $student + ['coaches' => Contexts::coachesOf((int) $student['id'])];
}

echo $view->page('admin/students', [
    'title' => t('admin.nav.students'),
    'messages' => AdminPage::takeFlash(),
    'students' => $rows,
    'coaches' => $coaches,
    'formError' => $formError,
    'duplicateOf' => $duplicateOf,
    'submittedName' => trim((string) ($_POST['name'] ?? '')),
    'submittedCoach' => AdminPage::id('coach_id', $_POST),
]);
