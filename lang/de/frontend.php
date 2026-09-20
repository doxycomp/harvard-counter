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

    'picker.collection' => 'Sammlung',
    'picker.context' => 'Zählen für',
    'picker.context.coach_total' => '{name} (gesamt)',
    'picker.number' => 'Zahl',
    'picker.number.hint' => 'Eine beliebige Zahl von 1 bis {max}.',
    'picker.submit' => 'Sätze anzeigen',
    'picker.random' => 'Zufällig auswählen',
    'picker.error.number' => 'Bitte eine Zahl zwischen 1 und {max} eingeben.',
    'picker.least_used' => 'Am seltensten genutzt ({count}×):',

    'result.uses.one' => '{count}× genutzt',
    'result.uses.other' => '{count}× genutzt',
    'result.uses_for' => 'Für {name}: {count}×',
    'result.total_for' => 'Gesamt bei {name}: {count}×',
    'result.shared_counter' => 'Dieser Zähler wird mit den anderen Coaches dieses Schülers geteilt.',
    'result.copy_hint' => 'Satz anklicken, um ihn zu kopieren.',
    'result.copied' => 'kopiert ✓',
    'result.copy_all' => 'Alles für Discord kopieren',
    'result.dont_count' => 'Diesen Aufruf nicht zählen',
    'result.not_counted' => 'Dieser Aufruf wurde nicht gezählt.',
    'result.show_block' => 'Discord-Block anzeigen',

    'overview.title' => 'Alle {count} Einträge mit Zählerstand',
    'overview.hint' => 'Ein Eintrag, den du hier öffnest, wird nicht gezählt.',

    'demo.notice' => 'Die Zähler gelten nur für diese Sitzung. Einen persönlichen Link zum Speichern gibt es im Discord.',

    'export.link' => 'Meine Daten exportieren',
    'export.denied.title' => 'Kein Zugangslink',
    'export.denied.body' => 'Für den Export braucht es einen persönlichen Zugangslink. Ohne ihn gibt es nichts zu exportieren — die Demo-Zähler leben nur in dieser Browser-Sitzung.',

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
