<?php
declare(strict_types=1);

/**
 * Migrations-Runner (versioniert, idempotent, datenerhaltend).
 *
 * Aufruf:
 *     php database/migrate.php
 *
 * Funktionsweise:
 *   - Eine Tabelle `schema_migrations` merkt sich, welche Migrationen bereits
 *     eingespielt wurden.
 *   - Migration "001_init" ist das Basisschema (schema.<treiber>.sql).
 *   - Weitere Migrationen liegen als nummerierte .sql-Dateien in
 *     database/migrations/ und werden in alphabetischer Reihenfolge angewendet.
 *   - Bereits angewendete Migrationen werden übersprungen.
 *
 * Dadurch ist der Befehl gefahrlos beliebig oft ausführbar – bestehende Daten
 * (z. B. in einer SQLite-Datei) bleiben erhalten. Siehe UPDATE.md.
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database as DB;

$pdo    = DB::pdo();
$driver = DB::driver();

echo "Datenbanktreiber: $driver\n";

// 1) Tracking-Tabelle sicherstellen.
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        version    VARCHAR(100) NOT NULL,
        applied_at VARCHAR(25),
        PRIMARY KEY (version)
    )'
);

// 2) Bereits angewendete Migrationen einlesen.
$applied = [];
foreach (DB::all('SELECT version FROM schema_migrations') as $row) {
    $applied[$row['version']] = true;
}

// 3) Migrationsliste zusammenstellen (Version => SQL-Dateipfad).
$migrations = [];
// 001 = Basisschema, treiberspezifisch.
$migrations['001_init'] = __DIR__ . '/schema.' . ($driver === 'mysql' ? 'mysql' : 'sqlite') . '.sql';
// Inkrementelle Migrationen (portabel) aus dem Unterordner.
foreach (glob(__DIR__ . '/migrations/*.sql') ?: [] as $file) {
    $version = basename($file, '.sql');
    $migrations[$version] = $file;
}

// 4) Offene Migrationen der Reihe nach anwenden.
$ranAny = false;
foreach ($migrations as $version => $file) {
    if (isset($applied[$version])) {
        echo "  = $version (bereits angewendet)\n";
        continue;
    }
    if (!is_file($file)) {
        fwrite(STDERR, "  ! $version: Datei fehlt ($file)\n");
        exit(1);
    }

    echo "  + $version wird angewendet ...\n";
    try {
        runSqlFile($pdo, $file);
        DB::run('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)',
            [$version, DB::now()]);
        $ranAny = true;
    } catch (Throwable $e) {
        fwrite(STDERR, "  ! Fehler in $version: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo $ranAny ? "✓ Migrationen abgeschlossen.\n" : "✓ Datenbank ist bereits aktuell.\n";

/**
 * Führt eine SQL-Datei aus: entfernt Kommentare (-- ... bis Zeilenende) und
 * führt die einzelnen, durch ';' getrennten Anweisungen aus.
 */
function runSqlFile(PDO $pdo, string $file): void
{
    $sql = (string) file_get_contents($file);

    // "--"-Kommentare zeilenweise entfernen (kein "--" in String-Literalen).
    $clean = [];
    foreach (preg_split('/\r\n|\r|\n/', $sql) as $line) {
        $pos = strpos($line, '--');
        $clean[] = $pos === false ? $line : substr($line, 0, $pos);
    }
    $sqlClean = implode("\n", $clean);

    foreach (array_filter(array_map('trim', explode(';', $sqlClean))) as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
}
