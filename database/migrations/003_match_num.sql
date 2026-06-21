-- Migration 003: offizielle Spielnummer (aus der Quelle) je Spiel.
-- Wird für den Turnierbaum benötigt: KO-Platzhalter wie "W73"/"L101"
-- (Sieger/Verlierer von Spiel 73 bzw. 101) verweisen auf diese Nummer.
-- Außerdem dient sie als stabiler Schlüssel für KO-Spiele, deren
-- Mannschaften sich im Turnierverlauf erst noch ergeben.
-- Portabel für SQLite und MySQL/MariaDB.

ALTER TABLE matches ADD COLUMN num INTEGER;
