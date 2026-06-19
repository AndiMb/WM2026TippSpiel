-- =====================================================================
--  WM 2026 Tippspiel  –  Datenbankschema (MySQL / MariaDB)
-- =====================================================================
--  Wird von  database/migrate.php  ausgeführt.
--  Alle Zeitstempel werden in UTC gespeichert.
-- =====================================================================

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(60)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name  VARCHAR(80)  NOT NULL,
    role          VARCHAR(10)  NOT NULL DEFAULT 'player',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    joined_at     DATETIME     NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ext_key     VARCHAR(120),
    stage       VARCHAR(20)  NOT NULL DEFAULT 'group',
    round_name  VARCHAR(60),
    group_name  VARCHAR(40),
    team1       VARCHAR(80)  NOT NULL,
    team2       VARCHAR(80)  NOT NULL,
    kickoff     DATETIME     NOT NULL,
    venue       VARCHAR(120),
    score1      INT,
    score2      INT,
    status      VARCHAR(12)  NOT NULL DEFAULT 'scheduled',
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_matches_extkey (ext_key),
    KEY idx_matches_kickoff (kickoff),
    KEY idx_matches_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    match_id   INT UNSIGNED NOT NULL,
    pred1      INT NOT NULL,
    pred2      INT NOT NULL,
    points     INT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bets_user_match (user_id, match_id),
    KEY idx_bets_user (user_id),
    KEY idx_bets_match (match_id),
    CONSTRAINT fk_bets_user  FOREIGN KEY (user_id)  REFERENCES users (id)   ON DELETE CASCADE,
    CONSTRAINT fk_bets_match FOREIGN KEY (match_id) REFERENCES matches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(60) NOT NULL,
    sval TEXT,
    PRIMARY KEY (skey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bonus_questions (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    qtype          VARCHAR(20)  NOT NULL DEFAULT 'custom',
    question       VARCHAR(255) NOT NULL,
    points         INT NOT NULL DEFAULT 5,
    correct_answer VARCHAR(120),
    deadline       DATETIME,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bonus_answers (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED NOT NULL,
    bonus_question_id INT UNSIGNED NOT NULL,
    answer            VARCHAR(120) NOT NULL,
    points            INT,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bonus_answer (user_id, bonus_question_id),
    CONSTRAINT fk_ba_user FOREIGN KEY (user_id)           REFERENCES users (id)           ON DELETE CASCADE,
    CONSTRAINT fk_ba_q    FOREIGN KEY (bonus_question_id) REFERENCES bonus_questions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
