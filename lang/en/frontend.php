<?php

declare(strict_types=1);

return [
    'app.name' => 'Harvard Counter',
    'app.tagline' => 'Practice sentences for voice training',

    'nav.login' => 'Sign in',
    'nav.admin' => 'Admin',

    'appearance.language' => 'Language',
    'appearance.theme' => 'Colour theme',
    'appearance.mode' => 'Appearance',
    'appearance.apply' => 'Apply',
    'appearance.mode.system' => 'Follow system',
    'appearance.mode.light' => 'Light',
    'appearance.mode.dark' => 'Dark',

    'theme.name.default' => 'Default',
    'theme.name.pride' => 'Pride',
    'theme.name.pastel' => 'Pastel',
    'theme.name.mono' => 'Black & white',
    'theme.name.trans' => 'Trans',
    'theme.name.nonbinary' => 'Non-binary',
    'theme.name.sapphic' => 'Sapphic',
    'theme.name.ace' => 'Ace',

    'home.title' => 'Practice sentences',
    'home.intro' => 'Ask for a number, get the matching sentences, and see how often they have been used.',

    'picker.collection' => 'Collection',
    'picker.context' => 'Counting for',
    'picker.context.coach_total' => '{name} (total)',
    'picker.number' => 'Number',
    'picker.number.hint' => 'Any number from 1 to {max}.',
    'picker.submit' => 'Show sentences',
    'picker.random' => 'Pick one at random',
    'picker.reset' => 'Start over',
    'picker.error.number' => 'Please enter a number between 1 and {max}.',
    'picker.least_used' => 'Least used ({count}×):',

    'result.uses.one' => 'Used {count} time',
    'result.uses.other' => 'Used {count} times',
    'result.uses_for' => 'For {name}: {count}×',
    'result.total_for' => 'Total for {name}: {count}×',
    'result.shared_counter' => 'This counter is shared with the student\'s other coaches.',
    'result.copy_hint' => 'Click a sentence to copy it.',
    'result.copied' => 'copied ✓',
    'result.copy_all' => 'Copy everything for Discord',
    'result.dont_count' => 'Do not count this',
    'result.not_counted' => 'That use was not counted.',
    'result.show_block' => 'Show the Discord block',

    'overview.title' => 'All {count} entries with their counters',
    'overview.hint' => 'Opening an entry from here does not count it.',

    'demo.notice' => 'Counters are only kept for this browser session. Ask in Discord for a personal link if you want them saved.',

    'export.link' => 'Export my data',
    'export.denied.title' => 'No access link',
    'export.denied.body' => 'Exporting needs a personal access link. Without one there is nothing stored to export — the demo counters live only in this browser session.',

    'self.notice' => 'Hi {name}! You are practising on your own here — this is counted separately from your lessons.',
    'result.self_uses' => 'Practised on your own: {count}×',
    'result.lesson_uses' => 'In lessons: {count}×',
    'result.self_uses_student' => 'Also practised alone: {count}×',

    'error.not_configured.title' => 'Configuration missing',
    'error.not_configured.body' => 'Copy config/config.example.php to config/config.php and fill in the database credentials.',
    'error.db_unreachable.title' => 'Database unreachable',
    'error.db_unreachable.body' => 'The credentials in config/config.php did not work. Check host, database name, user and password.',
    'error.not_installed.title' => 'Not set up yet',
    'error.not_installed.body' => 'The database is reachable but empty. Open the setup page to create the schema and the first admin account.',
    'error.not_installed.link' => 'Go to setup',

    'footer.license' => 'Source code licensed under the MIT License.',
    'footer.attribution' => 'The Harvard Sentences are in the public domain. They were developed by Harvard University\'s Psycho-Acoustic Laboratory and published in the 1969 IEEE Recommended Practice for Speech Quality Measurements.',
    'footer.attribution_fharvard' => 'The Fharvard Sentences are by Vincent Aubanel, Clémence Bayard, Antje Strauss and Jean-Luc Schwartz (doi:10.5281/zenodo.1462854), licensed under CC BY 4.0.',
];
