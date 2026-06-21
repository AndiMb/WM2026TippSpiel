-- Migration 004: Mehrsprachigkeit.
--   users.locale   -> bevorzugte Sprache je Benutzer ('de' | 'pt')
--   teams.name_pt  -> portugiesischer Mannschaftsname
-- Portabel für SQLite und MySQL/MariaDB.

ALTER TABLE users ADD COLUMN locale VARCHAR(5) NOT NULL DEFAULT 'de';
ALTER TABLE teams ADD COLUMN name_pt VARCHAR(80);
