# harvard-counter

A small web application for voice coaches. Ask a student for a number, get the
matching practice sentences, copy them into Discord, and keep track of how
often each one has been used — per coach and per student.

The sentences shipped with this repository are the **Harvard Sentences** (IEEE,
1969): 72 lists of 10 phonetically balanced English sentences. The data model
supports further collections, including prose passages and other languages.

## Status

Under construction. The installation path is complete — schema, sentence
import, setup page and administrator sign-in. The sentence picker, counters,
Discord templates, statistics and export are still to come. See
[docs/BUILDPLAN.md](docs/BUILDPLAN.md) for the full plan and the milestone
list.

## Requirements

- PHP 8.2 or newer (developed and tested against 8.5)
- MariaDB 10.4 or newer (or a compatible MySQL), verified against 10.4
- The `pdo_mysql` extension — the only one that is required
- No Composer dependencies, no build step — deployment is a `git pull`

`mbstring` and `intl` are used when present and fall back to portable
implementations when they are not, so the application also runs on a stripped
down shared host.

## Installation

1. Clone the repository and point the web server's document root at
   `public/`. Everything else — configuration, sentence data, templates —
   stays outside the document root and is never served.

2. Create a database and a user for it.

3. Copy the example configuration and fill it in:

   ```bash
   cp config/config.example.php config/config.php
   ```

   Generate a setup token for it with:

   ```bash
   php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
   ```

4. Open `/admin/setup.php`, enter the setup token, then run the three steps:
   apply the migrations, import the collections, create the first
   administrator account.

   Once an administrator exists the setup page locks itself and is only
   reachable after signing in. That is also how later migrations are applied
   on a server without shell access.

### Command line alternative

Everything the setup page does is also available from the CLI:

```bash
php bin/migrate.php              # apply pending migrations (--status to inspect)
php bin/import_collections.php   # import data/collections into the database
php bin/create_admin.php <name>  # create an administrator or reset a password
```

## Updating

```bash
git pull
php bin/migrate.php
php bin/import_collections.php
```

The collection import is idempotent and preserves item ids, so re-importing
after an update does not touch existing counters.

## Adding a sentence collection

Create a directory under `data/collections/<slug>/` with two files:

- `meta.json` — slug, language of the sentences, names and item labels per UI
  locale, source, attribution and licence note
- `items.csv` — `item_no,position,text`, one line per sentence

Then re-run the import. `meta.json` may declare an `expect` block
(`items`, `lines_per_item`); the import refuses data that does not match, so a
truncated file fails loudly instead of landing half-imported.

Check the licence of a collection before committing it. Not every well-known
practice text is public domain — "Comma Gets a Cure", for example, may only be
used with credit to its authors.

## Development

```bash
php -S 127.0.0.1:8000 -t public   # development server
php bin/check_translations.php    # verify de/en/fr stay in sync
```

UI strings live in `lang/<locale>/frontend.php` and `lang/<locale>/admin.php`.
English is the reference locale; `check_translations.php` reports keys that are
missing, orphaned, or used in code but defined nowhere.

Everything in this repository — code, comments, documentation, commit
messages — is written in English. German and French exist only as UI
translations.

## Security notes

- Coach access tokens are passed in the URL (`?t=…`). They therefore appear in
  the web server's access logs. Keep those logs private, and rotate a token in
  the admin area if a link was shared by mistake.
- The application sends `Referrer-Policy: no-referrer` so a token cannot leak
  to third parties through a `Referer` header.
- Serve the application over HTTPS and leave `cookie_secure` at `true`.
  Clipboard access in browsers also requires a secure context.
- `config/config.php` holds the database credentials and the setup token. It is
  gitignored and lives outside the document root.

## License

The source code for this project is licensed under the MIT License.

**Data Attribution:** The Harvard Sentences used in this project are in the
public domain. They were originally developed by Harvard University's
Psycho-Acoustic Laboratory and published in the 1969 IEEE Recommended Practice
for Speech Quality Measurements.
