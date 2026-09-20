<?php

declare(strict_types=1);

return [
    'app.name' => 'Harvard Counter',
    'app.tagline' => 'Phrases d\'entraînement pour le travail de la voix',

    'appearance.language' => 'Langue',
    'appearance.theme' => 'Thème de couleurs',
    'appearance.mode' => 'Apparence',
    'appearance.apply' => 'Appliquer',
    'appearance.mode.system' => 'Suivre le système',
    'appearance.mode.light' => 'Clair',
    'appearance.mode.dark' => 'Sombre',

    'theme.name.default' => 'Par défaut',
    'theme.name.pride' => 'Pride',
    'theme.name.pastel' => 'Pastel',
    'theme.name.mono' => 'Noir et blanc',
    'theme.name.trans' => 'Trans',

    'home.title' => 'Phrases d\'entraînement',
    'home.intro' => 'Demandez un numéro, obtenez les phrases correspondantes et voyez combien de fois elles ont déjà servi.',
    'home.ready.collections.one' => '{count} collection est chargée.',
    'home.ready.collections.other' => '{count} collections sont chargées.',
    'home.ready.items.one' => '{count} entrée est disponible.',
    'home.ready.items.other' => '{count} entrées sont disponibles.',
    'home.ready.next' => 'Le sélecteur de phrases n\'est pas encore construit — cette page confirme que l\'installation fonctionne.',

    'error.not_configured.title' => 'Configuration manquante',
    'error.not_configured.body' => 'Copiez config/config.example.php vers config/config.php et renseignez les identifiants de la base de données.',
    'error.db_unreachable.title' => 'Base de données injoignable',
    'error.db_unreachable.body' => 'Les identifiants de config/config.php n\'ont pas fonctionné. Vérifiez l\'hôte, le nom de la base, l\'utilisateur et le mot de passe.',
    'error.not_installed.title' => 'Pas encore installé',
    'error.not_installed.body' => 'La base de données est joignable mais vide. Ouvrez la page d\'installation pour créer le schéma et le premier compte administrateur.',
    'error.not_installed.link' => 'Aller à l\'installation',

    'footer.license' => 'Le code source est publié sous licence MIT.',
    'footer.attribution' => 'Les Harvard Sentences appartiennent au domaine public. Elles ont été développées par le Psycho-Acoustic Laboratory de l\'université Harvard et publiées en 1969 dans l\'IEEE Recommended Practice for Speech Quality Measurements.',
];
