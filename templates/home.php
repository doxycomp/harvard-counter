<?php

declare(strict_types=1);

use App\View;

/**
 * @var View     $view
 * @var App\Visitor $visitor
 * @var array    $collection
 * @var array    $collections
 * @var string   $collectionName
 * @var string   $itemLabel
 * @var int      $itemCount
 * @var array|null $result
 * @var int|null $number
 * @var string   $formError
 * @var array    $countsByItemNo
 * @var int[]    $leastUsed
 * @var int      $lowestCount
 * @var string|null $demoNotice
 * @var string|null $flash
 * @var callable $link
 * @var string|null $carryToken
 * @var string|null $coachTotalLabel
 * @var int      $studentCoachCount
 */
?>
<h1><?= e(t('home.title')) ?></h1>

<?php if ($demoNotice !== null): ?>
    <div class="notice">
        <p><?= e($demoNotice) ?></p>
    </div>
<?php endif; ?>

<?php if ($flash === 'not_counted'): ?>
    <div class="notice notice--success">
        <p><?= e(t('result.not_counted')) ?></p>
    </div>
<?php endif; ?>

<?= $view->render('partials/picker', [
    'visitor' => $visitor,
    'collection' => $collection,
    'collections' => $collections,
    'itemCount' => $itemCount,
    'itemLabel' => $itemLabel,
    'number' => $number,
    'formError' => $formError,
    'leastUsed' => $leastUsed,
    'lowestCount' => $lowestCount,
    'link' => $link,
    'carryToken' => $carryToken,
]) ?>

<?php if ($result !== null): ?>
    <?= $view->render('partials/result', [
        'visitor' => $visitor,
        'result' => $result,
        'itemLabel' => $itemLabel,
        'collectionName' => $collectionName,
        'coachTotalLabel' => $coachTotalLabel,
        'studentCoachCount' => $studentCoachCount,
    ]) ?>
<?php endif; ?>

<?= $view->render('partials/overview', [
    'countsByItemNo' => $countsByItemNo,
    'itemLabel' => $itemLabel,
    'number' => $number,
    'link' => $link,
]) ?>
