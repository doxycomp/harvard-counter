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

    /** @return array<string, mixed>|null */
    public static function findCoachByToken(string $token): ?array
    {
        $context = self::findByToken($token);

        return $context !== null && $context['kind'] === self::COACH ? $context : null;
    }

    /**
     * The active coach or student an access link belongs to.
     *
     * @return array<string, mixed>|null
     */
    public static function findByToken(string $token): ?array
    {
        if (!Token::looksValid($token)) {
            return null;
        }

        return Db::fetchOne(
            'SELECT * FROM contexts WHERE access_token = ? AND is_active = 1',
            [$token],
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::fetchOne('SELECT * FROM contexts WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByName(string $name, string $kind): ?array
    {
        return Db::fetchOne(
            'SELECT * FROM contexts WHERE name = ? AND kind = ?',
            [$name, $kind],
        );
    }

    /** @return list<array<string, mixed>> the coaches, ordered for display */
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
     *
     * @return list<array<string, mixed>>
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
     * @param array<string, mixed> $coach
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

    /**
     * Ids the coach's roll-up sums over: their own row plus their students.
     *
     * @return list<int>
     */
    public static function rollupIds(int $coachId): array
    {
        $ids = [$coachId];
        foreach (self::studentsOf($coachId) as $student) {
            $ids[] = (int) $student['id'];
        }

        return $ids;
    }

    /**
     * Guard for anything arriving from a request.
     *
     * @param array<string, mixed> $coach
     */
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

    /**
     * Update a context row from a whitelist of columns, so a request can never
     * reach a column it has no business touching (access_token above all).
     *
     * @param array<string, mixed> $fields
     */
    public static function update(int $id, array $fields): void
    {
        $allowed = [
            'name', 'sort_order', 'is_active', 'locale', 'theme', 'color_mode',
            'default_collection_id', 'fmt_header', 'fmt_line', 'fmt_footer',
            'fmt_codeblock', 'fmt_codeblock_lang',
        ];

        $set = array_intersect_key($fields, array_flip($allowed));
        if ($set === []) {
            return;
        }

        $assignments = implode(', ', array_map(
            static fn(string $column): string => "{$column} = ?",
            array_keys($set),
        ));

        Db::query(
            "UPDATE contexts SET {$assignments}, updated_at = NOW() WHERE id = ?",
            [...array_values($set), $id],
        );
    }

    /** @return list<array<string, mixed>> students with their coach assignments */
    public static function students(bool $activeOnly = false): array
    {
        return Db::fetchAll(
            'SELECT * FROM contexts WHERE kind = ?'
            . ($activeOnly ? ' AND is_active = 1' : '')
            . ' ORDER BY name',
            [self::STUDENT],
        );
    }

    /**
     * Every student assigned to a coach, including inactive ones and inactive
     * assignments — the management view, unlike studentsOf() which feeds the
     * dropdown.
     *
     * @return list<array<string, mixed>>
     */
    public static function studentsLinkedTo(int $coachId): array
    {
        return Db::fetchAll(
            'SELECT c.* FROM contexts c
             JOIN context_links l ON l.student_id = c.id
             WHERE l.coach_id = ? AND c.kind = ?
             ORDER BY c.name',
            [$coachId, self::STUDENT],
        );
    }

    /** @return list<array<string, mixed>> the coaches a student is assigned to */
    public static function coachesOf(int $studentId): array
    {
        return Db::fetchAll(
            'SELECT c.*, l.display_name, l.is_active AS link_active
             FROM context_links l
             JOIN contexts c ON c.id = l.coach_id
             WHERE l.student_id = ?
             ORDER BY c.name',
            [$studentId],
        );
    }

    public static function unlink(int $coachId, int $studentId): void
    {
        Db::query(
            'DELETE FROM context_links WHERE coach_id = ? AND student_id = ?',
            [$coachId, $studentId],
        );
    }

    /** Rename a student under one coach only; null falls back to their own name. */
    public static function setDisplayName(int $coachId, int $studentId, ?string $displayName): void
    {
        Db::query(
            'UPDATE context_links SET display_name = ? WHERE coach_id = ? AND student_id = ?',
            [$displayName, $coachId, $studentId],
        );
    }

    public static function setLinkActive(int $coachId, int $studentId, bool $active): void
    {
        Db::query(
            'UPDATE context_links SET is_active = ? WHERE coach_id = ? AND student_id = ?',
            [$active ? 1 : 0, $coachId, $studentId],
        );
    }

    /** How many students a coach has, for the overview. */
    public static function studentCount(int $coachId): int
    {
        return (int) Db::fetchValue(
            'SELECT COUNT(*) FROM context_links WHERE coach_id = ?',
            [$coachId],
        );
    }

    /** True when nothing has ever been counted for this context. */
    public static function isUnused(int $contextId): bool
    {
        $counted = (int) Db::fetchValue(
            'SELECT COUNT(*) FROM usage_counts WHERE context_id = ? AND uses > 0',
            [$contextId],
        );
        $events = (int) Db::fetchValue(
            'SELECT COUNT(*) FROM usage_events WHERE context_id = ? OR coach_id = ?',
            [$contextId, $contextId],
        );

        return $counted === 0 && $events === 0;
    }

    public static function delete(int $contextId): void
    {
        Db::query('DELETE FROM contexts WHERE id = ?', [$contextId]);
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

    /** Issue a new link for a coach or student; any previous one stops working. */
    public static function rotateToken(int $contextId): string
    {
        $token = self::freshToken();
        Db::query(
            'UPDATE contexts SET access_token = ?, updated_at = NOW() WHERE id = ?',
            [$token, $contextId],
        );

        return $token;
    }

    /**
     * Take a student's link away. Coaches always keep one — without it they
     * could not reach the frontend at all — so they are rotated instead.
     */
    public static function revokeToken(int $studentId): void
    {
        Db::query(
            'UPDATE contexts SET access_token = NULL, updated_at = NOW() WHERE id = ? AND kind = ?',
            [$studentId, self::STUDENT],
        );
    }

    /** Whether a student is assigned to this coach at all. */
    public static function isLinked(int $coachId, int $studentId): bool
    {
        return Db::fetchValue(
            'SELECT 1 FROM context_links WHERE coach_id = ? AND student_id = ?',
            [$coachId, $studentId],
        ) !== null;
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
