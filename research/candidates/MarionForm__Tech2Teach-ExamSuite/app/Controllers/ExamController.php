<?php
namespace App\Controllers;

use App\Models\Intake;
use App\Models\Exam;
use App\Services\ExamGeneratorService;
use App\Services\ExportService;
use App\Services\AnalyticsService;

class ExamController {

  public function generateForm(): void {
    $id = (string) req('id', '');
    $intake = $id ? Intake::find($id) : null;
    view('exam_generate', ['intake' => $intake]);
  }

  public function generate(): void {
    $intakeId = trim((string) req('intake_id', ''));
    $variant  = strtoupper(trim((string) req('variant', 'A')));
    $n        = (int) req('num_questions', 10);
    $level    = trim((string) req('level', 'mixto'));
    $topicHint= trim((string) req('topic_hint', ''));

    $variant = in_array($variant, ['A','B','C'], true) ? $variant : 'A';
    $n = max(5, min(60, $n));

    $examId = (new ExamGeneratorService())->generate($intakeId, $variant, $n, $level, $topicHint);

    redirect('/exam/tomar?id=' . urlencode($examId));
  }

  public function take(): void {
    $examId = (string) req('id', '');
    $exam = Exam::findWithQuestions($examId);

    if (!$exam) {
      http_response_code(404);
      echo "Examen no encontrado";
      return;
    }

    view('exam_take', ['exam' => $exam]);
  }

  public function grade(): void {
    $examId = (string) req('exam_id', '');
    $answers = $_POST['ans'] ?? [];

    $result = Exam::grade($examId, $answers);
    (new AnalyticsService())->recordAttempt($examId, $result);

    // ✅ REDIRECT LIMPIO A RESULTADOS
    redirect('/exam/resultados?id=' . urlencode($examId));
  }

  // ✅ NUEVO: PÁGINA DE RESULTADOS (separada y pro)
  public function results(): void {
    $examId = (string) req('id', '');
    $exam = Exam::findWithQuestions($examId);

    if (!$exam) {
      http_response_code(404);
      echo "Examen no encontrado";
      return;
    }

    view('exam_results', ['exam' => $exam]);
  }

  public function export(): void {
    $examId = (string) req('id', '');
    $type = (string) req('type', 'html'); // html | json | gift
    $export = (new ExportService())->exportExam($examId, $type);

    header('Content-Type: ' . $export['content_type']);
    header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
    echo $export['content'];
  }
}
