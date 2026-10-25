<?php
namespace App\Services;

use App\Models\Exam;
use App\Models\Intake;

class ExportService {

  public function exportExam(string $examId, string $type): array {
    $exam = Exam::findWithQuestions($examId);
    if (!$exam) {
      return ['content_type'=>'text/plain','filename'=>'error.txt','content'=>'Examen no encontrado'];
    }

    $intake = $exam['intake_id'] ? Intake::find($exam['intake_id']) : null;

    if ($type === 'json') return $this->asJson($exam, $intake);
    if ($type === 'gift') return $this->asGift($exam, $intake);

    return $this->asHtmlPrintable($exam, $intake);
  }

  private function asJson(array $exam, ?array $intake): array {
    $payload = ['exam'=>$exam, 'intake'=>$intake];
    return [
      'content_type' => 'application/json; charset=utf-8',
      'filename' => "exam_{$exam['id']}.json",
      'content' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)
    ];
  }

  private function asGift(array $exam, ?array $intake): array {
    $out = [];
    $title = "Examen {$exam['variant']} - " . ($intake['title'] ?? 'General');
    $out[] = "// $title";
    $out[] = "";

    foreach ($exam['questions'] as $i => $q) {
      $name = "Q" . ($i+1) . " " . ($q['topic'] ?: 'General');
      $out[] = "::" . $this->clean($name) . ":: " . $this->clean($q['question_text']);
      $choices = json_decode($q['choices_json'], true) ?: [];
      $correct = (string)$q['correct_choice'];

      $giftChoices = [];
      foreach ($choices as $key => $text) {
        $prefix = ((string)$key === $correct) ? "=" : "~";
        $giftChoices[] = $prefix . $this->clean($text);
      }
      $out[] = "{" . implode(" ", $giftChoices) . "}";
      $out[] = "";
    }

    return [
      'content_type' => 'text/plain; charset=utf-8',
      'filename' => "exam_{$exam['id']}.gift.txt",
      'content' => implode("\n", $out)
    ];
  }

  private function asHtmlPrintable(array $exam, ?array $intake): array {
    $title = "Examen {$exam['variant']} - " . e($intake['title'] ?? 'General');

    ob_start();
    ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= $title ?></title>
  <style>
    body{font-family: Arial, sans-serif; margin: 24px;}
    h1{margin:0 0 6px 0;}
    .meta{color:#444; margin-bottom:16px;}
    .q{margin:16px 0; padding:12px; border:1px solid #ddd; border-radius:10px;}
    .choices{margin-top:10px;}
    .choices div{margin:6px 0;}
    .footer{margin-top:26px; color:#666; font-size:12px;}
  </style>
</head>
<body>
  <h1><?= $title ?></h1>
  <div class="meta">
    Fecha: <?= date('Y-m-d H:i') ?> · ID: <?= e($exam['id']) ?> · Dificultad: (según banco)
  </div>

  <?php foreach ($exam['questions'] as $idx => $q): ?>
    <div class="q">
      <strong><?= ($idx+1) ?>)</strong> <?= e($q['question_text']) ?>
      <div class="choices">
        <?php $choices = json_decode($q['choices_json'], true) ?: []; ?>
        <?php foreach ($choices as $k => $t): ?>
          <div>[<?= e($k) ?>] <?= e($t) ?></div>
        <?php endforeach; ?>
      </div>
      <div class="footer">Tema: <?= e($q['topic']) ?> · Nivel: <?= e($q['difficulty']) ?></div>
    </div>
  <?php endforeach; ?>

  <div class="footer">
    Imprime este HTML y guarda como PDF (Ctrl+P) para “PDF oficial”.
  </div>
</body>
</html>
    <?php
    $html = ob_get_clean();

    return [
      'content_type' => 'text/html; charset=utf-8',
      'filename' => "exam_{$exam['id']}.print.html",
      'content' => $html
    ];
  }

  private function clean(string $s): string {
    $s = preg_replace('/\s+/', ' ', trim($s));
    return str_replace(['{','}','::'], ['(',' )','-'], $s);
  }
}
