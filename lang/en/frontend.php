<?php

declare(strict_types=1);

return [
    'app.name' => 'Harvard Counter',
    'app.tagline' => 'Practice sentences for voice training',

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

    'home.title' => 'Practice sentences',
    'home.intro' => 'Ask for a number, get the matching sentences, and see how often they have been used.',
    'home.ready.collections.one' => '{count} collection is loaded.',
    'home.ready.collections.other' => '{count} collections are loaded.',
    'home.ready.items.one' => '{count} item is available.',
    'home.ready.items.other' => '{count} items are available.',
    'home.ready.next' => 'The sentence picker is not built yet — this page confirms the installation works.',

    'error.not_configured.title' => 'Configuration missing',
    'error.not_configured.body' => 'Copy config/config.example.php to config/config.php and fill in the database credentials.',
    'error.db_unreachable.title' => 'Database unreachable',
    'error.db_unreachable.body' => 'The credentials in config/config.php did not work. Check host, database name, user and password.',
    'error.not_installed.title' => 'Not set up yet',
    'error.not_installed.body' => 'The database is reachable but empty. Open the setup page to create the schema and the first admin account.',
    'error.not_installed.link' => 'Go to setup',

    'footer.license' => 'Source code licensed under the MIT License.',
    'footer.attribution' => 'The Harvard Sentences are in the public domain. They were developed by Harvard University\'s Psycho-Acoustic Laboratory and published in the 1969 IEEE Recommended Practice for Speech Quality Measurements.',
];
