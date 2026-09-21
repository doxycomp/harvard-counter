<?php

declare(strict_types=1);

/*
 * Coding style: PER Coding Style 2.0 (the successor to PSR-12) plus a few
 * house rules the code already follows.
 *
 * Templates are left out on purpose: they mix HTML and PHP, and a fixer
 * reflowing that mix does more harm than good.
 */

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/public', __DIR__ . '/bin', __DIR__ . '/lang', __DIR__ . '/config'])
    ->name('*.php')
    ->append([__FILE__]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters', 'match']],
        'array_syntax' => ['syntax' => 'short'],
        'no_whitespace_in_blank_line' => true,
        'phpdoc_order' => true,
    ])
    ->setFinder($finder);
