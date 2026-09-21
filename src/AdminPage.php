<?php

declare(strict_types=1);

namespace App;

/**
 * Shared boot and access control for the authenticated admin pages.
 *
 * Every page under public/admin/ except sign-in and setup starts here, so the
 * installation guard, the sign-in check and the role check live in one place.
 *
 * Two roles: an administrator sees everything; a coach account sees only its
 * own coach and that coach's students. Every id arriving in a request is run
 * through mayAccess*() before it is used — hiding a link in the navigation is
 * not access control.
 */
final class AdminPage
{
    /**
     * @param bool $adminOnly  refuse coach accounts outright
     * @return array{0: array<string, mixed>, 1: View} request variables and a ready view
     */
    public static function start(string $navActive, bool $adminOnly = false): array
    {
        $vars = Web::boot(['frontend', 'admin'], '../');
        $view = new View();

        if (!Config::exists() || !Db::isReachable() || !Install::schemaPresent()) {
            $view->share($vars);
            http_response_code(503);
            echo $view->page('error', [
                'title' => t('error.not_installed.title'),
                'heading' => t('error.not_installed.title'),
                'body' => t('error.not_installed.body'),
                'linkHref' => 'setup.php',
                'linkLabel' => t('error.not_installed.link'),
            ]);
            exit;
        }

        if (!Auth::check()) {
            // Covers a removed account or a deactivated coach as well.
            Auth::logout();
            Web::redirect('index.php');
        }

        $vars += [
            'adminNav' => true,
            'navActive' => $navActive,
            'isAdmin' => Auth::isAdmin(),
        ];
        $view->share($vars);

        if ($adminOnly && !Auth::isAdmin()) {
            self::deny($view);
        }

        return [$vars, $view];
    }

    /** Null for an administrator, otherwise the one coach this account may see. */
    public static function coachScope(): ?int
    {
        return Auth::coachScope();
    }

    public static function mayAccessCoach(int $coachId): bool
    {
        $scope = self::coachScope();

        return Auth::isAdmin() || $scope === $coachId;
    }

    /** A coach's own row, or a student assigned to that coach. */
    public static function mayAccessContext(int $contextId): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }

        $scope = self::coachScope();
        if ($scope === null) {
            return false;
        }

        return $contextId === $scope || Contexts::isLinked($scope, $contextId);
    }

    /** Stop with a 403 page. */
    public static function deny(View $view): never
    {
        http_response_code(403);
        echo $view->page('error', [
            'title' => t('admin.denied.title'),
            'heading' => t('admin.denied.title'),
            'body' => t('admin.denied.body'),
            'linkHref' => 'index.php',
            'linkLabel' => t('admin.nav.overview'),
        ]);
        exit;
    }

    /**
     * Read a positive integer parameter, or null.
     *
     * @param array<string, mixed>|null $source
     */
    public static function id(string $name, ?array $source = null): ?int
    {
        $value = Web::stringParam($name, $source);
        if ($value === null) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    /**
     * Flash messages survive the redirect that follows every write, so a page
     * can answer "saved" without re-posting on reload.
     */
    public static function flash(string $type, string $text): void
    {
        $messages = Session::get('admin_flash', []);
        $messages[] = ['type' => $type, 'text' => $text];
        Session::set('admin_flash', $messages);
    }

    /** @return list<array{type:string, text:string}> */
    public static function takeFlash(): array
    {
        return (array) Session::pull('admin_flash', []);
    }
}
