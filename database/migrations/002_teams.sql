-- Migration 002: Mannschafts-Stammdaten (deutscher Name + FIFA-Rang).
-- Portabel für SQLite und MySQL/MariaDB. Wird aus src/Data/teams.php befüllt
-- (TeamService::syncFromData()), daher hier nur die Tabelle.

CREATE TABLE IF NOT EXISTS teams (
    name_en    VARCHAR(80) NOT NULL,
    name_de    VARCHAR(80) NOT NULL,
    fifa_rank  INTEGER,
    code       VARCHAR(8),
    updated_at VARCHAR(25),
    PRIMARY KEY (name_en)
);
