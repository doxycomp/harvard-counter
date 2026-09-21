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
use App\Auth;
use App\Collections;
use App\Contexts;
use App\Stats;

[$vars, $view] = AdminPage::start('stats');

$coaches = Contexts::coaches(false);
$collections = Collections::active();

// "All coaches" is the default and the first option; a coach id narrows it.
// A coach account is always narrowed to itself, whatever the request says.
$coachId = AdminPage::coachScope() ?? AdminPage::id('coach');
$coach = $coachId === null ? null : Contexts::find($coachId);

if ($coach !== null && $coach['kind'] !== Contexts::COACH) {
    $coach = null;
}
$scopeId = $coach === null ? null : (int) $coach['id'];

$collectionId = AdminPage::id('c');
$collection = $collectionId === null
    ? ($collections[0] ?? null)
    : (Collections::find($collectionId) ?? ($collections[0] ?? null));

// One student's self-practice per list. Checked like every other id: a coach
// account only gets its own students, whatever the request names.
$student = null;
$studentId = AdminPage::id('student');
if ($studentId !== null) {
    $student = Contexts::find($studentId);
    if ($student === null || $student['kind'] !== Contexts::STUDENT) {
        $student = null;
    } elseif (!AdminPage::mayAccessContext($studentId)) {
        AdminPage::deny($view);
    }
}

$data = null;

if ($coaches !== [] && $collection !== null) {
    $counts = Stats::itemCounts($scopeId, (int) $collection['id']);
    $monthly = Stats::monthly($scopeId);

    $data = [
        'summary' => Stats::summaryForCoach($scopeId),
        'monthly' => $monthly,
        'monthlyPeak' => $monthly === [] ? 0 : max($monthly),
        'byStudent' => Stats::byStudent($scopeId),
        'selfPractice' => Stats::selfPractice($scopeId),
        'top' => Stats::extremes($counts, 10, false),
        'bottom' => Stats::extremes($counts, 10, true),
        'itemLabel' => Collections::itemLabel($collection, $vars['locale']),
        'breakdown' => $student === null ? null : [
            'student' => $student,
            'rows' => Stats::selfBreakdown((int) $student['id'], (int) $collection['id']),
            'itemCount' => count(Collections::itemNoMap((int) $collection['id'])),
        ],
    ];
}

echo $view->page('admin/stats', [
    'title' => t('admin.nav.stats'),
    'messages' => AdminPage::takeFlash(),
    'coaches' => $coaches,
    'isAdmin' => Auth::isAdmin(),
    'collections' => $collections,
    'coach' => $coach,
    'collection' => $collection,
    'student' => $student,
    'data' => $data,
]);
