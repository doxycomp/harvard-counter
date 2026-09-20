-- Initial schema.
--
-- Counters hang off collection items rather than a hard-coded list number, so
-- further collections (other languages, prose passages) need no migration of
-- data that already holds real counts.

CREATE TABLE collections (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug           VARCHAR(64) NOT NULL,
    content_lang   VARCHAR(5) NOT NULL DEFAULT 'en',
    names          TEXT NOT NULL,
    item_labels    TEXT NOT NULL,
    descriptions   TEXT NULL,
    source_url     VARCHAR(255) NULL,
    attribution    TEXT NULL,
    license_note   TEXT NULL,
    item_count     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    sort_order     SMALLINT NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL,
    updated_at     DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_collections_slug (slug),
    KEY idx_collections_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE collection_items (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    collection_id INT UNSIGNED NOT NULL,
    item_no       SMALLINT UNSIGNED NOT NULL,
    title         VARCHAR(190) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_items_collection_no (collection_id, item_no),
    CONSTRAINT fk_items_collection FOREIGN KEY (collection_id)
        REFERENCES collections (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE collection_lines (
    id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_id  INT UNSIGNED NOT NULL,
    position SMALLINT UNSIGNED NOT NULL,
    text     TEXT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lines_item_position (item_id, position),
    CONSTRAINT fk_lines_item FOREIGN KEY (item_id)
        REFERENCES collection_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coaches and students share one table: both are things a counter can hang
-- off, and both appear in the same dropdown.
CREATE TABLE contexts (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kind                  ENUM('coach','student') NOT NULL,
    name                  VARCHAR(120) NOT NULL,
    sort_order            SMALLINT NOT NULL DEFAULT 0,
    is_active             TINYINT(1) NOT NULL DEFAULT 1,
    access_token          CHAR(22) NULL,
    locale                VARCHAR(5) NULL,
    theme                 VARCHAR(16) NULL,
    color_mode            ENUM('system','light','dark') NULL,
    default_collection_id INT UNSIGNED NULL,
    fmt_header            VARCHAR(500) NULL,
    fmt_line              VARCHAR(500) NULL,
    fmt_footer            VARCHAR(500) NULL,
    fmt_codeblock         TINYINT(1) NOT NULL DEFAULT 0,
    fmt_codeblock_lang    VARCHAR(20) NULL,
    created_at            DATETIME NOT NULL,
    updated_at            DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contexts_token (access_token),
    KEY idx_contexts_kind (kind, is_active, sort_order),
    CONSTRAINT fk_contexts_collection FOREIGN KEY (default_collection_id)
        REFERENCES collections (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- n:m, because a student may learn with several coaches.
CREATE TABLE context_links (
    coach_id     INT UNSIGNED NOT NULL,
    student_id   INT UNSIGNED NOT NULL,
    display_name VARCHAR(120) NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   SMALLINT NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL,
    PRIMARY KEY (coach_id, student_id),
    KEY idx_links_student (student_id),
    CONSTRAINT fk_links_coach FOREIGN KEY (coach_id)
        REFERENCES contexts (id) ON DELETE CASCADE,
    CONSTRAINT fk_links_student FOREIGN KEY (student_id)
        REFERENCES contexts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per context and item. A student row is shared across that student's
-- coaches; a coach row covers sessions without a named student.
-- The column is called "uses" rather than "count" so no query has to remember
-- to quote a function name.
CREATE TABLE usage_counts (
    context_id INT UNSIGNED NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    uses       INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (context_id, item_id),
    KEY idx_counts_item (item_id),
    CONSTRAINT fk_counts_context FOREIGN KEY (context_id)
        REFERENCES contexts (id) ON DELETE CASCADE,
    CONSTRAINT fk_counts_item FOREIGN KEY (item_id)
        REFERENCES collection_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The counter is shared, the history is not: coach_id records who taught.
CREATE TABLE usage_events (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    coach_id   INT UNSIGNED NOT NULL,
    context_id INT UNSIGNED NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    counted    TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_events_coach (coach_id, created_at),
    KEY idx_events_context (context_id, created_at),
    KEY idx_events_item (item_id),
    CONSTRAINT fk_events_coach FOREIGN KEY (coach_id)
        REFERENCES contexts (id) ON DELETE CASCADE,
    CONSTRAINT fk_events_context FOREIGN KEY (context_id)
        REFERENCES contexts (id) ON DELETE CASCADE,
    CONSTRAINT fk_events_item FOREIGN KEY (item_id)
        REFERENCES collection_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(64) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME NOT NULL,
    last_login_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip           VARCHAR(45) NOT NULL,
    username     VARCHAR(64) NULL,
    attempted_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_attempts_ip (ip, attempted_at),
    KEY idx_attempts_username (username, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    k          VARCHAR(64) NOT NULL,
    v          TEXT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (k)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Discord templates, used to pre-fill a newly created coach and for
-- visitors in demo mode. Editable in the admin area afterwards.
INSERT INTO settings (k, v, updated_at) VALUES
    ('fmt.default.en.header', '**{collection} – {item_label} {item_no}** ({count}× used)', NOW()),
    ('fmt.default.en.line',   '{n}. {sentence}', NOW()),
    ('fmt.default.en.footer', '', NOW()),
    ('fmt.default.de.header', '**{collection} – {item_label} {item_no}** ({count}× genutzt)', NOW()),
    ('fmt.default.de.line',   '{n}. {sentence}', NOW()),
    ('fmt.default.de.footer', '', NOW()),
    ('fmt.default.fr.header', '**{collection} – {item_label} {item_no}** ({count}× utilisé)', NOW()),
    ('fmt.default.fr.line',   '{n}. {sentence}', NOW()),
    ('fmt.default.fr.footer', '', NOW());
