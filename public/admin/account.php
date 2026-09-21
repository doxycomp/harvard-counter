<?php

declare(strict_types=1);

/** The signed-in administrator's own account. */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\AdminPage;
use App\Auth;
use App\Csrf;
use App\Web;

[$vars, $view] = AdminPage::start('account');

// Administrators reach this page from the users page, which stays highlighted.
if (Auth::isAdmin()) {
    $view->share(['navActive' => 'users']);
}

$formError = '';

if (Web::isPost()) {
    Csrf::verify();

    if (Web::stringParam('action', $_POST) === 'password') {
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['password'] ?? '');
        $repeat = (string) ($_POST['password_repeat'] ?? '');
        $user = Auth::user();

        // Re-authenticate rather than trusting the session alone: a borrowed
        // screen should not be enough to take the account over.
        if ($user === null || !Auth::verifyPassword((int) $user['id'], $current)) {
            $formError = t('account.error.current');
        } elseif (($problem = Auth::validatePassword($new, $repeat)) !== null) {
            $formError = t($problem);
        } else {
            Auth::changePassword((int) $user['id'], $new);
            AdminPage::flash('success', t('account.password.changed'));
            Web::redirect('account.php');
        }
    }
}

echo $view->page('admin/account', [
    'title' => t('admin.nav.account'),
    'messages' => AdminPage::takeFlash(),
    'admin' => Auth::user(),
    'formError' => $formError,
]);
