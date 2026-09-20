<?php

declare(strict_types=1);

/**
 * Public frontend.
 *
 * Until the sentence picker lands this only reports how far the installation
 * has come, which is also what a fresh checkout needs to show.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Install;
use App\View;
use App\Web;

$vars = Web::boot(['frontend'], '');
$view = new View();
$view->share($vars);

$status = Install::status();

$errorPages = [
    Install::NO_CONFIG => ['error.not_configured', null],
    Install::NO_DATABASE => ['error.db_unreachable', null],
    Install::NO_SCHEMA => ['error.not_installed', 'admin/setup.php'],
    Install::NO_COLLECTIONS => ['error.not_installed', 'admin/setup.php'],
    Install::NO_ADMIN => ['error.not_installed', 'admin/setup.php'],
];

if (isset($errorPages[$status])) {
    [$key, $link] = $errorPages[$status];
    http_response_code(503);
    echo $view->page('error', [
        'title' => t($key . '.title'),
        'heading' => t($key . '.title'),
        'body' => t($key . '.body'),
        'linkHref' => $link,
        'linkLabel' => $link === null ? null : t('error.not_installed.link'),
    ]);
    exit;
}

echo $view->page('home', [
    'title' => t('home.title'),
    'collections' => Install::collectionCount(),
    'items' => Install::itemCount(),
]);
