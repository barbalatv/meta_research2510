<?php
namespace App\Services;

class AnalyticsService {
  public function recordAttempt(string $examId, array $result): void {
    // MVP: ya guardamos attempt en Exam::grade. Aquí podrías agregar agregados por pregunta.
    if (!($result['ok'] ?? false)) return;

    $upd = \db()->prepare("UPDATE questions SET times_seen = times_seen + 1, times_wrong = times_wrong + ? WHERE id=?");
    foreach ($result['detail'] as $d) {
      $wrong = ($d['is_correct'] ?? 0) ? 0 : 1;
      $upd->execute([$wrong, $d['question_id']]);
    }
  }
}
