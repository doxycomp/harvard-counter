<?php

declare(strict_types=1);

return [
    'admin.title' => 'Verwaltung',

    'admin.dashboard.title' => 'Übersicht',
    'admin.dashboard.signed_in_as' => 'Angemeldet als {name}.',
    'admin.dashboard.setup' => 'Einrichtung und Migrationen',
    'admin.dashboard.todo' => 'Die Verwaltung von Coaches und Schülern, die Zähler-Matrix und die Statistik folgen in späteren Meilensteinen.',


    'setup.title' => 'Einrichtung',
    'setup.intro' => 'Diese Seite legt das Datenbankschema an, importiert die Satzsammlungen aus dem Repository und erstellt den ersten Administrator-Zugang.',
    'setup.token.label' => 'Setup-Token',
    'setup.token.hint' => 'Der Wert von setup_token aus config/config.php.',
    'setup.token.error' => 'Dieser Setup-Token stimmt nicht.',
    'setup.token.missing' => 'Es ist kein setup_token konfiguriert. Trage einen in config/config.php ein, bevor es weitergeht.',
    'setup.unlock' => 'Einrichtung freischalten',

    'setup.locked.title' => 'Einrichtung ist abgeschlossen',
    'setup.locked.body' => 'Es existiert bereits ein Administrator-Zugang, deshalb ist diese Seite nur noch nach dem Anmelden erreichbar.',

    'setup.step.schema' => 'Datenbankschema',
    'setup.step.collections' => 'Satzsammlungen',
    'setup.step.admin' => 'Administrator-Zugang',

    'setup.schema.current' => 'Das Schema ist aktuell.',
    'setup.schema.pending.one' => '{count} Migration wartet auf die Ausführung.',
    'setup.schema.pending.other' => '{count} Migrationen warten auf die Ausführung.',
    'setup.schema.apply' => 'Migrationen ausführen',
    'setup.schema.applied.one' => '{count} Migration ausgeführt.',
    'setup.schema.applied.other' => '{count} Migrationen ausgeführt.',

    'setup.collections.none' => 'Es wurde noch keine Sammlung importiert.',
    'setup.collections.present.one' => '{count} Sammlung ist verfügbar.',
    'setup.collections.present.other' => '{count} Sammlungen sind verfügbar.',
    'setup.collections.import' => 'Sammlungen aus dem Repository importieren',
    'setup.collections.imported' => '{collections} Sammlungen mit {items} Einträgen und {lines} Zeilen importiert.',

    'setup.admin.none' => 'Es existiert noch kein Administrator-Zugang.',
    'setup.admin.username' => 'Benutzername',
    'setup.admin.password' => 'Passwort',
    'setup.admin.password_repeat' => 'Passwort wiederholen',
    'setup.admin.password_hint' => 'Mindestens 12 Zeichen. Am besten aus dem Passwortmanager.',
    'setup.admin.create' => 'Zugang anlegen',
    'setup.admin.error.username' => 'Bitte einen Benutzernamen mit mindestens drei Zeichen wählen.',
    'setup.admin.error.password_short' => 'Das Passwort muss mindestens 12 Zeichen lang sein.',
    'setup.admin.error.password_mismatch' => 'Die beiden Passwörter stimmen nicht überein.',

    'setup.done.title' => 'Einrichtung abgeschlossen',
    'setup.done.body' => 'Der Administrator-Zugang wurde angelegt. Diese Seite ist jetzt gesperrt und nur noch nach dem Anmelden erreichbar.',
    'setup.done.link' => 'Zur Anmeldung',

    'login.title' => 'Anmelden',
    'login.username' => 'Benutzername',
    'login.password' => 'Passwort',
    'login.submit' => 'Anmelden',
    'login.error' => 'Benutzername oder Passwort stimmt nicht.',
    'login.throttled' => 'Zu viele Versuche. Bitte kurz warten und noch einmal probieren.',
    'login.signout' => 'Abmelden',
];
