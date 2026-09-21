<?php

declare(strict_types=1);

use App\Csrf;

/**
 * @var list<array<string, mixed>> $students
 * @var list<array<string, mixed>> $coaches   empty for a coach account
 * @var bool       $isAdmin
 * @var list<array{type:string, text:string}> $messages
 * @var string     $formError
 * @var array<string, mixed>|null $duplicateOf
 * @var string     $submittedName
 * @var int|null   $submittedCoach
 */
?>
<h1><?= e(t('admin.nav.students')) ?></h1>

<?php foreach ($messages as $message): ?>
    <div class="notice notice--<?= e($message['type']) ?>"><p><?= e($message['text']) ?></p></div>
<?php endforeach; ?>

<section class="card">
    <h2 style="margin-top:0"><?= e(t('student.add')) ?></h2>

    <?php if ($formError !== ''): ?>
        <div class="notice notice--danger"><p><?= e($formError) ?></p></div>
    <?php endif; ?>

    <form method="post" class="inline-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="create">

        <div class="field">
            <label for="new-name"><?= e(t('student.name')) ?></label>
            <input type="text" id="new-name" name="name" maxlength="120" required
                   value="<?= e($submittedName) ?>">
        </div>

        <?php if ($isAdmin): ?>
            <div class="field">
                <label for="new-coach"><?= e(t('student.coach')) ?></label>
                <select id="new-coach" name="coach_id">
                    <option value=""><?= e(t('student.coach.none')) ?></option>
                    <?php foreach ($coaches as $coach): ?>
                        <option value="<?= (int) $coach['id'] ?>"
                            <?= $submittedCoach === (int) $coach['id'] ? 'selected' : '' ?>>
                            <?= e((string) $coach['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($duplicateOf !== null): ?>
            <div class="field">
                <label class="checkbox">
                    <input type="checkbox" name="confirm_existing" value="1">
                    <?= e(t('student.duplicate.use_existing', ['name' => (string) $duplicateOf['name']])) ?>
                </label>
                <p class="field__hint"><?= e(t('student.duplicate.hint')) ?></p>
            </div>
        <?php endif; ?>

        <div class="button-row">
            <button type="submit"><?= e(t('student.add')) ?></button>
        </div>
    </form>
</section>

<?php if ($students === []): ?>
    <div class="card"><p class="muted"><?= e(t('student.none')) ?></p></div>
<?php else: ?>
    <div class="card">
        <table class="data-table">
            <thead>
            <tr>
                <th><?= e(t('student.name')) ?></th>
                <?php if ($isAdmin): ?>
                    <th><?= e(t('student.coaches')) ?></th>
                <?php endif; ?>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr<?= (int) $student['is_active'] === 1 ? '' : ' class="is-inactive"' ?>>
                    <td data-label="<?= e(t('student.name')) ?>">
                        <a href="students.php?id=<?= (int) $student['id'] ?>"><?= e((string) $student['name']) ?></a>
                        <?php if ((int) $student['is_active'] !== 1): ?>
                            <span class="badge"><?= e(t('common.inactive')) ?></span>
                        <?php endif; ?>
                        <?php if (($student['access_token'] ?? null) !== null): ?>
                            <span class="badge badge--info"><?= e(t('student.token.badge')) ?></span>
                        <?php endif; ?>
                        <?php if (!$isAdmin && $student['shared']): ?>
                            <span class="badge badge--info"><?= e(t('student.shared')) ?></span>
                        <?php endif; ?>
                    </td>
                    <?php if ($isAdmin): ?>
                        <td data-label="<?= e(t('student.coaches')) ?>">
                            <?php if ($student['coaches'] === []): ?>
                                <span class="muted"><?= e(t('student.coach.none')) ?></span>
                            <?php else: ?>
                                <?= e(implode(', ', array_map(
                                    static fn (array $c): string => (string) ($c['display_name'] ?? '') !== ''
                                        ? $c['name'] . ' (' . $c['display_name'] . ')'
                                        : (string) $c['name'],
                                    $student['coaches'],
                                ))) ?>
                                <?php if ($student['shared']): ?>
                                    <span class="badge badge--info"><?= e(t('student.shared')) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td>
                        <a class="button button--quiet" href="students.php?id=<?= (int) $student['id'] ?>">
                            <?= e(t('common.edit')) ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
