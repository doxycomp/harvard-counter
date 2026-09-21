<?php

declare(strict_types=1);

/**
 * Students, their coach assignments and their own access links.
 *
 * Students are their own list rather than being nested under a coach, because
 * one student may work with several coaches. An administrator sees all of
 * them; a coach account sees only the students assigned to its coach.
 *
 * What a coach account may change is deliberately narrower than what it may
 * see: renaming or deleting a student shared with another coach would change
 * that other coach's view as well, so those stay with the administrator, as
 * does sharing a student with a further coach in the first place.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\AdminPage;
use App\Auth;
use App\Contexts;
use App\Csrf;
use App\Str;
use App\Web;

[$vars, $view] = AdminPage::start('students');

$isAdmin = Auth::isAdmin();
$scope = AdminPage::coachScope();

/** May this account look at and work with this student at all? */
$mayManage = static fn(int $studentId): bool => $isAdmin
    || ($scope !== null && Contexts::isLinked($scope, $studentId));

/** May it change things about the student that every coach of theirs sees? */
$ownsAlone = static fn(int $studentId): bool => $isAdmin
    || ($mayManage($studentId) && Contexts::coachCountFor($studentId) === 1);

/** A coach account may only touch its own assignment. */
$mayTouchLink = static fn(int $coachId): bool => $isAdmin || $coachId === $scope;

$editing = AdminPage::id('id');
$formError = '';
$duplicateOf = null;

if ($editing !== null) {
    $student = Contexts::find($editing);
    if ($student === null || $student['kind'] !== Contexts::STUDENT || !$mayManage($editing)) {
        AdminPage::deny($view);
    }
}

if (Web::isPost()) {
    Csrf::verify();
    $action = Web::stringParam('action', $_POST);
    $id = AdminPage::id('id', $_POST);
    $coachId = AdminPage::id('coach_id', $_POST);

    // Every action on an existing student needs a student this account may see.
    if ($id !== null) {
        $target = Contexts::find($id);
        if ($target === null || $target['kind'] !== Contexts::STUDENT || !$mayManage($id)) {
            AdminPage::deny($view);
        }
    }

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));

        // A coach account always creates a new person. Checking the name
        // against existing students would reveal other coaches' students, and
        // sharing a student between coaches is an administrator's decision.
        $existing = $isAdmin ? Contexts::findByName($name, Contexts::STUDENT) : null;

        if (Str::length($name) < 2) {
            $formError = t('student.error.name');
        } elseif ($existing !== null && !isset($_POST['confirm_existing'])) {
            $duplicateOf = $existing;
            $formError = t('student.error.duplicate', ['name' => $name]);
        } else {
            $studentId = $existing !== null
                ? (int) $existing['id']
                : Contexts::createStudent(Str::truncate($name, 120, ''));

            $linkTo = $isAdmin ? $coachId : $scope;
            if ($linkTo !== null) {
                Contexts::link($linkTo, $studentId);
            }

            AdminPage::flash('success', t('student.created', ['name' => $name]));
            Web::redirect('students.php?id=' . $studentId);
        }
    }

    if ($action === 'save' && $id !== null) {
        if (!$ownsAlone($id)) {
            AdminPage::deny($view);
        }

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
        // Sharing a student with a further coach: administrators only.
        if (!$isAdmin) {
            AdminPage::deny($view);
        }

        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        Contexts::link($coachId, $id, $displayName === '' ? null : Str::truncate($displayName, 120, ''));

        if (Contexts::coachCountFor($id) > 1) {
            AdminPage::flash('info', t('student.shared_warning'));
        }
        AdminPage::flash('success', t('student.linked'));
        Web::redirect('students.php?id=' . $id);
    }

    if ($action === 'rename-link' && $id !== null && $coachId !== null) {
        if (!$mayTouchLink($coachId)) {
            AdminPage::deny($view);
        }

        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        Contexts::setDisplayName(
            $coachId,
            $id,
            $displayName === '' ? null : Str::truncate($displayName, 120, ''),
        );
        AdminPage::flash('success', t('student.display_name.saved'));
        Web::redirect('students.php?id=' . $id);
    }

    if ($action === 'unlink' && $id !== null && $coachId !== null) {
        if (!$mayTouchLink($coachId)) {
            AdminPage::deny($view);
        }

        Contexts::unlink($coachId, $id);
        AdminPage::flash('success', t('student.unlinked'));
        // A coach who let a student go can no longer see them.
        Web::redirect($isAdmin ? 'students.php?id=' . $id : 'students.php');
    }

    if ($action === 'toggle-link' && $id !== null && $coachId !== null) {
        if (!$mayTouchLink($coachId)) {
            AdminPage::deny($view);
        }

        Contexts::setLinkActive($coachId, $id, Web::stringParam('state', $_POST) === '1');
        Web::redirect('students.php?id=' . $id);
    }

    if (in_array($action, ['token-create', 'token-rotate'], true) && $id !== null) {
        Contexts::rotateToken($id);
        AdminPage::flash('success', t($action === 'token-create' ? 'student.token.created' : 'student.token.rotated'));
        Web::redirect('students.php?id=' . $id);
    }

    if ($action === 'token-revoke' && $id !== null) {
        Contexts::revokeToken($id);
        AdminPage::flash('success', t('student.token.revoked'));
        Web::redirect('students.php?id=' . $id);
    }

    if ($action === 'delete' && $id !== null) {
        if (!$ownsAlone($id)) {
            AdminPage::deny($view);
        }

        if (Contexts::isUnused($id)) {
            Contexts::delete($id);
            AdminPage::flash('success', t('student.deleted'));
        } else {
            AdminPage::flash('danger', t('student.error.in_use'));
        }
        Web::redirect('students.php');
    }
}

$coaches = $isAdmin ? Contexts::coaches(false) : [];

if ($editing !== null) {
    $student = Contexts::find($editing);
    $assigned = Contexts::coachesOf($editing);
    $assignedIds = array_map(static fn(array $c): int => (int) $c['id'], $assigned);
    $sharedCount = count($assigned);

    // A coach account sees its own assignment only — not who else teaches
    // this student.
    if (!$isAdmin) {
        $assigned = array_values(array_filter(
            $assigned,
            static fn(array $c): bool => (int) $c['id'] === $scope,
        ));
    }

    echo $view->page('admin/student-edit', [
        'title' => (string) $student['name'],
        'messages' => AdminPage::takeFlash(),
        'student' => $student,
        'assigned' => $assigned,
        'sharedCount' => $sharedCount,
        'available' => array_values(array_filter(
            $coaches,
            static fn(array $c): bool => !in_array((int) $c['id'], $assignedIds, true),
        )),
        'formError' => $formError,
        'isAdmin' => $isAdmin,
        'canEditBasics' => $ownsAlone($editing),
        'canDelete' => $ownsAlone($editing) && Contexts::isUnused($editing),
    ]);
    exit;
}

$rows = [];
$list = $isAdmin ? Contexts::students() : Contexts::studentsLinkedTo((int) $scope);
foreach ($list as $student) {
    $studentCoaches = Contexts::coachesOf((int) $student['id']);
    $rows[] = $student + [
        // Other coaches' names are an administrator's view only.
        'coaches' => $isAdmin ? $studentCoaches : [],
        'shared' => count($studentCoaches) > 1,
    ];
}

echo $view->page('admin/students', [
    'title' => t('admin.nav.students'),
    'messages' => AdminPage::takeFlash(),
    'students' => $rows,
    'coaches' => $coaches,
    'isAdmin' => $isAdmin,
    'formError' => $formError,
    'duplicateOf' => $duplicateOf,
    'submittedName' => trim((string) ($_POST['name'] ?? '')),
    'submittedCoach' => AdminPage::id('coach_id', $_POST),
]);
