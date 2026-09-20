<?php

declare(strict_types=1);

return [
    'admin.title' => 'Administration',

    'admin.dashboard.title' => 'Overview',
    'admin.dashboard.signed_in_as' => 'Signed in as {name}.',
    'admin.dashboard.setup' => 'Setup and migrations',
    'admin.dashboard.todo' => 'Coach and student management, the counter matrix and the statistics arrive in later milestones.',


    'setup.title' => 'Setup',
    'setup.intro' => 'This page creates the database schema, imports the sentence collections from the repository and creates the first administrator account.',
    'setup.token.label' => 'Setup token',
    'setup.token.hint' => 'The value of setup_token from config/config.php.',
    'setup.token.error' => 'That setup token is not correct.',
    'setup.token.missing' => 'No setup_token is configured. Add one to config/config.php before continuing.',
    'setup.unlock' => 'Unlock setup',

    'setup.locked.title' => 'Setup is closed',
    'setup.locked.body' => 'An administrator account already exists, so this page is only reachable after signing in.',

    'setup.step.schema' => 'Database schema',
    'setup.step.collections' => 'Sentence collections',
    'setup.step.admin' => 'Administrator account',

    'setup.schema.current' => 'The schema is up to date.',
    'setup.schema.pending.one' => '{count} migration is waiting to be applied.',
    'setup.schema.pending.other' => '{count} migrations are waiting to be applied.',
    'setup.schema.apply' => 'Apply migrations',
    'setup.schema.applied.one' => 'Applied {count} migration.',
    'setup.schema.applied.other' => 'Applied {count} migrations.',

    'setup.collections.none' => 'No collection has been imported yet.',
    'setup.collections.present.one' => '{count} collection is available.',
    'setup.collections.present.other' => '{count} collections are available.',
    'setup.collections.import' => 'Import collections from the repository',
    'setup.collections.imported' => 'Imported {collections} collections with {items} items and {lines} lines.',

    'setup.admin.none' => 'No administrator account exists yet.',
    'setup.admin.username' => 'Username',
    'setup.admin.password' => 'Password',
    'setup.admin.password_repeat' => 'Repeat password',
    'setup.admin.password_hint' => 'At least 12 characters. Use a password manager.',
    'setup.admin.create' => 'Create account',
    'setup.admin.error.username' => 'Please choose a username of at least three characters.',
    'setup.admin.error.password_short' => 'The password must be at least 12 characters long.',
    'setup.admin.error.password_mismatch' => 'The two passwords do not match.',

    'setup.done.title' => 'Setup complete',
    'setup.done.body' => 'The administrator account has been created. This page is now locked and only reachable after signing in.',
    'setup.done.link' => 'Go to sign-in',

    'login.title' => 'Sign in',
    'login.username' => 'Username',
    'login.password' => 'Password',
    'login.submit' => 'Sign in',
    'login.error' => 'Username or password is not correct.',
    'login.throttled' => 'Too many attempts. Please wait a moment and try again.',
    'login.signout' => 'Sign out',
];
