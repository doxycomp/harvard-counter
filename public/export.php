<?php

declare(strict_types=1);

/**
 * "Export my data" for a coach holding an access link.
 *
 * A coach can take their counters with them without going through an
 * administrator — the point of the feature is that they could fork the
 * repository and keep running it themselves.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Exporter;
use App\Install;
use App\View;
use App\Visitor;
use App\Web;

$vars = Web::boot(['frontend'], '');
$view = new View();
$view->share($vars);

if (Install::status() !== Install::READY) {
    http_response_code(503);
    echo $view->page('error', [
        'title' => t('error.not_installed.title'),
        'heading' => t('error.not_installed.title'),
        'body' => t('error.not_installed.body'),
    ]);
    exit;
}

$visitor = Visitor::resolve(Web::stringParam('t'), null);

if ($visitor->isDemo()) {
    http_response_code(403);
    echo $view->page('error', [
        'title' => t('export.denied.title'),
        'heading' => t('export.denied.title'),
        'body' => t('export.denied.body'),
        'linkHref' => 'index.php',
        'linkLabel' => t('home.title'),
    ]);
    exit;
}

$coach = $visitor->coach;
$includeEvents = Web::stringParam('events') === '1';
$export = Exporter::exportCoach((int) $coach['id'], $includeEvents);

if (Web::stringParam('format') === 'csv') {
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
