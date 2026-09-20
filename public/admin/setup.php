<?php

declare(strict_types=1);

/**
 * One-time setup: create the schema, import collections, create the first
 * administrator.
 *
 * While no administrator exists the page is reachable with the setup_token
 * from config/config.php. Afterwards it locks itself and only opens for a
 * signed-in administrator, which is also how later migrations are applied on
 * a server without shell access.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Auth;
use App\CollectionImporter;
use App\Config;
use App\Csrf;
use App\Db;
use App\Install;
use App\Migrator;
use App\Session;
use App\Str;
use App\View;
use App\Web;

$vars = Web::boot(['frontend', 'admin'], '../');
$view = new View();
$view->share($vars);

/** Render a standalone error page and stop. */
$fail = static function (string $key) use ($view): never {
    http_response_code(503);
    echo $view->page('error', [
        'title' => t($key . '.title'),
        'heading' => t($key . '.title'),
        'body' => t($key . '.body'),
    ]);
    exit;
};

if (!Config::exists()) {
    $fail('error.not_configured');
}
if (!Db::isReachable()) {
    $fail('error.db_unreachable');
}

$setupOpen = Install::setupIsOpen();
$unlocked = (bool) Session::get('_setup_unlocked', false);

// Once an administrator exists, only a signed-in administrator may continue.
if (!$setupOpen) {
    $unlocked = Auth::check();
    if (!$unlocked) {
        http_response_code(403);
        echo $view->page('error', [
            'title' => t('setup.locked.title'),
            'heading' => t('setup.locked.title'),
            'body' => t('setup.locked.body'),
            'linkHref' => 'index.php',
            'linkLabel' => t('login.title'),
        ]);
        exit;
    }
}

$configuredToken = (string) Config::get('setup_token', '');
$tokenConfigured = $configuredToken !== '';
$tokenError = '';
$adminError = '';
$formUsername = '';
$messages = [];

if (Web::isPost()) {
    Csrf::verify();
    $action = Web::stringParam('action', $_POST);

    if ($action === 'unlock' && $setupOpen) {
        $supplied = (string) (Web::stringParam('setup_token', $_POST) ?? '');
        if ($tokenConfigured && hash_equals($configuredToken, $supplied)) {
            Session::set('_setup_unlocked', true);
            Web::redirect('setup.php');
        }
        $tokenError = t('setup.token.error');
    }

    if ($unlocked && $action === 'migrate') {
        try {
            $applied = (new Migrator())->migrate();
            $messages[] = [
                'type' => 'success',
                'text' => tn('setup.schema.applied', count($applied)),
            ];
        } catch (Throwable $e) {
            $messages[] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
    }

    if ($unlocked && $action === 'import') {
        try {
            $result = (new CollectionImporter())->importAll();
            $messages[] = [
                'type' => 'success',
                'text' => t('setup.collections.imported', [
                    'collections' => $result['collections'],
                    'items' => $result['items'],
                    'lines' => $result['lines'],
                ]),
            ];
        } catch (Throwable $e) {
            $messages[] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
    }

    if ($unlocked && $action === 'create-admin' && !Auth::hasAdmin()) {
        $formUsername = (string) (Web::stringParam('username', $_POST) ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $repeat = (string) ($_POST['password_repeat'] ?? '');

        if (Str::length($formUsername) < 3) {
            $adminError = t('setup.admin.error.username');
        } elseif (($problem = Auth::validatePassword($password, $repeat)) !== null) {
            $adminError = t($problem);
        } else {
            Auth::createAdmin($formUsername, $password);
            Session::forget('_setup_unlocked');
            $messages[] = ['type' => 'success', 'text' => t('setup.done.body')];
        }
    }
}

echo $view->page('admin/setup', [
    'title' => t('setup.title'),
    'unlocked' => $unlocked,
    'tokenConfigured' => $tokenConfigured,
    'tokenError' => $tokenError,
    'messages' => $messages,
    'pending' => Install::pendingMigrations(),
    'collections' => Install::collectionCount(),
    'items' => Install::itemCount(),
    'hasAdmin' => Install::schemaPresent() && Auth::hasAdmin(),
    'formUsername' => $formUsername,
    'adminError' => $adminError,
]);
