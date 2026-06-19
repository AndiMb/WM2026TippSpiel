<?php
declare(strict_types=1);

/**
 * Migrations-Skript: legt das Datenbankschema an.
 *
 * Aufruf (von der Kommandozeile, im Projektverzeichnis):
 *     php database/migrate.php
 *
 * Liest den Treiber aus config/config.php und führt das passende
 * Schema (schema.sqlite.sql oder schema.mysql.sql) aus.
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database as DB;

$driver = DB::driver();
$schemaFile = __DIR__ . '/schema.' . ($driver === 'mysql' ? 'mysql' : 'sqlite') . '.sql';

if (!file_exists($schemaFile)) {
    fwrite(STDERR, "Schema-Datei nicht gefunden: $schemaFile\n");
    exit(1);
}

$sql = file_get_contents($schemaFile);
$pdo = DB::pdo();

echo "Datenbanktreiber: $driver\n";
echo "Führe Schema aus: " . basename($schemaFile) . "\n";

// Kommentare entfernen: alles ab "--" bis Zeilenende (das Schema enthält keine
// "--" innerhalb von String-Literalen, daher ist das hier sicher).
$lines = preg_split('/\r\n|\r|\n/', $sql);
$clean = [];
foreach ($lines as $line) {
    $pos = strpos($line, '--');
    $clean[] = $pos === false ? $line : substr($line, 0, $pos);
}
$sqlClean = implode("\n", $clean);

try {
    // Anweisungen einzeln ausführen (portabel über beide Treiber).
    foreach (array_filter(array_map('trim', explode(';', $sqlClean))) as $statement) {
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }
    echo "✓ Schema erfolgreich angelegt.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Fehler beim Anlegen des Schemas: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Tipp: Jetzt Beispieldaten/Admin anlegen mit:  php database/seed.php\n";
