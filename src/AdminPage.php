<?php

declare(strict_types=1);

namespace App;

/**
 * Shared boot for the authenticated admin pages.
 *
 * Every page under public/admin/ except the sign-in and setup pages starts
 * here, so the installation guard and the auth check exist in one place rather
 * than being repeated seven times.
 */
final class AdminPage
{
    /**
     * @return array{0: array, 1: View} request variables and a ready view
     */
    public static function start(string $navActive): array
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
            Web::redirect('index.php');
        }

        $vars += ['adminNav' => true, 'navActive' => $navActive];
        $view->share($vars);

        return [$vars, $view];
    }

    /** Read a positive integer parameter, or null. */
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

    public static function takeFlash(): array
    {
        return (array) Session::pull('admin_flash', []);
    }
}
