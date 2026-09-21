<?php

declare(strict_types=1);

/**
 * Sign-in accounts: administrators, and coach accounts tied to one coach.
 *
 * Administrators only. Two guard rails: an administrator cannot delete their
 * own account, and the last administrator cannot be deleted at all — either
 * would lock everyone out of the parts only an administrator can reach.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\AdminPage;
use App\Auth;
use App\Contexts;
use App\Csrf;
use App\Db;
use App\Str;
use App\Web;

[$vars, $view] = AdminPage::start('users', adminOnly: true);

$formError = '';
$form = ['username' => '', 'role' => Auth::ROLE_COACH, 'coach_id' => null];

if (Web::isPost()) {
    Csrf::verify();
    $action = Web::stringParam('action', $_POST);
    $userId = AdminPage::id('user_id', $_POST);

    if ($action === 'create') {
        $form = [
            'username' => trim((string) ($_POST['username'] ?? '')),
            'role' => Web::stringParam('role', $_POST) === Auth::ROLE_ADMIN ? Auth::ROLE_ADMIN : Auth::ROLE_COACH,
            'coach_id' => AdminPage::id('coach_id', $_POST),
        ];
        $password = (string) ($_POST['password'] ?? '');
        $repeat = (string) ($_POST['password_repeat'] ?? '');
        $coach = $form['coach_id'] === null ? null : Contexts::find($form['coach_id']);

        if (Str::length($form['username']) < 3) {
            $formError = t('setup.admin.error.username');
        } elseif (Auth::usernameTaken($form['username'])) {
            $formError = t('users.error.taken', ['name' => $form['username']]);
        } elseif ($form['role'] === Auth::ROLE_COACH && ($coach === null || $coach['kind'] !== Contexts::COACH)) {
            $formError = t('users.error.coach');
        } elseif (($problem = Auth::validatePassword($password, $repeat)) !== null) {
            $formError = t($problem);
        } else {
            Auth::createUser(
                Str::truncate($form['username'], 64, ''),
                $password,
                $form['role'],
                $form['coach_id'],
            );
            AdminPage::flash('success', t('users.created', ['name' => $form['username']]));
            Web::redirect('users.php');
        }
    }

    // Setting your own password here would skip the current-password check
    // that the account page asks for.
    if ($action === 'password' && $userId === Auth::id()) {
        Web::redirect('account.php');
    }

    if ($action === 'password' && $userId !== null) {
        $password = (string) ($_POST['password'] ?? '');
        $problem = Auth::validatePassword($password, (string) ($_POST['password_repeat'] ?? ''));

        if ($problem !== null) {
            AdminPage::flash('danger', t($problem));
        } else {
            Auth::changePassword($userId, $password);
            AdminPage::flash('success', t('users.password.changed'));
        }
        Web::redirect('users.php');
    }

    if ($action === 'delete' && $userId !== null) {
        $target = Db::fetchOne('SELECT id, role FROM admin_users WHERE id = ?', [$userId]);

        if ($target === null) {
            Web::redirect('users.php');
        }
        if ($userId === Auth::id()) {
            AdminPage::flash('danger', t('users.error.self'));
        } elseif ($target['role'] === Auth::ROLE_ADMIN && Auth::adminCount() <= 1) {
            AdminPage::flash('danger', t('users.error.last_admin'));
        } else {
            Auth::deleteUser($userId);
            AdminPage::flash('success', t('users.deleted'));
        }
        Web::redirect('users.php');
    }
}

echo $view->page('admin/users', [
    'title' => t('admin.nav.users'),
    'messages' => AdminPage::takeFlash(),
    'users' => Auth::users(),
    'coaches' => Contexts::coaches(false),
    'currentId' => Auth::id(),
    'formError' => $formError,
    'form' => $form,
]);
