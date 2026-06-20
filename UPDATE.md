# Update-Anleitung – WM 2026 Tippspiel

Diese Anleitung beschreibt, wie du eine bestehende Installation auf eine neue
Version aktualisierst, **ohne vorhandene Daten zu verlieren** (Benutzer, Tipps,
Ergebnisse, Einstellungen). Sie gilt insbesondere für **SQLite**.

> Kurzfassung: **Backup → Code aktualisieren → `php database/migrate.php`** – fertig.

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
  + 002_teams wird angewendet ...
✓ Migrationen abgeschlossen.
```

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
> nur nötig, wenn du nicht ohnehin gleich importierst. Die deutschen Namen
> funktionieren dank Datei-Fallback auch ohne diesen Schritt.

---

## 5. Prüfen

1. Seite im Browser öffnen und einloggen.
2. **Spiele tippen** öffnen – Ländernamen sind deutsch, unter jeder Mannschaft
   stehen FIFA-Rang und letztes Ergebnis.
3. **Rangliste → „Tipps der anderen"** öffnen.

Fertig. Falls etwas klemmt, Backup zurückspielen:

```bash
cp data/tippspiel.backup-XXXX.sqlite data/tippspiel.sqlite
```

---

## Was diese Version Neues bringt

| Neu | Beschreibung |
|-----|--------------|
| 🇩🇪 **Deutsche Ländernamen** | Übersetzung über `src/Data/teams.php` (anpassbar). |
| 📊 **Team-Infos beim Tippen** | FIFA-Weltranglistenplatz + letztes Spielergebnis je Mannschaft. |
| 👀 **Tipps der anderen** | Übersicht aller Tipps – erst **nach Anpfiff** sichtbar. |
| 🔁 **Migrationssystem** | Sichere, datenerhaltende Updates (`schema_migrations`). |

### FIFA-Ränge anpassen

Die Ränge in `src/Data/teams.php` sind eine Näherung. Zum Aktualisieren die
Datei bearbeiten und anschließend `php bin/sync_teams.php` ausführen.
