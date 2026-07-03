<?php
/**
 * Beispiel-Konfiguration für das WM-2026 Tippspiel.
 *
 * Kopiere diese Datei nach  config/config.php  und passe die Werte an.
 *   cp config/config.example.php config/config.php
 *
 * config/config.php wird NICHT ins Git eingecheckt (siehe .gitignore).
 */

return [

    // ---------------------------------------------------------------------
    // Datenbank
    // ---------------------------------------------------------------------
    // 'sqlite'  -> einfachste Variante, keine DB-Einrichtung nötig.
    // 'mysql'   -> MySQL / MariaDB (für größere Installationen).
    'db' => [
        'driver'   => 'sqlite',                 // 'sqlite' oder 'mysql'

        // Nur für SQLite: absoluter Pfad zur Datenbankdatei.
        // Der Ordner muss vom Webserver beschreibbar sein.
        'sqlite_path' => __DIR__ . '/../data/tippspiel.sqlite',

        // Nur für MySQL / MariaDB:
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'tippspiel',
        'username' => 'tippspiel',
        'password' => 'CHANGE_ME',
        'charset'  => 'utf8mb4',
    ],

    // ---------------------------------------------------------------------
    // Anwendung
    // ---------------------------------------------------------------------
    'app' => [
        'name'      => 'WM 2026 Tippspiel',

        // Basis-Pfad, falls die App in einem Unterverzeichnis läuft.
        // Beispiel: App unter https://example.com/tippspiel/  -> '/tippspiel'
        // Läuft die App direkt auf der Domain-Wurzel -> '' (leer lassen).
        'base_path' => '',

        // Zeitzone für die Anzeige von Anstoßzeiten (Anzeige, nicht Speicherung).
        // Gespeichert wird intern immer in UTC.
        'timezone'  => 'Europe/Berlin',

        // Auf 'true' setzen, wenn die App ausschließlich über HTTPS läuft
        // (empfohlen). Sorgt für das 'Secure'-Flag bei Session-Cookies.
        'https_only' => false,

        // Schlüssel für CSRF / Session-Härtung. Bitte ändern!
        'secret'    => 'BITTE_AENDERN_zufaelliger_langer_string',
    ],

    // ---------------------------------------------------------------------
    // Import-Quellen (Spielplan + Ergebnisse)
    // ---------------------------------------------------------------------
    'import' => [
        // Primärquelle: OpenFootball (Public Domain, kein API-Key).
        // Liefert Spielplan UND Endergebnisse in einer Datei.
        'openfootball_url' =>
            'https://raw.githubusercontent.com/openfootball/worldcup.json/master/2026/worldcup.json',

        // Optional: football-data.org für LIVE-Zwischenstände (laufende Spiele
        // + sofortige Wertung nach Abpfiff). Kostenlosen API-Key holen unter
        // https://www.football-data.org/ (WM ist im Gratis-Tarif enthalten,
        // Stände wenige Minuten verzögert). Leer lassen, wenn nicht genutzt.
        'footballdata_token' => '',

        // Lokale Fallback-Datei (JSON oder CSV) für manuellen Import,
        // falls keine Online-Quelle erreichbar ist.
        'local_json' => __DIR__ . '/../data/import/fixtures.json',
        'local_csv'  => __DIR__ . '/../data/import/fixtures.csv',
    ],
];
