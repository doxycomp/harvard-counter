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
    private const SESSION_KEY = '_admin_id';
    private const MIN_PASSWORD_LENGTH = 12;
    private const MAX_ATTEMPTS = 10;
    private const WINDOW_MINUTES = 15;

    public static function minPasswordLength(): int
    {
        return self::MIN_PASSWORD_LENGTH;
    }

    public static function adminCount(): int
    {
        return (int) Db::fetchValue('SELECT COUNT(*) FROM admin_users');
    }

    public static function hasAdmin(): bool
    {
        return self::adminCount() > 0;
    }

    public static function createAdmin(string $username, string $password): int
    {
        return Db::insert(
            'INSERT INTO admin_users (username, password_hash, created_at)
             VALUES (?, ?, NOW())',
            [$username, self::hash($password)],
        );
    }

    public static function attempt(string $username, string $password): bool
    {
        if (self::isThrottled()) {
            return false;
        }

        $user = Db::fetchOne(
            'SELECT id, password_hash FROM admin_users WHERE username = ?',
            [$username],
        );

        // Always spend time on a hash comparison so a missing username is not
        // distinguishable from a wrong password by response time.
        $hash = $user['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$aW52YWxpZHNhbHQ$aW52YWxpZA';
        $ok = password_verify($password, $hash) && $user !== null;

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

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return is_int($id) ? $id : null;
    }

    public static function user(): ?array
    {
        $id = self::id();

        return $id === null
            ? null
            : Db::fetchOne('SELECT id, username, last_login_at FROM admin_users WHERE id = ?', [$id]);
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::regenerate();
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

    private static function options(): array
    {
        return defined('PASSWORD_ARGON2ID')
            ? ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1]
            : [];
    }
}
