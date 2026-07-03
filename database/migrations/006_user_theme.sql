-- Migration 006: Wählbare Ansicht (Design) je Benutzer.
--   users.theme -> 'standard' | 'kids' | 'modern'
--     standard = bisherige Ansicht
--     kids     = extra groß und bunt, für junge Mitspieler (~10 Jahre)
--     modern   = dunkles Design mit dezenten Animationen
-- Portabel für SQLite und MySQL/MariaDB.

ALTER TABLE users ADD COLUMN theme VARCHAR(20) NOT NULL DEFAULT 'standard';
