-- =====================================================================
--  WM 2026 Tippspiel  –  Datenbankschema (SQLite)
-- =====================================================================
--  Wird von  database/migrate.php  ausgeführt.
--  Alle Zeitstempel werden in UTC gespeichert (Format: YYYY-MM-DD HH:MM:SS).
-- =====================================================================

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------------
--  Benutzer
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT    NOT NULL UNIQUE,          -- Login-Name
    password_hash TEXT    NOT NULL,                 -- password_hash()
    display_name  TEXT    NOT NULL,                 -- Anzeigename
    role          TEXT    NOT NULL DEFAULT 'player', -- 'admin' | 'player'
    is_active     INTEGER NOT NULL DEFAULT 1,        -- 1 = aktiv, 0 = gesperrt
    joined_at     TEXT    NOT NULL,                  -- Beitrittszeitpunkt (UTC) für Variante B
    created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ---------------------------------------------------------------------
--  Spiele
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS matches (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    ext_key     TEXT    UNIQUE,                      -- stabiler Schlüssel aus Quelle (Dedupe)
    stage       TEXT    NOT NULL DEFAULT 'group',    -- 'group' | 'knockout'
    round_name  TEXT,                                -- z.B. "Matchday 1", "Achtelfinale"
    group_name  TEXT,                                -- z.B. "Group A"
    team1       TEXT    NOT NULL,
    team2       TEXT    NOT NULL,
    kickoff     TEXT    NOT NULL,                    -- Anstoß in UTC (Tippschluss)
    venue       TEXT,
    score1      INTEGER,                             -- Endergebnis Heim (NULL = noch offen)
    score2      INTEGER,                             -- Endergebnis Auswärts
    status      TEXT    NOT NULL DEFAULT 'scheduled',-- 'scheduled' | 'live' | 'finished'
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_matches_kickoff ON matches (kickoff);
CREATE INDEX IF NOT EXISTS idx_matches_status  ON matches (status);

-- ---------------------------------------------------------------------
--  Tipps
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bets (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL,
    match_id   INTEGER NOT NULL,
    pred1      INTEGER NOT NULL,                     -- getipptes Tor Heim
    pred2      INTEGER NOT NULL,                     -- getipptes Tor Auswärts
    points     INTEGER,                              -- berechnete Punkte (NULL = noch nicht gewertet)
    created_at TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (user_id, match_id),
    FOREIGN KEY (user_id)  REFERENCES users (id)   ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches (id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_bets_user  ON bets (user_id);
CREATE INDEX IF NOT EXISTS idx_bets_match ON bets (match_id);

-- ---------------------------------------------------------------------
--  Einstellungen (Key/Value)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    skey  TEXT PRIMARY KEY,
    sval  TEXT
);

-- ---------------------------------------------------------------------
--  Bonusfragen (Weltmeister, Finalist, Torschützenkönig, …)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bonus_questions (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    qtype         TEXT    NOT NULL DEFAULT 'custom',  -- 'champion'|'finalist'|'topscorer'|'custom'
    question      TEXT    NOT NULL,
    points        INTEGER NOT NULL DEFAULT 5,
    correct_answer TEXT,                              -- vom Admin gepflegt (NULL = noch offen)
    deadline      TEXT,                               -- UTC; bis dahin Antwort möglich
    is_active     INTEGER NOT NULL DEFAULT 1,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS bonus_answers (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id           INTEGER NOT NULL,
    bonus_question_id INTEGER NOT NULL,
    answer            TEXT    NOT NULL,
    points            INTEGER,                        -- berechnet bei Auflösung
    created_at        TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at        TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (user_id, bonus_question_id),
    FOREIGN KEY (user_id)           REFERENCES users (id)           ON DELETE CASCADE,
    FOREIGN KEY (bonus_question_id) REFERENCES bonus_questions (id) ON DELETE CASCADE
);
