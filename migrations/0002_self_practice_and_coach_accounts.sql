-- Self-practice and coach accounts.
--
-- Students may now hold an access link of their own and count what they
-- practise at home. That lives in separate tables on purpose: usage_counts and
-- usage_events keep meaning exactly what they meant before — what came up in a
-- lesson — so "least used", the coach totals and every statistic built on them
-- stay untouched by homework.

CREATE TABLE self_counts (
    context_id INT UNSIGNED NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    uses       INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (context_id, item_id),
    KEY idx_self_counts_item (item_id),
    CONSTRAINT fk_self_counts_context FOREIGN KEY (context_id)
        REFERENCES contexts (id) ON DELETE CASCADE,
    CONSTRAINT fk_self_counts_item FOREIGN KEY (item_id)
        REFERENCES collection_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE self_events (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    context_id INT UNSIGNED NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    counted    TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_self_events_context (context_id, created_at),
    KEY idx_self_events_item (item_id),
    CONSTRAINT fk_self_events_context FOREIGN KEY (context_id)
        REFERENCES contexts (id) ON DELETE CASCADE,
    CONSTRAINT fk_self_events_item FOREIGN KEY (item_id)
        REFERENCES collection_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- An account is either an administrator, who sees everything, or tied to one
-- coach and limited to that coach's students, template, statistics and
-- export. Existing accounts stay administrators.
ALTER TABLE admin_users
    ADD COLUMN role ENUM('admin','coach') NOT NULL DEFAULT 'admin' AFTER password_hash,
    ADD COLUMN coach_id INT UNSIGNED NULL AFTER role,
    ADD KEY idx_admin_users_coach (coach_id),
    ADD CONSTRAINT fk_admin_users_coach FOREIGN KEY (coach_id)
        REFERENCES contexts (id) ON DELETE CASCADE;
