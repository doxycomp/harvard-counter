# Build Plan – harvard-counter

A small web application that lets voice coaches pick practice sentences, copy
them into Discord, and track how often each one has been used per coach and
per student.

> **Repository language:** everything inside this repository — code, comments,
> documentation, commit messages, README — is written in English. German and
> French exist only as UI translations under `lang/`.

---

## 1. Goal

The coach asks the student for a random number between 1 and 72. The
application shows the matching sentence list, how often it has already been
used in that context, increments the counter, and offers the sentences as a
pre-formatted block ready to paste into Discord.

The Harvard Sentences (IEEE, 1969) consist of **72 lists of 10 sentences = 720
sentences**. A number from 1 to 72 therefore selects a *list*. Additional
collections are planned (German and French sentence lists, classic practice
texts such as the Rainbow Passage or Comma Gets a Cure), so the data model is
collection-aware from day one.

---

## 2. Decisions

| Topic | Decision |
|---|---|
| Counting unit | Per item (= one Harvard list), not per individual sentence |
| Seed format | One CSV plus `meta.json` per collection, the single source of truth |
| When to count | Immediately on display, undoable via "Do not count this" |
| Migration / import | Setup page in the admin area (CLI scripts as a fallback) |
| Frontend | Public; without a token it runs as a demo with session-only counters |
| Access | Token in the URL, one token per coach |
| Relations | Coaches ↔ students as n:m; a student may have several coaches |
| Counting | One shared counter per student across all their coaches; a coach's total is their own row plus all assigned students |
| Discord format | Template fields per coach, with presets and a live preview |
| Languages | Frontend **and** admin fully in de/en/fr, independent of the sentence language |
| Collections | Generalised schema from day one, shipping with Harvard only |
| Appearance | Light/dark/system plus selectable colour themes |
| Export | JSON and CSV, retrievable by the coach through their own token link |
| Licence | MIT for the code, per-collection licence and attribution in the data model |

---

## 3. Stack and layout

PHP 8.5, MariaDB, PDO. No framework, no Composer dependencies — deployment is a
`git pull`, nothing has to be built on the server.

`pdo_mysql` is the only required extension. `mbstring` and `intl` are used when
present and fall back to portable implementations when they are not, so a
stripped down shared host stays viable. `App\Str` and `App\I18n::date()` hold
those fallbacks.

```
/                        <- repository root, parent of the document root
├─ public/               <- web server root
│  ├─ index.php          <- frontend
│  ├─ admin/index.php    <- admin (login, management, statistics)
│  ├─ admin/setup.php    <- one-time setup and migrations
│  └─ assets/app.css, themes.css, app.js
├─ src/                  <- Db, Auth, Csrf, Token, I18n, Theme, repositories,
│                           Formatter, Exporter, Importer, View
├─ lang/{de,en,fr}/frontend.php, admin.php
├─ data/collections/
│  └─ harvard-en/meta.json, items.csv
├─ migrations/0001_init.sql …
├─ bin/migrate.php, import_collections.php, create_admin.php,
│     export_context.php, check_translations.php
├─ config/config.php     <- gitignored; config.example.php is committed
└─ docs/BUILDPLAN.md, README.md, LICENSE
```

Everything except `public/` lives outside the web root.

---

## 4. Data model

### Collections

Generalising "72 lists of 10 sentences" to arbitrary collections works through
three levels: **collection → item → line**. The *item* is the unit that gets
drawn and counted.

| Collection | Items | Lines per item |
|---|---|---|
| Harvard Sentences | 72 lists | 10 sentences |
| German / French sentence list | n lists | m sentences |
| Rainbow Passage | 1 passage | n paragraphs |
| Comma Gets a Cure | 1 passage | n paragraphs |
| Community one-liners | n sentences | 1 |

```sql
collections (
  id, slug UNIQUE,              -- e.g. 'harvard-en'
  content_lang CHAR(5),         -- language of the SENTENCES, not of the UI
  names JSON,                   -- {"de":"Harvard Sentences", ...}
  item_labels JSON,             -- {"de":"Liste","en":"List","fr":"Liste"}
  descriptions JSON,
  source_url, attribution, license_note,
  item_count SMALLINT,          -- denormalised, drives the input range
  is_active, sort_order, created_at, updated_at
)

collection_items (
  id, collection_id, item_no SMALLINT,   -- the number the student calls out
  title NULL,
  UNIQUE (collection_id, item_no)
)

collection_lines (
  id, item_id, position SMALLINT, text TEXT,
  UNIQUE (item_id, position)
)
```

Adding a collection means adding a directory `data/collections/<slug>/` with a
`meta.json` and an `items.csv`. The importer scans the directory and creates or
updates idempotently.

### Contexts, counters, the rest

```sql
contexts (
  id, kind ENUM('coach','student'),
  name, sort_order, is_active,
  access_token CHAR(22) NULL UNIQUE,   -- coaches only
  locale CHAR(5) NULL,                 -- preferred UI language of the coach
  theme VARCHAR(16) NULL, color_mode ENUM('system','light','dark') NULL,
  default_collection_id NULL,
  fmt_header, fmt_line, fmt_footer,    -- coaches only, students inherit
  fmt_codeblock TINYINT(1), fmt_codeblock_lang,
  created_at, updated_at
)

-- n:m — a student may learn with several coaches
context_links (
  coach_id, student_id,
  display_name NULL,           -- optional alternative name under this coach
  is_active, sort_order,
  PRIMARY KEY (coach_id, student_id)
)

-- Counter per context row: either a student (shared across their coaches)
-- or a coach's own row
usage_counts (
  context_id, item_id, uses INT UNSIGNED, updated_at,
  PRIMARY KEY (context_id, item_id)
)   -- "uses" rather than "count" so no query has to quote a function name

-- History stays attributed to the teaching coach even though the counter is
-- shared
usage_events (
  id, coach_id, context_id, item_id, counted TINYINT(1), created_at,
  INDEX (coach_id, created_at), INDEX (context_id, created_at)
)

admin_users (id, username UNIQUE, password_hash, created_at, last_login_at)
login_attempts (ip, username, attempted_at)
settings (k VARCHAR(64) PK, v TEXT)
schema_migrations (version PK, applied_at)
```

Everything `utf8mb4` / `utf8mb4_unicode_ci`, InnoDB.

> **Why `item_id` as the counter key from the start:** changing that key later
> means migrating counts that already hold real data. Cutting the schema
> correctly now costs almost nothing — the UI can stay single-collection for a
> while, the schema cannot.

### Counting and roll-up

Exactly **one** context row is incremented per use, never two at once:

* Student selected → counter on the student row.
* "Coach (total)" selected → counter on the coach's own row.

The coach's own row stays selectable on purpose: students who prefer not to be
listed by name are still counted.

The student counter is **shared across coaches**. If a student works with two
coaches, both see the same figure — which answers "has this student had this
list already?" regardless of who taught it. The flip side: a coach can tell
from the counter that a shared student practised elsewhere, and a coach's total
includes those sessions. This is intended, and the admin shows a note when a
student is assigned to a second coach.

```sql
-- Total for this coach
SELECT item_id, SUM(uses) FROM usage_counts
WHERE context_id IN (:coach_id, <ids of assigned students>)
GROUP BY item_id

-- One student, across all of their coaches
SELECT item_id, uses FROM usage_counts WHERE context_id = :student_id
```

Nothing can drift apart this way, not even when individual counters are
corrected by hand in the admin area. The result page shows both figures when a
student is selected: "For Alex: 2× · Total for Robin: 7×".

**History remains attributable:** `usage_events` also stores the teaching
coach. The shared counter answers "has this student had it already?", the event
log answers "what did *I* do with them?" — the admin statistics can show both
without maintaining a second counter.

Rows are created on demand (`INSERT … ON DUPLICATE KEY UPDATE uses = uses + 1`);
missing items count as 0 and are filled in for display in PHP.

---

## 5. Internationalisation

**Two independent axes:** the language of the *interface* and the language of
the *sentences*. A French-speaking coach can work with the English Harvard list.

* UI strings in `lang/<locale>/frontend.php` and `lang/<locale>/admin.php` —
  plain arrays, accessed through `t('key')` and `tn('key', $count)` for
  singular/plural. No gettext, so no PHP extension becomes a requirement.
* Language resolution order: `?lang=fr` → cookie → the coach's `locale` →
  `Accept-Language` → default from `settings`.
* Language switcher in the header on every page.
* Date and number formats per locale (`IntlDateFormatter`, with a fallback if
  `ext-intl` is missing on the target server).
* Collection names, item labels ("Liste" / "List") and descriptions are stored
  as per-locale JSON on the collection and maintained through `meta.json`.
* The admin area is fully translated as well, so the repository can be forked
  without requiring German.
* Discord templates are free text and therefore inherently language-bound —
  `settings` holds one default template per locale, used to pre-fill a new coach.
* `bin/check_translations.php` reports missing and orphaned keys so the three
  locales do not drift apart; it also runs as a smoke test.

The i18n scaffolding lands in M1, not later — otherwise every output string is
touched twice.

---

## 6. Access model

### With a token

`…/?t=<token>` resolves to a coach. The dropdown then shows exactly:

```
Robin (total)     <- own row, also covers students without their own entry
  ├─ Alex         <- shared counter, also used by their other coaches
  └─ Sam
```

Listed are the students assigned to that coach through `context_links` and
active there, under the alternative `display_name` of that link if set.

The token is also stored in a cookie (1 year, `HttpOnly`, `SameSite=Lax`) so
the link only has to be opened once. The most recent context, collection,
language and theme are remembered as well.

22 base62 characters ≈ 131 bits of entropy. Visible, copyable and regeneratable
in the admin area (regenerating invalidates the old link).

### Without a token — demo mode

No dropdown, no foreign counters. Counters start at zero and live in the PHP
session only. A notice bar at the top explains this; its text is configurable
per language in the admin area so the Discord invitation can be changed without
a deployment.

---

## 7. Discord format

Four template fields per coach; students inherit them, and the fallback is the
default from `settings`:

| Field | Example |
|---|---|
| `fmt_header` | `**{collection} – {item_label} {item_no}** ({count}× used)` |
| `fmt_line` | `{n}. {sentence}` |
| `fmt_footer` | *(empty)* |
| `fmt_codeblock` | checkbox plus optional language |

Placeholders: `{item_no}` (`{list_no}` kept as an alias) `{n}` `{global_no}`
`{sentence}` `{count}` `{context}` `{coach}` `{student}` `{collection}`
`{item_label}` `{date}`

In the admin area:

* One-click presets: standard (bold + numbered), code block, sentences only,
  quote style `> `
* Live preview with real sentences next to the fields
* A warning when a code block is enabled while the template contains Markdown
  (Discord does not render `**` inside a code block)

---

## 8. Frontend flow

1. Collection selector (only shown when more than one collection is active),
   context dropdown (with a token) or demo notice, number field
   1–`item_count`, optional shuffle button.
   If a collection holds a single item (Rainbow Passage), the number field is
   omitted.
2. POST → counter +1, `usage_events` entry, redirect (post/redirect/get so that
   F5 does not count twice), event id kept in the session.
3. Result: item number, counter (plus the coach total when a student is
   selected), each line clickable → copied to the clipboard with a "copied ✓"
   confirmation.
4. "Copy everything for Discord" button with the formatted block.
5. "Do not count this" button → decrements and marks the event as
   `counted = 0`, as long as the event id is still in the session.
6. A line reading "Still open: 7, 41, 58 (0× each)" — the least used items for
   the current selection, clickable. Plus a collapsible overview of all items
   with their counters.

Clipboard via `navigator.clipboard` with a `document.execCommand` fallback
(requires HTTPS or localhost).

---

## 9. Appearance and theming

Two independent settings, both server-rendered so there is no flash of the
wrong theme on load:

* **Colour mode:** `system` (default, follows `prefers-color-scheme`), `light`,
  `dark`.
* **Colour theme:** `default`, `pride`, `pastel`, `mono` (black and white),
  `trans`.

Implementation: all colours are CSS custom properties. The server reads the
cookie and renders `<html data-theme="trans" data-mode="dark">`, so the correct
palette is applied before the first paint. `themes.css` defines each theme
twice — once for light and once for dark — so every theme works in both modes.
JavaScript is only needed to switch and to store the cookie.

Rules the themes must follow:

* Body text and controls keep WCAG AA contrast in **every** combination of
  theme and mode. Flag colours are used for accents, borders, focus rings and
  decorative gradients — never as body text on a light background.
* Focus rings stay clearly visible in all themes, including `mono`.
* `prefers-reduced-motion` disables gradient animations and transitions.
* A coach can set a preferred theme and mode, so their token link opens the way
  they like it; visitors can still override it for themselves.

---

## 10. Export and import

So a coach can fork the repository and keep running it on their own server
without starting from zero.

**Export** — reachable in the frontend through the coach's own token link
("Export my data") and in the admin area for any or all coaches:

* **JSON** (machine readable, re-importable): coach, students, Discord
  templates, locale, theme, default collection and all counters.
* **CSV** (for spreadsheets): one row per context × item with its counter.

The **event log** with the timestamps of individual sessions is *not* included
by default and is only added through an "include history" checkbox. Counters
are enough for a move, and the session history should not end up in a file that
gets passed around by accident.

References use **collection slug + `item_no`**, never internal ids, so an import
works on a freshly set up instance with different ids.

What gets exported is what hangs off the exporting coach: their own row, their
student assignments and those counters. Because student counters are shared
across coaches, those figures include sessions with other coaches — the export
says so. The optional event log, in contrast, is filtered down to the coach's
own sessions.

```json
{
  "schema": "harvard-counter/export@1",
  "exported_at": "2026-09-20T10:00:00+00:00",
  "coach": { "name": "Robin", "locale": "de", "format": { } },
  "students": [ { "name": "Alex" }, { "name": "Sam" } ],
  "counts": [ { "collection": "harvard-en", "item_no": 23,
                "context": "Alex", "count": 7 } ]
}
```

**Import** in the admin area: upload the file, preview ("creates 1 coach and 2
students, applies 148 counters"), then confirm. Unknown collection slugs are
reported and skipped rather than silently dropped.

---

## 11. Admin area

* **Login** against `admin_users`, Argon2id, CSRF tokens on every form, session
  cookie `HttpOnly; SameSite=Strict; Secure`, rate limiting through
  `login_attempts`.
* **Setup page:** while no admin user exists it is reachable only with the
  `setup_token` from `config/config.php` — migrate, import collections, create
  the first admin. Afterwards it locks itself and is only reachable behind the
  login (for later migrations after a `git pull`). The dashboard shows a notice
  when migrations are pending.
* **Coaches:** create, rename, deactivate, show/copy/regenerate the token, set
  language, theme and default collection, edit Discord templates with a preview.
* **Students:** their own list, not nested under a coach — create, rename,
  deactivate globally. Maintain coach assignments per student (several are
  possible), each with an optional display name and an active flag. Creating a
  student from within a coach view checks for an existing student of the same
  name and offers "assign the existing one" or "create a new one", so two
  different people are not silently merged.
* **Collections:** overview, activate/deactivate, ordering, re-import from the
  repository, licence and attribution details.
* **Counters:** a matrix of items per context row, directly editable; the coach
  total column is computed and therefore read-only.
* **Statistics** per coach: total uses, last use, top 10 / bottom 10 items, uses
  per month over the last 12 months as plain CSS bars, a breakdown by student
  and the undo rate. All from `usage_events`, so it can be filtered by teaching
  coach — unlike the shared counter. Per student there is an additional view
  across all of their coaches.
* **Export/import**, **change password**.

---

## 12. Responsive design

Mobile-first, single column, no CSS framework.

* Breakpoints: base (phone) → 640px (two columns: sentences plus metadata) →
  1024px (centred, around 72ch of reading width).
* Touch targets of at least 44px; every sentence row is a large click target for
  copying.
* Number field with `inputmode="numeric"` so phones show the numeric keypad.
* Item overview as a CSS grid (`auto-fill, minmax(3.5rem, 1fr)`) — four columns
  on a phone, all items at a glance on a desktop.
* "Copy everything" is pinned to the bottom edge on phones.
* No horizontal scrolling; admin tables collapse into cards on small screens.

---

## 13. Security

* Prepared statements throughout, `htmlspecialchars` on every output.
* CSRF tokens on every writing form, in the frontend as well.
* `Referrer-Policy: no-referrer` — otherwise the token leaks into third-party
  logs when an external link is clicked. The README additionally notes that the
  token appears in the web server's access logs.
* `session_regenerate_id` after login, against session fixation.
* Import upload: size limit, JSON only, strict schema validation before writing.
* `config/config.php` is gitignored and lives outside the web root.

---

## 14. Milestones

| # | Content |
|---|---|
| M1 ✅ | Scaffolding: layout, `config.example.php`, PDO wrapper, migration runner, **i18n scaffolding**, theme scaffolding, `.gitignore` |
| M2 ✅ | Collection schema + `data/collections/harvard-en/` (720 sentences, validated as 72 × 10) + idempotent importer |
| M3 ✅ | Setup route with `setup_token`, self-locking |
| M4 | Context graph (coaches/students, n:m), token resolution, demo mode |
| M5 | Frontend: collection, dropdown, number entry, sentence output |
| M6 | Counting with roll-up, undo, session counters in demo mode |
| M7 | Discord templates, presets, live preview |
| M8 | Admin: login, coach/student CRUD, collections, counter matrix |
| M9 | Statistics and "least used" |
| M10 | Export (JSON/CSV) in frontend and admin, import in the admin area |
| M11 | Responsive CSS, colour themes, clipboard UX, complete de/en/fr translations |
| M12 | README: setup, deployment, configuration, adding collections, licence and attribution |

---

## 15. Deliberately out of scope

* Per-student Discord formats (students inherit from their coach).
* A login for the frontend — the token is the access mechanism.
* Counters per individual sentence — items only.
* Creating whole collections through the web UI; collections come from the
  repository. (A CSV upload in the admin area would be easy to add later.)

---

## 16. Licence

The source code for this project is licensed under the MIT License.

**Data Attribution:** The Harvard Sentences used in this project are in the
public domain. They were originally developed by Harvard University's
Psycho-Acoustic Laboratory and published in the 1969 IEEE Recommended Practice
for Speech Quality Measurements.

Further collections come with their own rights, which is why every collection
carries `source_url`, `attribution` and `license_note` in the data model and
shows them in the frontend. The licence of a collection has to be checked
before it is shipped in the repository — Comma Gets a Cure, for instance, is
not public domain and may only be used with credit to its authors.
