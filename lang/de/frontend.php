<?php

declare(strict_types=1);

return [
    'app.name' => 'Harvard Counter',
    'app.tagline' => 'Übungssätze fürs Voicetraining',

    'appearance.language' => 'Sprache',
    'appearance.theme' => 'Farbthema',
    'appearance.mode' => 'Darstellung',
    'appearance.apply' => 'Übernehmen',
    'appearance.mode.system' => 'System folgen',
    'appearance.mode.light' => 'Hell',
    'appearance.mode.dark' => 'Dunkel',

    'theme.name.default' => 'Standard',
    'theme.name.pride' => 'Pride',
    'theme.name.pastel' => 'Pastell',
    'theme.name.mono' => 'Schwarz-Weiß',
    'theme.name.trans' => 'Trans',

    'home.title' => 'Übungssätze',
    'home.intro' => 'Nach einer Zahl fragen, die passenden Sätze erhalten und sehen, wie oft sie schon genutzt wurden.',
    'home.ready.collections.one' => '{count} Sammlung ist geladen.',
    'home.ready.collections.other' => '{count} Sammlungen sind geladen.',
    'home.ready.items.one' => '{count} Eintrag ist verfügbar.',
    'home.ready.items.other' => '{count} Einträge sind verfügbar.',
    'home.ready.next' => 'Die Satzauswahl ist noch nicht gebaut — diese Seite bestätigt, dass die Installation funktioniert.',

    'error.not_configured.title' => 'Konfiguration fehlt',
    'error.not_configured.body' => 'Kopiere config/config.example.php nach config/config.php und trage die Datenbank-Zugangsdaten ein.',
    'error.db_unreachable.title' => 'Datenbank nicht erreichbar',
    'error.db_unreachable.body' => 'Die Zugangsdaten in config/config.php haben nicht funktioniert. Prüfe Host, Datenbankname, Benutzer und Passwort.',
    'error.not_installed.title' => 'Noch nicht eingerichtet',
    'error.not_installed.body' => 'Die Datenbank ist erreichbar, aber leer. Öffne die Setup-Seite, um das Schema und den ersten Admin-Zugang anzulegen.',
    'error.not_installed.link' => 'Zum Setup',

    'footer.license' => 'Der Quellcode steht unter der MIT-Lizenz.',
    'footer.attribution' => 'Die Harvard Sentences sind gemeinfrei. Sie wurden vom Psycho-Acoustic Laboratory der Harvard University entwickelt und 1969 in der IEEE Recommended Practice for Speech Quality Measurements veröffentlicht.',
];
