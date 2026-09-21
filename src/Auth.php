<?php

declare(strict_types=1);

namespace App;

/**
 * Administrator accounts and sign-in.
 *
 * Failed attempts are recorded per IP and throttled, so a leaked username does
 * not turn into an open guessing game.
 */
final class Auth
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_COACH = 'coach';

    private const SESSION_KEY = '_admin_id';

    /** @var array<string, mixed>|null the current request's account */
    private static ?array $user = null;
    private const MIN_PASSWORD_LENGTH = 12;
    private const MAX_ATTEMPTS = 10;
    private const WINDOW_MINUTES = 15;

    public static function minPasswordLength(): int
    {
        return self::MIN_PASSWORD_LENGTH;
    }

    /** Administrators only — coach accounts do not keep the setup page closed. */
    public static function adminCount(): int
    {
        return (int) Db::fetchValue('SELECT COUNT(*) FROM admin_users WHERE role = ?', [self::ROLE_ADMIN]);
    }

    public static function hasAdmin(): bool
    {
        return self::adminCount() > 0;
    }

    public static function createAdmin(string $username, string $password): int
    {
        return self::createUser($username, $password, self::ROLE_ADMIN, null);
    }

    /** @param int|null $coachId required for, and only used by, the coach role */
    public static function createUser(string $username, string $password, string $role, ?int $coachId): int
    {
        return Db::insert(
            'INSERT INTO admin_users (username, password_hash, role, coach_id, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [
                $username,
                self::hash($password),
                $role === self::ROLE_COACH ? self::ROLE_COACH : self::ROLE_ADMIN,
                $role === self::ROLE_COACH ? $coachId : null,
            ],
        );
    }

    /** @return list<array<string, mixed>> every account, with its coach's name */
    public static function users(): array
    {
        return Db::fetchAll(
            'SELECT u.id, u.username, u.role, u.coach_id, u.created_at, u.last_login_at,
                    c.name AS coach_name
             FROM admin_users u
             LEFT JOIN contexts c ON c.id = u.coach_id
             ORDER BY u.role, u.username',
        );
    }

    public static function deleteUser(int $userId): void
    {
        Db::query('DELETE FROM admin_users WHERE id = ?', [$userId]);
    }

    public static function usernameTaken(string $username): bool
    {
        return Db::fetchValue('SELECT 1 FROM admin_users WHERE username = ?', [$username]) !== null;
    }

    public static function attempt(string $username, string $password): bool
    {
        if (self::isThrottled()) {
            return false;
        }

        $user = Db::fetchOne(
            'SELECT id, password_hash, role, coach_id FROM admin_users WHERE username = ?',
            [$username],
        );

        // Always spend time on a hash comparison so a missing username is not
        // distinguishable from a wrong password by response time.
        $hash = $user['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$aW52YWxpZHNhbHQ$aW52YWxpZA';
        $ok = password_verify($password, $hash) && $user !== null && self::accountUsable($user);

        if (!$ok) {
            self::recordAttempt($username);

            return false;
        }

        if (password_needs_rehash($hash, self::algorithm(), self::options())) {
            Db::query(
                'UPDATE admin_users SET password_hash = ? WHERE id = ?',
                [self::hash($password), (int) $user['id']],
            );
        }

        Session::regenerate();
        Session::set(self::SESSION_KEY, (int) $user['id']);
        Db::query('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?', [(int) $user['id']]);
        self::clearAttempts();

        return true;
    }

    /** Signed in with an account that still exists and is still usable. */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return is_int($id) ? $id : null;
    }

    /**
     * The signed-in account, read fresh on every request so that a changed
     * role, a removed account or a deactivated coach takes effect at once
     * rather than at the next sign-in.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }

        if (self::$user === null || (int) self::$user['id'] !== $id) {
            $user = Db::fetchOne(
                'SELECT id, username, role, coach_id, last_login_at FROM admin_users WHERE id = ?',
                [$id],
            );
            self::$user = $user !== null && self::accountUsable($user) ? $user : null;
        }

        return self::$user;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === self::ROLE_ADMIN;
    }

    /**
     * The coach a coach account is limited to, or null for an administrator,
     * who is not limited at all. Callers must check user() first — null here
     * means "everything", not "nobody".
     */
    public static function coachScope(): ?int
    {
        $user = self::user();
        if ($user === null || $user['role'] === self::ROLE_ADMIN) {
            return null;
        }

        return (int) $user['coach_id'];
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::regenerate();
        self::$user = null;
    }

    /**
     * A coach account is only usable while its coach exists and is active —
     * deactivating a coach cuts their link and their sign-in together.
     *
     * @param array<string, mixed> $user
     */
    private static function accountUsable(array $user): bool
    {
        if (($user['role'] ?? self::ROLE_ADMIN) === self::ROLE_ADMIN) {
            return true;
        }

        if (($user['coach_id'] ?? null) === null) {
            return false;
        }

        return Db::fetchValue(
            'SELECT 1 FROM contexts WHERE id = ? AND kind = ? AND is_active = 1',
            [(int) $user['coach_id'], Contexts::COACH],
        ) !== null;
    }

    /** Confirm a password for an already signed-in account, e.g. before a change. */
    public static function verifyPassword(int $adminId, string $password): bool
    {
        $hash = Db::fetchValue('SELECT password_hash FROM admin_users WHERE id = ?', [$adminId]);

        return is_string($hash) && password_verify($password, $hash);
    }

    public static function changePassword(int $adminId, string $password): void
    {
        Db::query(
            'UPDATE admin_users SET password_hash = ? WHERE id = ?',
            [self::hash($password), $adminId],
        );
    }

    /** @return string|null a translation key describing why, or null when fine */
    public static function validatePassword(string $password, string $repeat): ?string
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return 'setup.admin.error.password_short';
        }
        if (!hash_equals($password, $repeat)) {
            return 'setup.admin.error.password_mismatch';
        }

        return null;
    }

    /**
     * Reads the attempt log, which attempt() writes to — so two calls around
     * a failed sign-in can disagree, and that is how the tenth attempt flips
     * into the throttle.
     *
     * @phpstan-impure
     */
    public static function isThrottled(): bool
    {
        $recent = (int) Db::fetchValue(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)',
            [Web::clientIp(), self::WINDOW_MINUTES],
        );

        return $recent >= self::MAX_ATTEMPTS;
    }

    private static function recordAttempt(string $username): void
    {
        Db::query(
            'INSERT INTO login_attempts (ip, username, attempted_at) VALUES (?, ?, NOW())',
            [Web::clientIp(), $username],
        );
    }

    private static function clearAttempts(): void
    {
        Db::query('DELETE FROM login_attempts WHERE ip = ?', [Web::clientIp()]);
    }

    private static function hash(string $password): string
    {
        return password_hash($password, self::algorithm(), self::options());
    }

    private static function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }

    /** @return array<string, int> */
    private static function options(): array
    {
        return defined('PASSWORD_ARGON2ID')
            ? ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1]
            : [];
    }
}
