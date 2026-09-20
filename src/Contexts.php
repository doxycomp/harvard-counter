<?php

declare(strict_types=1);

namespace App;

/**
 * Coaches and students.
 *
 * Both live in the contexts table because both are things a counter can hang
 * off and both appear in the same dropdown. Their relationship is n:m, since a
 * student may work with several coaches.
 */
final class Contexts
{
    public const COACH = 'coach';
    public const STUDENT = 'student';

    public static function findCoachByToken(string $token): ?array
    {
        if (!Token::looksValid($token)) {
            return null;
        }

        return Db::fetchOne(
            'SELECT * FROM contexts
             WHERE access_token = ? AND kind = ? AND is_active = 1',
            [$token, self::COACH],
        );
    }

    public static function find(int $id): ?array
    {
        return Db::fetchOne('SELECT * FROM contexts WHERE id = ?', [$id]);
    }

    public static function findByName(string $name, string $kind): ?array
    {
        return Db::fetchOne(
            'SELECT * FROM contexts WHERE name = ? AND kind = ?',
            [$name, $kind],
        );
    }

    /** @return array<int, array> the coaches, ordered for display */
    public static function coaches(bool $activeOnly = true): array
    {
        return Db::fetchAll(
            'SELECT * FROM contexts WHERE kind = ?'
            . ($activeOnly ? ' AND is_active = 1' : '')
            . ' ORDER BY sort_order, name',
            [self::COACH],
        );
    }

    /**
     * The students assigned to a coach and active in that assignment, using
     * the display name of the link when one is set.
     */
    public static function studentsOf(int $coachId): array
    {
        return Db::fetchAll(
            'SELECT c.*, COALESCE(l.display_name, c.name) AS label
             FROM context_links l
             JOIN contexts c ON c.id = l.student_id
             WHERE l.coach_id = ? AND l.is_active = 1 AND c.is_active = 1
             ORDER BY l.sort_order, label',
            [$coachId],
        );
    }

    /**
     * What the dropdown offers: the coach's own row first, then their
     * students. The coach row is what counts a session that is not attributed
     * to a named student.
     *
     * @return array<int, array{id:int, label:string, kind:string}>
     */
    public static function selectableFor(array $coach): array
    {
        $options = [[
            'id' => (int) $coach['id'],
            'label' => (string) $coach['name'],
            'kind' => self::COACH,
        ]];

        foreach (self::studentsOf((int) $coach['id']) as $student) {
            $options[] = [
                'id' => (int) $student['id'],
                'label' => (string) $student['label'],
                'kind' => self::STUDENT,
            ];
        }

        return $options;
    }

    /** Ids the coach's roll-up sums over: their own row plus their students. */
    public static function rollupIds(int $coachId): array
    {
        $ids = [$coachId];
        foreach (self::studentsOf($coachId) as $student) {
            $ids[] = (int) $student['id'];
        }

        return $ids;
    }

    /** Guard for anything arriving from a request. */
    public static function isSelectable(array $coach, int $contextId): bool
    {
        foreach (self::selectableFor($coach) as $option) {
            if ($option['id'] === $contextId) {
                return true;
            }
        }

        return false;
    }

    public static function createCoach(string $name, ?string $locale = null): int
    {
        $format = Settings::defaultFormat($locale ?? 'en');

        return Db::insert(
            'INSERT INTO contexts
                (kind, name, access_token, locale, fmt_header, fmt_line, fmt_footer,
                 created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                self::COACH,
                $name,
                self::freshToken(),
                $locale,
                $format['header'],
                $format['line'],
                $format['footer'],
            ],
        );
    }

    public static function createStudent(string $name): int
    {
        return Db::insert(
            'INSERT INTO contexts (kind, name, created_at, updated_at)
             VALUES (?, ?, NOW(), NOW())',
            [self::STUDENT, $name],
        );
    }

    public static function link(int $coachId, int $studentId, ?string $displayName = null): void
    {
        Db::query(
            'INSERT INTO context_links (coach_id, student_id, display_name, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), is_active = 1',
            [$coachId, $studentId, $displayName],
        );
    }

    /** How many coaches a student is assigned to — drives the shared-counter note. */
    public static function coachCountFor(int $studentId): int
    {
        return (int) Db::fetchValue(
            'SELECT COUNT(*) FROM context_links WHERE student_id = ?',
            [$studentId],
        );
    }

    public static function rotateToken(int $coachId): string
    {
        $token = self::freshToken();
        Db::query(
            'UPDATE contexts SET access_token = ?, updated_at = NOW() WHERE id = ? AND kind = ?',
            [$token, $coachId, self::COACH],
        );

        return $token;
    }

    /** A token that is not already in use. */
    private static function freshToken(): string
    {
        do {
            $token = Token::generate();
            $taken = Db::fetchValue('SELECT 1 FROM contexts WHERE access_token = ?', [$token]);
        } while ($taken !== null);

        return $token;
    }
}
