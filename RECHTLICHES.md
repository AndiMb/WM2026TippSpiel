# Rechtliche Hinweise (Checkliste)

> **Wichtig:** Dies ist eine praxisnahe Orientierung, **keine Rechtsberatung**.
> Im Zweifel bitte rechtlichen Rat einholen. Die Einschätzung bezieht sich auf
> Deutschland und eine **private Familien-Nutzung**.

## Kurzfazit

Für ein **privates, passwortgeschütztes Familien-Tippspiel ohne Geldeinsatz** ist
die rechtliche Belastung gering. Die App ist bewusst datensparsam gebaut:

* ✅ Keine Tracker, keine Analyse-Dienste, keine Werbe-Cookies.
* ✅ Keine externen Schriftarten/Skripte/CDNs im Browser (kein IP-Abfluss an Dritte).
* ✅ Nur **ein technisch notwendiges** Sitzungs-Cookie → **kein Cookie-Banner** nötig (§ 25 Abs. 2 TDDDG).
* ✅ Passwörter werden nur **gehasht** gespeichert (`password_hash`/bcrypt).
* ✅ Spielplan-Import läuft **serverseitig**; dabei werden keine Teilnehmerdaten an Dritte übertragen.
* ✅ Datenquelle **OpenFootball** ist gemeinfrei (Public Domain).

## 1. Impressum (§ 5 DDG, früher § 5 TMG)

* Eine Impressumspflicht besteht v. a. für **geschäftsmäßige** Telemedien.
  Ein **rein privates** Angebot im Familienkreis ist i. d. R. **nicht**
  impressumspflichtig.
* **Empfehlung:** Sobald die Seite öffentlich im Internet erreichbar ist
  (die Login-Seite ist ohne Anmeldung sichtbar), ist ein Impressum die sichere
  Wahl. Es ist bereits vorbereitet:
  * Seite **`/impressum`** (auch ohne Login erreichbar, verlinkt von der Anmeldeseite).
  * Inhalte ausfüllen unter **Admin → Einstellungen → Rechtliches**
    (Name, Anschrift, Kontakt-E-Mail).

## 2. Datenschutz (DSGVO / TDDDG)

* Verarbeitet werden: Benutzername, Anzeigename, gehashtes Passwort, Tipps und
  Zeitstempel sowie ein Sitzungs-Cookie.
* Bei **rein privater/familiärer** Nutzung kann die **Haushaltsausnahme**
  (Art. 2 Abs. 2 lit. c DSGVO) greifen. Sobald Dritte außerhalb des reinen
  Privatkreises teilnehmen, sollte die **Datenschutzerklärung** aktiv sein:
  * Seite **`/datenschutz`** (vorbereitet, ohne Login erreichbar).
* **Empfehlung:** Verantwortlichen (Name/Kontakt) in den Einstellungen hinterlegen.

## 3. Glücksspiel (GlüStV)

* Glücksspiel setzt einen **Entgelt-/Geldeinsatz** voraus. Dieses Tippspiel ist
  **kostenlos und ohne Einsatz** → **kein** Glücksspiel.
* **Empfehlung:** Keine Geldeinsätze/Pflichtbeiträge einführen. Ein freiwilliger,
  organisatorisch getrennter „Pokal" ist unkritisch; sobald Geld als
  Teilnahmebedingung fließt, ändert sich die rechtliche Lage.

## 4. Marken-/Urheberrecht

* „FIFA" und „FIFA World Cup" sind **geschütze Marken**. Verwende **keine
  offiziellen Logos/Embleme/Maskottchen**. Reine **Mannschafts- und
  Ländernamen** sind beschreibende Angaben und unkritisch.
* Der App-Standardname wurde auf **„WM 2026 Tippspiel"** gesetzt (statt „FIFA …"),
  um keine offizielle Verbindung zu suggerieren. Impressum enthält einen
  entsprechenden **Distanzierungs-Hinweis**.
* Spieldaten von **OpenFootball** stehen in der Public Domain.

## 5. Sicherheit (unterstützt den Datenschutz)

* CSRF-Schutz, gehärtete Sessions (`HttpOnly`, `SameSite=Lax`, bei HTTPS `Secure`),
  serverseitige Validierung, ausschließlich vorbereitete SQL-Statements.
* **Empfehlung:** Seite nur über **HTTPS** betreiben und in `config/config.php`
  `'https_only' => true` setzen.

## To-do des Betreibers vor dem „Live"-Gang

1. **Admin → Einstellungen → Rechtliches** ausfüllen (Name, Anschrift, E-Mail).
2. Texte unter `/impressum` und `/datenschutz` prüfen und ggf. anpassen.
3. HTTPS aktivieren (`https_only = true`).
4. Sicherstellen, dass **kein Geldeinsatz** Teilnahmebedingung ist.
