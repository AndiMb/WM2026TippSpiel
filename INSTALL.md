# Installationsanleitung – WM 2026 Tippspiel

Diese Anleitung beschreibt die Installation auf einem klassischen Linux-Webserver
(Apache2 + PHP 8.2). Es wird **kein Docker** und **kein Node.js** benötigt.

---

## 1. Voraussetzungen

| Komponente | Anforderung |
|------------|-------------|
| PHP        | **8.2 oder neuer** (CLI + Apache-Modul oder PHP-FPM) |
| PHP-Erweiterungen | `pdo`, `pdo_sqlite` (für SQLite) **oder** `pdo_mysql` (für MariaDB), `curl`, `mbstring`, `openssl` |
| Webserver  | Apache 2.4 mit `mod_rewrite` (empfohlen) |
| Datenbank  | **SQLite** (Standard, nichts einzurichten) **oder** MySQL/MariaDB |
| Sonstiges  | Schreibrechte im Ordner `data/` |

Erweiterungen prüfen:

```bash
php -m | grep -E 'pdo_sqlite|pdo_mysql|curl|mbstring|openssl'
```

---

## 2. Dateien hochladen

Lade das komplette Projektverzeichnis per **SFTP/FTP** auf den Server, z. B. nach
`/var/www/tippspiel`. Alternativ per Git:

```bash
git clone <REPO-URL> /var/www/tippspiel
cd /var/www/tippspiel
```

**Wichtig:** Der Apache-`DocumentRoot` soll auf den Unterordner **`public/`** zeigen
(siehe Abschnitt 5). So liegen Quellcode, Konfiguration und Datenbank außerhalb des
öffentlich erreichbaren Bereichs.

---

## 3. Konfiguration anlegen

```bash
cp config/config.example.php config/config.php
nano config/config.php
```

Mindestens anpassen:

* `app.secret` → einen langen Zufallswert eintragen
  (`php -r "echo bin2hex(random_bytes(24));"`)
* `app.timezone` → z. B. `Europe/Berlin`
* `app.https_only` → `true`, sobald die Seite über HTTPS läuft
* `app.base_path` → leer lassen, wenn die App auf der Domain-Wurzel läuft;
  sonst z. B. `/tippspiel`

### Datenbank wählen

**Variante A – SQLite (empfohlen, einfachste Lösung):**

```php
'db' => [
    'driver'      => 'sqlite',
    'sqlite_path' => __DIR__ . '/../data/tippspiel.sqlite',
],
```

**Variante B – MySQL / MariaDB:**

```php
'db' => [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'tippspiel',
    'username' => 'tippspiel',
    'password' => 'GEHEIM',
    'charset'  => 'utf8mb4',
],
```

Für MariaDB vorab Datenbank + Benutzer anlegen:

```sql
CREATE DATABASE tippspiel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tippspiel'@'localhost' IDENTIFIED BY 'GEHEIM';
GRANT ALL PRIVILEGES ON tippspiel.* TO 'tippspiel'@'localhost';
FLUSH PRIVILEGES;
```

---

## 4. Datenbank einrichten (Migration + Seed)

Im Projektverzeichnis ausführen:

```bash
# 1) Tabellen anlegen
php database/migrate.php

# 2) Admin-Account, Standard-Einstellungen und Beispiel-Bonusfragen anlegen
php database/seed.php
# Optional mit eigenem Admin-Passwort:
php database/seed.php MeinSicheresPasswort
```

Danach existiert ein Administrator:

* **Benutzer:** `admin`
* **Passwort:** `admin123` (bzw. das beim Seed angegebene)

> ⚠️ **Passwort sofort nach dem ersten Login ändern** (Menü „Konto“).

Schreibrechte für SQLite setzen (nur bei SQLite nötig):

```bash
chown -R www-data:www-data data
chmod 775 data
```

---

## 5. Apache-Konfiguration

### Variante 1 – Eigener VirtualHost (empfohlen)

```apache
<VirtualHost *:80>
    ServerName tippspiel.example.com
    DocumentRoot /var/www/tippspiel/public

    <Directory /var/www/tippspiel/public>
        AllowOverride All        # .htaccess (mod_rewrite) zulassen
        Require all granted
        Options -Indexes
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/tippspiel_error.log
    CustomLog ${APACHE_LOG_DIR}/tippspiel_access.log combined
</VirtualHost>
```

Aktivieren:

```bash
sudo a2enmod rewrite
sudo a2ensite tippspiel.conf
sudo systemctl reload apache2
```

HTTPS über Let’s Encrypt:

```bash
sudo certbot --apache -d tippspiel.example.com
```
Anschließend in `config/config.php` `'https_only' => true` setzen.

### Variante 2 – Unterverzeichnis auf bestehender Domain

Wenn die App unter `https://example.com/tippspiel/` laufen soll und der
DocumentRoot bereits gesetzt ist: Projekt nach `…/htdocs/tippspiel` legen und in
`config/config.php` `'base_path' => '/tippspiel'` setzen. Die mitgelieferten
`.htaccess`-Dateien leiten Anfragen automatisch an `public/index.php` weiter.

> Die im Projektstamm liegende `.htaccess` sorgt dafür, dass auch bei einem
> DocumentRoot auf das Projektverzeichnis (statt `public/`) sensible Dateien
> geschützt sind. **Empfohlen bleibt** der DocumentRoot direkt auf `public/`.

---

## 6. Spielplan importieren

Nach dem Login als Admin: **Admin → Spiele & Ergebnisse → „Jetzt online
importieren“**. Damit werden alle Spiele der WM 2026 inkl. bereits bekannter
Ergebnisse von der kostenlosen Quelle [OpenFootball](https://github.com/openfootball/worldcup.json)
geladen.

Alternativ per Kommandozeile:

```bash
php bin/import_schedule.php
```

**Fallback ohne Internet/API:** Im Adminbereich lässt sich eine **JSON-** oder
**CSV-Datei** hochladen. Beispieldateien liegen unter
`data/import/fixtures.example.json` und `data/import/fixtures.example.csv`.

---

## 7. Cronjobs einrichten

`crontab -e` öffnen und eintragen (Pfad zu PHP und Projekt anpassen):

```cron
# Spielplan einmal täglich um 03:00 Uhr aktualisieren
0 3 * * * /usr/bin/php /var/www/tippspiel/bin/import_schedule.php >> /var/log/tippspiel_import.log 2>&1

# Ergebnisse während der WM alle 30 Minuten aktualisieren und Tipps werten
*/30 * * * * /usr/bin/php /var/www/tippspiel/bin/update_results.php >> /var/log/tippspiel_results.log 2>&1

# Optional: Live-Zwischenstände (benötigt football-data.org-Key in config.php).
# Außerhalb von Spielzeiten macht das Skript keinen API-Aufruf.
* * * * * /usr/bin/php /var/www/tippspiel/bin/update_live.php >> /var/log/tippspiel_live.log 2>&1
```

> Steht keine API zur Verfügung, kann der Ergebnis-Cronjob entfallen – der Admin
> trägt Ergebnisse dann manuell ein (Admin → Spiele & Ergebnisse).
>
> Der Live-Cronjob ist optional: Auch ohne ihn werden Live-Stände aktualisiert,
> sobald jemand die Seite benutzt (der `/live`-Endpunkt stößt den – auf einmal
> pro Minute gedrosselten – Abruf an). Erlaubt der Hoster nur gröbere
> Cron-Raster (z. B. alle 5 Minuten), ist auch das völlig in Ordnung.

Pfad zur PHP-CLI ermitteln: `which php`.

---

## 8. Erste Schritte nach der Installation

1. Als `admin` einloggen und **Passwort ändern**.
2. Unter **Admin → Einstellungen** das Punktesystem und die **Nachhol-Regel**
   (Variante A oder B) festlegen sowie unter **Rechtliches** die Betreiberangaben
   für Impressum/Datenschutz eintragen (siehe **[RECHTLICHES.md](RECHTLICHES.md)**).
3. **Spielplan importieren** (Abschnitt 6). Damit der **Turnierbaum** vollständig
   erscheint, ist mindestens ein Import nötig (füllt die Spielnummern).
4. Unter **Admin → Benutzer** für jedes Familienmitglied einen Account anlegen –
   dabei je Person die **Sprache** (Deutsch/Português) wählbar. Jeder kann seine
   Sprache später selbst unter **Konto → Sprache** ändern.
5. Fertig – die Mitspieler können sich anmelden und tippen.

---

## 9. Fehlerbehebung

| Problem | Lösung |
|---------|--------|
| „Konfiguration fehlt“ | `config/config.php` wurde nicht erstellt (Abschnitt 3). |
| Weiße Seite / 500er | Apache-Error-Log prüfen; `display_errors` ist bei `https_only=false` aktiv. |
| „Datenbankverbindung fehlgeschlagen“ | Zugangsdaten in `config.php` bzw. SQLite-Schreibrechte (`data/`) prüfen. |
| Online-Import schlägt fehl (SSL) | Server-CA-Zertifikate aktualisieren (`ca-certificates`), oder JSON/CSV-Fallback nutzen. |
| Links führen ins Leere | `mod_rewrite` aktiv? `AllowOverride All` gesetzt? Ggf. `base_path` korrigieren. |
| Punkte stimmen nicht | Nach Änderung des Punktesystems **„Punkte neu berechnen“** ausführen. |
