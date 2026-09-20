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

    'picker.collection' => 'Collection',
    'picker.context' => 'Compter pour',
    'picker.context.coach_total' => '{name} (total)',
    'picker.number' => 'Numéro',
    'picker.number.hint' => 'Un numéro au choix entre 1 et {max}.',
    'picker.submit' => 'Afficher les phrases',
    'picker.random' => 'Choisir au hasard',
    'picker.error.number' => 'Veuillez saisir un numéro entre 1 et {max}.',
    'picker.least_used' => 'Les moins utilisées ({count}×) :',

    'result.uses.one' => 'Utilisée {count} fois',
    'result.uses.other' => 'Utilisée {count} fois',
    'result.uses_for' => 'Pour {name} : {count}×',
    'result.total_for' => 'Total pour {name} : {count}×',
    'result.shared_counter' => 'Ce compteur est partagé avec les autres coachs de cet élève.',
    'result.copy_hint' => 'Cliquez sur une phrase pour la copier.',
    'result.copied' => 'copié ✓',
    'result.copy_all' => 'Tout copier pour Discord',
    'result.dont_count' => 'Ne pas compter cet appel',
    'result.not_counted' => 'Cet appel n\'a pas été compté.',
    'result.show_block' => 'Afficher le bloc Discord',

    'overview.title' => 'Les {count} entrées et leurs compteurs',
    'overview.hint' => 'Ouvrir une entrée depuis ici ne la compte pas.',

    'demo.notice' => 'Les compteurs ne sont conservés que pour cette session. Demandez un lien personnel sur Discord si vous souhaitez les enregistrer.',

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
