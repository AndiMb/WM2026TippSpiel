# Update-Anleitung – WM 2026 Tippspiel

Diese Anleitung beschreibt, wie du eine bestehende Installation auf eine neue
Version aktualisierst, **ohne vorhandene Daten zu verlieren** (Benutzer, Tipps,
Ergebnisse, Einstellungen). Sie gilt insbesondere für **SQLite**.

> Kurzfassung: **Backup → Code aktualisieren → `php database/migrate.php` →
> `php bin/import_schedule.php`** – fertig.

---

## Warum keine Daten verloren gehen

Die Anwendung nutzt ein **versioniertes Migrationssystem**:

* Eine Tabelle `schema_migrations` merkt sich, welche Datenbank-Änderungen bereits
  eingespielt wurden.
* `php database/migrate.php` wendet **nur neue** Migrationen an und überspringt
  bereits angewandte. Der Befehl ist beliebig oft gefahrlos ausführbar.
* Alle Schema-Änderungen sind additiv (`CREATE TABLE IF NOT EXISTS`, neue
  Tabellen/Spalten) – bestehende Tabellen und Inhalte werden **nicht** gelöscht
  oder überschrieben.

Deine eigentlichen Daten liegen bei SQLite in **einer einzigen Datei**
(`data/tippspiel.sqlite`). Diese Datei wird beim Update **nicht** angefasst,
solange du sie nicht selbst löschst.

---

## 1. Backup erstellen (immer zuerst!)

**SQLite** – einfach die Datenbankdatei kopieren:

```bash
cd /var/www/tippspiel
cp data/tippspiel.sqlite data/tippspiel.backup-$(date +%Y%m%d-%H%M).sqlite
```

> Tipp: Am besten zusätzlich den ganzen Ordner sichern (inkl. `config/config.php`).

**MySQL/MariaDB** – Dump ziehen:

```bash
mysqldump -u tippspiel -p tippspiel > tippspiel-backup-$(date +%Y%m%d).sql
```

---

## 2. Neuen Code einspielen

**Per Git:**

```bash
cd /var/www/tippspiel
git pull
```

**Per FTP/SFTP:** Alle Dateien **außer** den folgenden überschreiben:

* `config/config.php`  ← deine Konfiguration, **nicht** überschreiben
* `data/`              ← deine Datenbank & Importe, **nicht** überschreiben

> Die Ordner `src/`, `views/`, `public/`, `database/`, `bin/` werden ersetzt.

---

## 3. Migrationen ausführen

```bash
php database/migrate.php
```

Beispielausgabe bei einem Update:

```
Datenbanktreiber: sqlite
  = 001_init (bereits angewendet)
  = 002_teams (bereits angewendet)
  + 003_match_num wird angewendet ...
✓ Migrationen abgeschlossen.
```

Aktueller Migrationsstand:

| Version | Inhalt |
|---------|--------|
| `001_init` | Basisschema (Benutzer, Spiele, Tipps, Einstellungen, Bonus). |
| `002_teams` | Tabelle `teams` (deutsche Namen + FIFA-Rang). |
| `003_match_num` | Spalte `matches.num` (offizielle Spielnummer; nötig für den Turnierbaum). |
| `004_i18n` | Spalte `users.locale` (Sprache je Benutzer) + `teams.name_pt` (portugiesische Namen). |

> Bei einer Installation, die es **vor** dem Migrationssystem gab, erkennt der
> Runner die vorhandenen Tabellen automatisch (dank `CREATE TABLE IF NOT EXISTS`)
> und ergänzt nur das Fehlende.

---

## 4. Mannschaftsdaten synchronisieren

Damit deutsche Ländernamen und FIFA-Ränge gefüllt sind:

```bash
php bin/sync_teams.php
```

> Wird auch automatisch bei jedem Spielplan-Import ausgeführt. Der Schritt ist
> nur nötig, wenn du nicht ohnehin gleich importierst. Deutsche Namen **und
> Flaggen** funktionieren dank Datei-Fallback auch ohne diesen Schritt.

---

## 5. Spielplan importieren (für den Turnierbaum wichtig)

Damit die **Spielnummern** (`num`) gefüllt sind und der **Turnierbaum**
vollständig erscheint, nach den Migrationen einmal importieren:

```bash
php bin/import_schedule.php
```

> Alternativ im Adminbereich: **Spiele & Ergebnisse → „Jetzt online importieren"**.
> Beim Import werden vorhandene Spiele aktualisiert (keine Duplikate) und die
> KO-Spiele über ihre Spielnummer abgeglichen. Läuft ohnehin ein Cronjob für den
> Import, genügt dessen nächster Lauf.

---

## 6. Prüfen

1. Seite im Browser öffnen und einloggen.
2. **Spiele tippen** öffnen – Ländernamen erscheinen übersetzt (Sprache laut
   Konto), jede Mannschaft hat eine **Flagge**, darunter FIFA-Rang und letztes
   Ergebnis.
3. **Turnier** öffnen – Gruppentabellen und KO-Turnierbaum (Sechzehntelfinale
   zeigt die Plätze laut aktuellem Tabellenstand).
4. **Rangliste → „Tipps der anderen"** öffnen.
5. **Konto → Sprache** auf „Português" umstellen und prüfen, dass die Oberfläche
   übersetzt erscheint.

Fertig. Falls etwas klemmt, Backup zurückspielen:

```bash
cp data/tippspiel.backup-XXXX.sqlite data/tippspiel.sqlite
```

---

## Was diese Version Neues bringt

| Neu | Beschreibung |
|-----|--------------|
| 🌐 **Mehrsprachig (DE/PT)** | Sprache je Benutzer (Konto → Sprache), Umschalter auf der Login-Seite; übersetzte Oberfläche **und** Ländernamen. |
| 🏴 **Ländernamen + Flaggen** | Lokale SVG-Flaggen je Mannschaft (`public/assets/img/flags/`), Namen in DE/PT – keine externen Server. |
| 📊 **Team-Infos beim Tippen** | FIFA-Weltranglistenplatz + letztes Spielergebnis je Mannschaft. |
| 🏟️ **Gruppen & Turnierbaum** | Gruppentabellen, beste Gruppendritte und KO-Baum mit Runden-Tabs und stufenweisem Wischen (`/turnier`). |
| 👀 **Tipps der anderen** | Übersicht aller Tipps – erst **nach Anpfiff** sichtbar. |
| 🔁 **Migrationssystem** | Sichere, datenerhaltende Updates (`schema_migrations`). |

### FIFA-Ränge anpassen

Die Ränge in `src/Data/teams.php` sind eine Näherung. Zum Aktualisieren die
Datei bearbeiten und anschließend `php bin/sync_teams.php` ausführen.

### Flaggen ergänzen

Fehlt eine Flagge, die passende `<iso2>.svg` nach
`public/assets/img/flags/` legen und in `src/Data/teams.php` den `iso2`-Code
eintragen (Details: `public/assets/img/flags/README.md`).
