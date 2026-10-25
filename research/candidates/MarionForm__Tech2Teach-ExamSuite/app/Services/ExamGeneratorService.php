<?php
namespace App\Services;

use App\Models\Question;
use App\Models\Exam;

class ExamGeneratorService {

  public function generate(string $intakeId, string $variant, int $n, string $level, string $topicHint): string {
    // Estrategia: mitad por topicHint (si existe), resto random
    $pick1 = [];
    if (trim($topicHint) !== '') {
      $pick1 = Question::pickRandom((int)floor($n/2), $level, $topicHint);
    }
    $remaining = $n - count($pick1);
    $pick2 = Question::pickRandom($remaining, $level, '');

    // Merge sin duplicados
    $seen = [];
    $final = [];
    foreach (array_merge($pick1, $pick2) as $q) {
      if (isset($seen[$q['id']])) continue;
      $seen[$q['id']] = true;
      $final[] = $q;
      if (count($final) >= $n) break;
    }

    // Si faltan, rellena
    if (count($final) < $n) {
      $extra = Question::pickRandom($n - count($final), 'mixto', '');
      foreach ($extra as $q) {
        if (isset($seen[$q['id']])) continue;
        $final[] = $q;
        if (count($final) >= $n) break;
      }
    }

    return Exam::create($intakeId, $variant, $final);
  }
}
