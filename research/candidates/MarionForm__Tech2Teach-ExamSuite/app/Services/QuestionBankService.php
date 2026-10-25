<?php
namespace App\Services;

class QuestionBankService {
  public function importArray(array $items): int {
    $ins = \db()->prepare("
      INSERT INTO questions(
        id, topic, tags, difficulty, question_text,
        choices_json, correct_choice, explanation,
        times_seen, times_wrong
      ) VALUES(?,?,?,?,?,?,?,?,0,0)
    ");

    $count = 0;

    foreach ($items as $q) {
      $ins->execute([
        $q['id'] ?? \uuid(),
        $q['topic'] ?? 'General',
        $q['tags'] ?? '',
        $q['difficulty'] ?? 'mixto',
        $q['question_text'] ?? '',
        json_encode($q['choices'] ?? [], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
        (string)($q['correct_choice'] ?? 'A'),
        $q['explanation'] ?? ''
      ]);
      $count++;
    }

    return $count;
  }
}
