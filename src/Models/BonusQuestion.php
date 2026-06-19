<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class BonusQuestion
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM bonus_questions WHERE id = ?', [$id]);
    }

    public static function all(): array
    {
        return DB::all('SELECT * FROM bonus_questions ORDER BY id');
    }

    public static function active(): array
    {
        return DB::all('SELECT * FROM bonus_questions WHERE is_active = 1 ORDER BY id');
    }

    public static function create(string $qtype, string $question, int $points, ?string $deadline): int
    {
        return DB::insert(
            'INSERT INTO bonus_questions (qtype, question, points, deadline, is_active, created_at)
             VALUES (?, ?, ?, ?, 1, ?)',
            [$qtype, $question, $points, $deadline ?: null, DB::now()]
        );
    }

    public static function update(int $id, string $question, int $points, ?string $deadline, int $isActive): void
    {
        DB::run(
            'UPDATE bonus_questions SET question = ?, points = ?, deadline = ?, is_active = ? WHERE id = ?',
            [$question, $points, $deadline ?: null, $isActive, $id]
        );
    }

    /** Richtige Antwort setzen – damit werden die Punkte vergeben. */
    public static function resolve(int $id, string $correctAnswer): void
    {
        DB::run('UPDATE bonus_questions SET correct_answer = ? WHERE id = ?', [$correctAnswer, $id]);
    }

    public static function delete(int $id): void
    {
        DB::run('DELETE FROM bonus_questions WHERE id = ?', [$id]);
    }

    // --- Antworten ----------------------------------------------------

    public static function answersForUser(int $userId): array
    {
        $rows = DB::all('SELECT * FROM bonus_answers WHERE user_id = ?', [$userId]);
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['bonus_question_id']] = $r;
        }
        return $map;
    }

    public static function saveAnswer(int $userId, int $questionId, string $answer): void
    {
        $existing = DB::one('SELECT id FROM bonus_answers WHERE user_id = ? AND bonus_question_id = ?',
            [$userId, $questionId]);
        if ($existing) {
            DB::run('UPDATE bonus_answers SET answer = ?, points = NULL, updated_at = ? WHERE id = ?',
                [$answer, DB::now(), $existing['id']]);
        } else {
            DB::insert(
                'INSERT INTO bonus_answers (user_id, bonus_question_id, answer, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?)',
                [$userId, $questionId, $answer, DB::now(), DB::now()]
            );
        }
    }

    public static function answersForQuestion(int $questionId): array
    {
        return DB::all('SELECT * FROM bonus_answers WHERE bonus_question_id = ?', [$questionId]);
    }
}
