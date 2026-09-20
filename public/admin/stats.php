<?php

declare(strict_types=1);

/**
 * Statistics per coach.
 *
 * The figures come from usage_events, which keeps the teaching coach, so this
 * answers "what did I do" even though the student counters themselves are
 * shared between a student's coaches.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\AdminPage;
use App\Collections;
use App\Contexts;
use App\Stats;

[$vars, $view] = AdminPage::start('stats');

$coaches = Contexts::coaches(false);
$collections = Collections::active();

$coachId = AdminPage::id('coach') ?? (isset($coaches[0]) ? (int) $coaches[0]['id'] : null);
$coach = $coachId === null ? null : Contexts::find($coachId);

if ($coach !== null && $coach['kind'] !== Contexts::COACH) {
    $coach = null;
}

$collectionId = AdminPage::id('c');
$collection = $collectionId === null
    ? ($collections[0] ?? null)
    : (Collections::find($collectionId) ?? ($collections[0] ?? null));

$data = null;

if ($coach !== null && $collection !== null) {
    $counts = Stats::itemCounts((int) $coach['id'], (int) $collection['id']);
    $monthly = Stats::monthly((int) $coach['id']);

    $data = [
        'summary' => Stats::summaryForCoach((int) $coach['id']),
        'monthly' => $monthly,
        'monthlyPeak' => $monthly === [] ? 0 : max($monthly),
        'byStudent' => Stats::byStudent((int) $coach['id']),
        'top' => Stats::extremes($counts, 10, false),
        'bottom' => Stats::extremes($counts, 10, true),
        'itemLabel' => Collections::itemLabel($collection, $vars['locale']),
    ];
}

echo $view->page('admin/stats', [
    'title' => t('admin.nav.stats'),
    'messages' => AdminPage::takeFlash(),
    'coaches' => $coaches,
    'collections' => $collections,
    'coach' => $coach,
    'collection' => $collection,
    'data' => $data,
]);
