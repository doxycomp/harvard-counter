<?php

declare(strict_types=1);

/**
 * Public frontend: pick a number, get the sentences, count the use.
 *
 * The invariant that keeps counting honest: a GET never counts. Only a POST
 * increments, and it answers with a redirect, so reloading the result or
 * switching context to look something up leaves the counters alone.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Collections;
use App\Contexts;
use App\Csrf;
use App\Formatter;
use App\Install;
use App\Session;
use App\Settings;
use App\View;
use App\Visitor;
use App\Web;

$vars = Web::boot(['frontend'], '');
$view = new View();

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
    $view->share($vars);
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

// ---------------------------------------------------------------- visitor

// A POST submits to the current URL, so these normally arrive in the query
// string; the $_POST fallback keeps the page working if a form is ever moved.
$param = static fn (string $name): ?string
    => Web::stringParam($name) ?? Web::stringParam($name, $_POST);

$token = $param('t');
$requestedContext = $param('ctx');
$visitor = Visitor::resolve($token, $requestedContext === null ? null : (int) $requestedContext);

if ($visitor->coach !== null) {
    $vars = Web::applyContextPreferences(
        $vars,
        $visitor->coach['locale'] ?? null,
        $visitor->coach['theme'] ?? null,
        $visitor->coach['color_mode'] ?? null,
    );
}
$view->share($vars);

// Keep the token in links only when it arrived in the URL; a visitor who is
// recognised by cookie should not have it put back into their address bar.
$carryToken = $token !== null && $visitor->coach !== null ? $token : null;

// ------------------------------------------------------------- collection

$collections = Collections::active();
$collection = Collections::choose(
    $param('c'),
    isset($visitor->coach['default_collection_id'])
        ? (int) $visitor->coach['default_collection_id']
        : null,
);

if ($collection === null) {
    http_response_code(503);
    echo $view->page('error', [
        'title' => t('error.not_installed.title'),
        'heading' => t('error.not_installed.title'),
        'body' => t('error.not_installed.body'),
    ]);
    exit;
}

$collectionId = (int) $collection['id'];
$itemCount = (int) $collection['item_count'];

/** Build a link to this page, carrying the identity bits. */
$link = static function (array $extra = []) use ($carryToken, $collection, $visitor): string {
    $query = [];
    if ($carryToken !== null) {
        $query['t'] = $carryToken;
    }
    $query['c'] = $collection['slug'];
    if ($visitor->selected !== null) {
        $query['ctx'] = $visitor->selected['id'];
    }

    $query = [...$query, ...$extra];

    return '?' . http_build_query($query);
};

// ------------------------------------------------------------------ POST

$formError = '';

if (Web::isPost()) {
    Csrf::verify();
    $action = Web::stringParam('action', $_POST);

    if ($action === 'undo') {
        $pending = Session::get('pending_undo');
        if (is_array($pending) && $visitor->counters->undo((string) $pending['handle'])) {
            Session::forget('pending_undo');
            Session::set('flash', 'not_counted');
        }
        Web::redirect($link(['n' => (int) ($pending['item_no'] ?? 1)]));
    }

    if ($action === 'show') {
        $wantsRandom = isset($_POST['random']);
        $raw = Web::stringParam('n', $_POST);

        if ($wantsRandom) {
            $number = random_int(1, max(1, $itemCount));
        } elseif ($itemCount === 1) {
            $number = 1;
        } else {
            $number = filter_var($raw ?? '', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => max(1, $itemCount)],
            ]);
            $number = $number === false ? null : $number;
        }

        if ($number === null) {
            $formError = t('picker.error.number', ['max' => $itemCount]);
        } else {
            $item = Collections::item($collectionId, $number);
            if ($item === null) {
                $formError = t('picker.error.number', ['max' => $itemCount]);
            } else {
                $handle = $visitor->counters->increment((int) $item['id']);
                if ($handle !== null) {
                    Session::set('pending_undo', [
                        'handle' => $handle,
                        'item_id' => (int) $item['id'],
                        'item_no' => $number,
                        'context_id' => $visitor->selected['id'] ?? 0,
                    ]);
                }
                Session::set('flash', 'counted');
                Web::redirect($link(['n' => $number]));
            }
        }
    }
}

// ------------------------------------------------------------------- GET

$requestedNumber = Web::stringParam('n');
$number = $requestedNumber === null ? null : filter_var($requestedNumber, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => max(1, $itemCount)],
]);
$number = $number === false ? null : $number;

$result = null;

if ($number !== null) {
    $item = Collections::item($collectionId, $number);

    if ($item !== null) {
        $itemId = (int) $item['id'];
        $lines = Collections::lines($itemId);

        $uses = $visitor->counters->uses($itemId);
        $total = $visitor->counters->total($itemId);
        $primary = $visitor->hasStudentSelected() ? $uses : $total;

        $template = Formatter::templateFor($visitor->coach, $vars['locale']);
        $discord = Formatter::render(
            $template,
            [
                'item_no' => $number,
                'count' => $primary,
                'context' => $visitor->selected['label'] ?? '',
                'coach' => $visitor->coachName() ?? '',
                'student' => $visitor->hasStudentSelected() ? $visitor->selected['label'] : '',
                'collection' => Collections::name($collection, $vars['locale']),
                'item_label' => Collections::itemLabel($collection, $vars['locale']),
            ],
            $lines,
            str_contains($template['line'], '{global_no}')
                ? Collections::lineOffset($collectionId, $number)
                : 0,
        );

        $pending = Session::get('pending_undo');
        $canUndo = is_array($pending)
            && (int) $pending['item_id'] === $itemId
            && (int) $pending['context_id'] === (int) ($visitor->selected['id'] ?? 0);

        $result = [
            'item_no' => $number,
            'lines' => $lines,
            'uses' => $uses,
            'total' => $total,
            'primary' => $primary,
            'discord' => $discord,
            'canUndo' => $canUndo,
        ];
    }
}

// ------------------------------------------------- counters for the overview

$itemNoMap = Collections::itemNoMap($collectionId);
$rawCounts = $visitor->counters->allUses(array_keys($itemNoMap));

$countsByItemNo = [];
foreach ($itemNoMap as $id => $no) {
    $countsByItemNo[$no] = $rawCounts[$id] ?? 0;
}
ksort($countsByItemNo);

// The "still open" hint follows the selected level: for a student it answers
// what that student has not had yet.
$leastUsed = [];
if ($countsByItemNo !== []) {
    $lowest = min($countsByItemNo);
    $leastUsed = array_slice(
        array_keys($countsByItemNo, $lowest, true),
        0,
        3,
    );
}

$demoNotice = null;
if ($visitor->isDemo()) {
    $demoNotice = Settings::get('demo.notice.' . $vars['locale'])
        ?? Settings::get('demo.notice.en')
        ?? t('demo.notice');
}

echo $view->page('home', [
    'title' => t('home.title'),
    'visitor' => $visitor,
    'collection' => $collection,
    'collections' => $collections,
    'collectionName' => Collections::name($collection, $vars['locale']),
    'itemLabel' => Collections::itemLabel($collection, $vars['locale']),
    'itemCount' => $itemCount,
    'result' => $result,
    'number' => $number,
    'formError' => $formError,
    'countsByItemNo' => $countsByItemNo,
    'leastUsed' => $leastUsed,
    'lowestCount' => $countsByItemNo === [] ? 0 : min($countsByItemNo),
    'demoNotice' => $demoNotice,
    'flash' => Session::pull('flash'),
    'link' => $link,
    'carryToken' => $carryToken,
    'coachTotalLabel' => $visitor->coachName(),
    'studentCoachCount' => $visitor->hasStudentSelected()
        ? Contexts::coachCountFor((int) $visitor->selected['id'])
        : 0,
]);
