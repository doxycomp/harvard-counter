<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/**
 * Builds the block that gets pasted into Discord.
 *
 * A coach's template is three free-text fields plus a code-block switch, which
 * covers numbering, headings, bold, quote style and plain sentences without a
 * checkbox for each. The admin area gets presets and a live preview on top of
 * this in a later milestone.
 */
final class Formatter
{
    public const PLACEHOLDERS = [
        '{item_no}', '{list_no}', '{n}', '{global_no}', '{sentence}', '{count}',
        '{context}', '{coach}', '{student}', '{collection}', '{item_label}', '{date}',
    ];

    /**
     * Starting points offered in the admin area. Deliberately free of words so
     * they suit any interface language; the wording is the coach's to add.
     */
    public const PRESETS = [
        'standard' => [
            'header' => '**{collection} – {item_label} {item_no}** ({count}×)',
            'line' => '{n}. {sentence}',
            'footer' => '',
            'codeblock' => false,
            'codeblock_lang' => '',
        ],
        'codeblock' => [
            'header' => '{collection} – {item_label} {item_no} ({count}x)',
            'line' => '{n}. {sentence}',
            'footer' => '',
            'codeblock' => true,
            'codeblock_lang' => '',
        ],
        'plain' => [
            'header' => '',
            'line' => '{sentence}',
            'footer' => '',
            'codeblock' => false,
            'codeblock_lang' => '',
        ],
        'quote' => [
            'header' => '**{item_label} {item_no}**',
            'line' => '> {sentence}',
            'footer' => '',
            'codeblock' => false,
            'codeblock_lang' => '',
        ],
    ];

    /**
     * The template to use: the coach's own, else the per-locale default.
     *
     * @return array{header:string, line:string, footer:string, codeblock:bool, codeblock_lang:string}
     */
    public static function templateFor(?array $coach, string $locale): array
    {
        $defaults = Settings::defaultFormat($locale);

        if ($coach === null) {
            return [
                'header' => $defaults['header'],
                'line' => $defaults['line'],
                'footer' => $defaults['footer'],
                'codeblock' => false,
                'codeblock_lang' => '',
            ];
        }

        // A coach field that was never set falls back; one deliberately
        // emptied stays empty, which is how "no heading" is expressed.
        return [
            'header' => $coach['fmt_header'] ?? $defaults['header'],
            'line' => ($coach['fmt_line'] ?? '') !== '' ? (string) $coach['fmt_line'] : $defaults['line'],
            'footer' => $coach['fmt_footer'] ?? $defaults['footer'],
            'codeblock' => (bool) ($coach['fmt_codeblock'] ?? false),
            'codeblock_lang' => (string) ($coach['fmt_codeblock_lang'] ?? ''),
        ];
    }

    /**
     * @param array{header:string, line:string, footer:string, codeblock:bool, codeblock_lang:string} $template
     * @param array<string, string|int> $vars       shared placeholders
     * @param array<int, array{position:int, text:string}> $lines
     * @param int $lineOffset  how many lines precede this item, for {global_no}
     */
    public static function render(array $template, array $vars, array $lines, int $lineOffset = 0): string
    {
        $shared = self::sharedReplacements($vars);

        $parts = [];

        if (trim($template['header']) !== '') {
            $parts[] = strtr($template['header'], $shared);
        }

        $n = 0;
        foreach ($lines as $line) {
            $n++;
            // The per-line values must come first: array union keeps the
            // left-hand side, and $shared carries empty defaults for these
            // three keys so a stray {n} in the header does not survive.
            $parts[] = strtr($template['line'], [
                '{n}' => (string) $n,
                '{global_no}' => (string) ($lineOffset + $n),
                '{sentence}' => (string) $line['text'],
            ] + $shared);
        }

        if (trim($template['footer']) !== '') {
            $parts[] = strtr($template['footer'], $shared);
        }

        $body = implode("\n", $parts);

        if ($template['codeblock']) {
            $language = preg_replace('/[^a-z0-9+-]/i', '', $template['codeblock_lang']) ?? '';

            return "```{$language}\n{$body}\n```";
        }

        return $body;
    }

    /**
     * True when the template mixes Discord markdown with a code block, where
     * the markdown would be shown literally instead of rendered.
     */
    public static function markdownInCodeblock(array $template): bool
    {
        if (!$template['codeblock']) {
            return false;
        }

        $text = $template['header'] . $template['line'] . $template['footer'];

        return preg_match('/\*\*|__|~~|^\s*>|\*(?=\S)/m', $text) === 1;
    }

    /** @param array<string, string|int> $vars */
    private static function sharedReplacements(array $vars): array
    {
        $date = $vars['date'] ?? null;
        $formatted = $date instanceof DateTimeImmutable
            ? I18n::date($date)
            : (string) ($date ?? I18n::date(new DateTimeImmutable()));

        return [
            '{item_no}' => (string) ($vars['item_no'] ?? ''),
            // Kept as an alias so a template written for the Harvard lists
            // keeps working when other collections appear.
            '{list_no}' => (string) ($vars['item_no'] ?? ''),
            '{count}' => (string) ($vars['count'] ?? ''),
            '{context}' => (string) ($vars['context'] ?? ''),
            '{coach}' => (string) ($vars['coach'] ?? ''),
            '{student}' => (string) ($vars['student'] ?? ''),
            '{collection}' => (string) ($vars['collection'] ?? ''),
            '{item_label}' => (string) ($vars['item_label'] ?? ''),
            '{date}' => $formatted,
            // Defaults, so a stray {n} in the header does not survive as text.
            '{n}' => '',
            '{global_no}' => '',
            '{sentence}' => '',
        ];
    }
}
