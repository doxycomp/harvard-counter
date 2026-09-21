<?php

declare(strict_types=1);

/**
 * Admin sign-in and overview.
 *
 * Coach and student management, the counter matrix and the statistics land in
 * later milestones; what exists here is the authenticated shell they hang off.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Auth;
use App\Config;
use App\Contexts;
use App\Csrf;
use App\Db;
use App\Install;
use App\View;
use App\Web;

$vars = Web::boot(['frontend', 'admin'], '../');
$view = new View();
$view->share($vars);

if (!Config::exists() || !Db::isReachable() || !Install::schemaPresent()) {
    http_response_code(503);
    echo $view->page('error', [
        'title' => t('error.not_installed.title'),
        'heading' => t('error.not_installed.title'),
        'body' => t('error.not_installed.body'),
        'linkHref' => 'setup.php',
        'linkLabel' => t('error.not_installed.link'),
    ]);
    exit;
}

$loginError = '';

if (Web::isPost()) {
    Csrf::verify();
    $action = Web::stringParam('action', $_POST);

    if ($action === 'signout') {
        Auth::logout();
        Web::redirect('index.php');
    }

    if ($action === 'signin') {
        $username = (string) (Web::stringParam('username', $_POST) ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (Auth::isThrottled()) {
            $loginError = t('login.throttled');
        } elseif (Auth::attempt($username, $password)) {
            Web::redirect('index.php');
        } else {
            $loginError = Auth::isThrottled() ? t('login.throttled') : t('login.error');
        }
    }
}

if (!Auth::check()) {
    echo $view->page('admin/login', [
        'title' => t('login.title'),
        'loginError' => $loginError,
    ]);
    exit;
}

$isAdmin = Auth::isAdmin();
$scope = Auth::coachScope();
$view->share(['adminNav' => true, 'navActive' => 'dashboard', 'isAdmin' => $isAdmin]);

echo $view->page('admin/dashboard', [
    'title' => t('admin.dashboard.title'),
    'admin' => Auth::user(),
    'isAdmin' => $isAdmin,
    'coach' => $scope === null ? null : Contexts::find($scope),
    // Migrations and collections are an administrator's business.
    'pending' => $isAdmin ? Install::pendingMigrations() : [],
    'collections' => Install::collectionCount(),
    'items' => Install::itemCount(),
    'coachCount' => $isAdmin ? count(Contexts::coaches(false)) : 1,
    'studentCount' => $scope === null ? count(Contexts::students()) : Contexts::studentCount($scope),
    'totalUses' => $scope === null
        ? (int) Db::fetchValue('SELECT COUNT(*) FROM usage_events WHERE counted = 1')
        : (int) Db::fetchValue('SELECT COUNT(*) FROM usage_events WHERE counted = 1 AND coach_id = ?', [$scope]),
]);
