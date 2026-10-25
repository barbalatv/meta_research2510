<?php
namespace App\Models;

use PDO;

class Question {

  public static function pickRandom(int $n, string $level = 'mixto', string $topicHint = ''): array {
    $where = [];
    $params = [];

    if ($level !== 'mixto') {
      $where[] = "difficulty = ?";
      $params[] = $level;
    }
    if (trim($topicHint) !== '') {
      $where[] = "(topic LIKE ? OR tags LIKE ?)";
      $params[] = '%' . $topicHint . '%';
      $params[] = '%' . $topicHint . '%';
    }

    $sql = "SELECT * FROM questions";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY RANDOM() LIMIT " . (int)$n;

    $stmt = \db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function findManyByExam(string $examId): array {
    $stmt = \db()->prepare("
      SELECT q.*, eq.q_order
      FROM exam_questions eq
      JOIN questions q ON q.id = eq.question_id
      WHERE eq.exam_id = ?
      ORDER BY eq.q_order ASC
    ");
    $stmt->execute([$examId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
