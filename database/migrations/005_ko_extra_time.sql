-- Migration 005: Verlängerung und Elfmeterschießen je KO-Spiel.
--
-- score1/score2 bleiben der Stand nach 90 Minuten (regulär) – darauf werden
-- die Tipps gewertet (Standardregel, wie bei den meisten Tippspielen).
-- Zusätzlich werden festgehalten:
--   et1/et2   = Stand nach Verlängerung  (NULL = keine Verlängerung)
--   pen1/pen2 = Ergebnis Elfmeterschießen (NULL = kein Elfmeterschießen)
-- Damit lässt sich im Turnierbaum der Sieger eines in der Verlängerung oder im
-- Elfmeterschießen entschiedenen Spiels korrekt ermitteln, obwohl es nach
-- 90 Minuten unentschieden stand.
--
-- Portabel für SQLite und MySQL/MariaDB.

ALTER TABLE matches ADD COLUMN et1 INTEGER;
ALTER TABLE matches ADD COLUMN et2 INTEGER;
ALTER TABLE matches ADD COLUMN pen1 INTEGER;
ALTER TABLE matches ADD COLUMN pen2 INTEGER;
