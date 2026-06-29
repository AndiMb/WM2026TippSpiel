<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\MatchModel;
use App\Services\ScheduleImporter;
use App\Services\ScoringService;

final class MatchController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('admin/matches', [
            '_active' => 'admin',
            'matches' => MatchModel::all(),
        ], 'Spiele & Ergebnisse');
    }

    /** Manueller Import: Online-Quelle, hochgeladene JSON- oder CSV-Datei. */
    public function import(): void
    {
        Auth::requireAdmin();
        $source = $_POST['source'] ?? 'online';

        try {
            if ($source === 'online') {
                $stats = ScheduleImporter::importOpenFootball((string) config('import.openfootball_url'));
            } elseif ($source === 'json' && !empty($_FILES['file']['tmp_name'])) {
                $stats = ScheduleImporter::importJson((string) file_get_contents($_FILES['file']['tmp_name']));
            } elseif ($source === 'csv' && !empty($_FILES['file']['tmp_name'])) {
                $stats = ScheduleImporter::importCsv((string) file_get_contents($_FILES['file']['tmp_name']));
            } else {
                Session::flash('error', 'Keine gültige Quelle/Datei ausgewählt.');
                redirect('/admin/spiele');
            }

            Session::flash('success', sprintf(
                'Import abgeschlossen: %d neu, %d aktualisiert, %d gewertet.',
                $stats['inserted'], $stats['updated'], $stats['scored']
            ));
        } catch (\Throwable $e) {
            Session::flash('error', 'Import fehlgeschlagen: ' . $e->getMessage());
        }

        redirect('/admin/spiele');
    }

    /** Ergebnis manuell setzen (Fallback ohne API). */
    public function saveResult(array $params): void
    {
        Auth::requireAdmin();
        $id = (int) $params['id'];

        $match = MatchModel::find($id);
        if (!$match) {
            Session::flash('error', 'Spiel nicht gefunden.');
            redirect('/admin/spiele');
        }

        $s1 = $_POST['score1'] ?? '';
        $s2 = $_POST['score2'] ?? '';

        // Leere Felder -> Ergebnis zurücksetzen (Status wieder offen).
        if ($s1 === '' || $s2 === '') {
            MatchModel::setResult($id, null, null, 'scheduled');
            Session::flash('success', 'Ergebnis zurückgesetzt.');
            redirect('/admin/spiele');
        }

        if (!ctype_digit((string) $s1) || !ctype_digit((string) $s2)) {
            Session::flash('error', 'Bitte gültige Torzahlen eingeben.');
            redirect('/admin/spiele');
        }

        // Optional: Verlängerung (n.V.) und Elfmeterschießen (i.E.) für KO-Spiele.
        // Jeweils nur gültig, wenn beide Felder eines Paares gefüllt sind.
        [$et1, $et2, $okEt]   = self::scorePair($_POST['et1'] ?? '', $_POST['et2'] ?? '');
        [$pen1, $pen2, $okPen] = self::scorePair($_POST['pen1'] ?? '', $_POST['pen2'] ?? '');
        if (!$okEt || !$okPen) {
            Session::flash('error', 'Bitte für Verlängerung/Elfmeter beide Torzahlen oder beide leer lassen.');
            redirect('/admin/spiele');
        }

        MatchModel::setResult($id, (int) $s1, (int) $s2, 'finished', $et1, $et2, $pen1, $pen2);
        ScoringService::scoreMatch($id);   // Tipps sofort werten
        Session::flash('success', 'Ergebnis gespeichert und Tipps gewertet.');
        redirect('/admin/spiele');
    }

    /**
     * Validiert ein optionales Tor-Paar (z.B. Verlängerung/Elfmeter).
     * @return array{0:?int,1:?int,2:bool}  [tore1, tore2, gültig?]
     *   Beide leer  -> [null, null, true]   (nicht zutreffend)
     *   Beide Zahl  -> [a, b, true]
     *   sonst       -> [null, null, false]  (ungültige Eingabe)
     */
    private static function scorePair($a, $b): array
    {
        $a = trim((string) $a);
        $b = trim((string) $b);
        if ($a === '' && $b === '') {
            return [null, null, true];
        }
        if (ctype_digit($a) && ctype_digit($b)) {
            return [(int) $a, (int) $b, true];
        }
        return [null, null, false];
    }

    /** Alle Punkte neu berechnen (z.B. nach Änderung des Punktesystems). */
    public function recalculate(): void
    {
        Auth::requireAdmin();
        $r = ScoringService::recalculateAll();
        Session::flash('success', sprintf(
            'Neu berechnet: %d Spiele, %d Bonusfragen.', $r['matches'], $r['bonus']
        ));
        redirect('/admin/spiele');
    }
}
