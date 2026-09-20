<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Plain PHP templates. Variables are extracted into the template's scope;
 * escaping stays explicit through e() so it is visible at every output site.
 */
final class View
{
    /** Values made available to every template, e.g. locale and theme. */
    private array $shared = [];

    public function __construct(
        private readonly string $directory = APP_ROOT . '/templates',
    ) {
    }

    public function share(array $values): void
    {
        $this->shared = $values + $this->shared;
    }

    public function render(string $template, array $vars = []): string
    {
        $file = $this->directory . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Template not found: {$template}");
        }

        $scope = $vars + $this->shared + ['view' => $this];

        ob_start();
        (static function (string $__file, array $__scope): void {
            extract($__scope, EXTR_SKIP);
            require $__file;
        })($file, $scope);

        return (string) ob_get_clean();
    }

    /** Render a template and wrap it in a layout as $content. */
    public function page(string $template, array $vars = [], string $layout = 'layout'): string
    {
        $content = $this->render($template, $vars);

        return $this->render($layout, $vars + ['content' => $content]);
    }

    public function output(string $template, array $vars = [], string $layout = 'layout'): void
    {
        echo $this->page($template, $vars, $layout);
    }
}
