<?php
namespace App\Models;

use PDO;

class Exam {

  public static function create(string $intakeId, string $variant, array $questions): string {
    $examId = \uuid();
    $stmt = \db()->prepare("INSERT INTO exams(id,intake_id,variant,created_at) VALUES(?,?,?,datetime('now'))");
    $stmt->execute([$examId, $intakeId ?: null, $variant]);

    $ins = \db()->prepare("INSERT INTO exam_questions(exam_id,question_id,q_order) VALUES(?,?,?)");
    $order = 1;
    foreach ($questions as $q) {
      $ins->execute([$examId, $q['id'], $order++]);
    }
    return $examId;
  }

  public static function findWithQuestions(string $examId): ?array {
    $stmt = \db()->prepare("SELECT * FROM exams WHERE id=?");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$exam) return null;

    $exam['questions'] = Question::findManyByExam($examId);
    return $exam;
  }

  public static function grade(string $examId, array $answers): array {
    $exam = self::findWithQuestions($examId);
    if (!$exam) return ['ok'=>false,'error'=>'Examen no encontrado'];

    $total = count($exam['questions']);
    $correct = 0;
    $detail = [];

    foreach ($exam['questions'] as $q) {
      $qid = $q['id'];
      $given = $answers[$qid] ?? null;
      $isOk = ($given !== null) && ((string)$given === (string)$q['correct_choice']);
      if ($isOk) $correct++;

      $detail[] = [
        'question_id' => $qid,
        'given' => $given,
        'correct' => $q['correct_choice'],
        'is_correct' => $isOk ? 1 : 0,
        'topic' => $q['topic'],
        'difficulty' => $q['difficulty'],
      ];
    }

    $score = $total > 0 ? round(($correct / $total) * 10, 2) : 0.0;

    // Guarda intento (simple)
    $attemptId = \uuid();
    $stmt = \db()->prepare("INSERT INTO attempts(id,exam_id,score_10,correct,total,created_at,detail_json) VALUES(?,?,?,?,?,datetime('now'),?)");
    $stmt->execute([$attemptId, $examId, $score, $correct, $total, json_encode($detail, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)]);

    return [
      'ok' => true,
      'attempt_id' => $attemptId,
      'score_10' => $score,
      'correct' => $correct,
      'total' => $total,
      'detail' => $detail
    ];
  }

  public static function lastAttempt(string $examId): ?array {
    $stmt = \db()->prepare("SELECT * FROM attempts WHERE exam_id=? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$examId]);
    $a = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$a) return null;
    $a['detail'] = $a['detail_json'] ? json_decode($a['detail_json'], true) : [];
    return $a;
  }
}
