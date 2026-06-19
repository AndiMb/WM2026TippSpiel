<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Schlanke PDO-Wrapper-Klasse.
 *
 * Unterstützt SQLite (Standard) und MySQL/MariaDB. Die übrige Anwendung
 * spricht ausschließlich über diese Klasse mit der Datenbank, damit der
 * Treiber an einer einzigen Stelle gewechselt werden kann.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'sqlite';

    /** Verbindung anhand der Konfiguration aufbauen (einmalig). */
    public static function init(array $config): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = $config['db'];
        self::$driver = $db['driver'];

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if ($db['driver'] === 'sqlite') {
                $dir = \dirname($db['sqlite_path']);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                self::$pdo = new PDO('sqlite:' . $db['sqlite_path'], null, null, $options);
                // Fremdschlüssel in SQLite explizit aktivieren.
                self::$pdo->exec('PRAGMA foreign_keys = ON');
                self::$pdo->exec('PRAGMA journal_mode = WAL');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $db['host'], $db['port'], $db['database'], $db['charset']
                );
                self::$pdo = new PDO($dsn, $db['username'], $db['password'], $options);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
        }

        return self::$pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            throw new \RuntimeException('Database::init() wurde noch nicht aufgerufen.');
        }
        return self::$pdo;
    }

    public static function driver(): string
    {
        return self::$driver;
    }

    /** SELECT, das mehrere Zeilen liefert. */
    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** SELECT, das genau eine Zeile (oder null) liefert. */
    public static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Einzelwert aus der ersten Spalte. */
    public static function scalar(string $sql, array $params = [])
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /** INSERT/UPDATE/DELETE; liefert die Anzahl betroffener Zeilen. */
    public static function run(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** INSERT und Rückgabe der neuen ID. */
    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    /** Aktueller UTC-Zeitstempel im DB-Format. */
    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
