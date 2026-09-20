<?php

declare(strict_types=1);

return [
    'admin.title' => 'Administration',

    'admin.dashboard.title' => 'Vue d’ensemble',
    'admin.dashboard.signed_in_as' => 'Connecté en tant que {name}.',
    'admin.dashboard.setup' => 'Installation et migrations',
    'admin.dashboard.todo' => 'La gestion des coachs et des élèves, la matrice des compteurs et les statistiques arriveront dans des étapes ultérieures.',


    'setup.title' => 'Installation',
    'setup.intro' => 'Cette page crée le schéma de la base de données, importe les collections de phrases depuis le dépôt et crée le premier compte administrateur.',
    'setup.token.label' => 'Jeton d\'installation',
    'setup.token.hint' => 'La valeur de setup_token dans config/config.php.',
    'setup.token.error' => 'Ce jeton d\'installation est incorrect.',
    'setup.token.missing' => 'Aucun setup_token n\'est configuré. Ajoutez-en un dans config/config.php avant de continuer.',
    'setup.unlock' => 'Déverrouiller l\'installation',

    'setup.locked.title' => 'L\'installation est terminée',
    'setup.locked.body' => 'Un compte administrateur existe déjà : cette page n\'est désormais accessible qu\'après connexion.',

    'setup.step.schema' => 'Schéma de la base de données',
    'setup.step.collections' => 'Collections de phrases',
    'setup.step.admin' => 'Compte administrateur',

    'setup.schema.current' => 'Le schéma est à jour.',
    'setup.schema.pending.one' => '{count} migration est en attente.',
    'setup.schema.pending.other' => '{count} migrations sont en attente.',
    'setup.schema.apply' => 'Appliquer les migrations',
    'setup.schema.applied.one' => '{count} migration appliquée.',
    'setup.schema.applied.other' => '{count} migrations appliquées.',

    'setup.collections.none' => 'Aucune collection n\'a encore été importée.',
    'setup.collections.present.one' => '{count} collection est disponible.',
    'setup.collections.present.other' => '{count} collections sont disponibles.',
    'setup.collections.import' => 'Importer les collections depuis le dépôt',
    'setup.collections.imported' => '{collections} collections importées, avec {items} entrées et {lines} lignes.',

    'setup.admin.none' => 'Aucun compte administrateur n\'existe encore.',
    'setup.admin.username' => 'Nom d\'utilisateur',
    'setup.admin.password' => 'Mot de passe',
    'setup.admin.password_repeat' => 'Répéter le mot de passe',
    'setup.admin.password_hint' => 'Au moins 12 caractères. Utilisez un gestionnaire de mots de passe.',
    'setup.admin.create' => 'Créer le compte',
    'setup.admin.error.username' => 'Choisissez un nom d\'utilisateur d\'au moins trois caractères.',
    'setup.admin.error.password_short' => 'Le mot de passe doit contenir au moins 12 caractères.',
    'setup.admin.error.password_mismatch' => 'Les deux mots de passe ne correspondent pas.',

    'setup.done.title' => 'Installation terminée',
    'setup.done.body' => 'Le compte administrateur a été créé. Cette page est maintenant verrouillée et n\'est accessible qu\'après connexion.',
    'setup.done.link' => 'Aller à la connexion',

    'login.title' => 'Connexion',
    'login.username' => 'Nom d\'utilisateur',
    'login.password' => 'Mot de passe',
    'login.submit' => 'Se connecter',
    'login.error' => 'Nom d\'utilisateur ou mot de passe incorrect.',
    'login.throttled' => 'Trop de tentatives. Patientez un instant avant de réessayer.',
    'login.signout' => 'Se déconnecter',
];
