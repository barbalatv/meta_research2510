<?php
namespace App\Services;

class DidacticPackService {

  public function buildFromRaw(string $title, string $raw): array {
    // Heurística simple + plantilla (sin IA externa)
    $bullets = $this->extractBullets($raw);
    $keywords = $this->keywords($raw);

    $objectives = array_slice(array_map(fn($k)=>"Comprender y aplicar: $k", $keywords), 0, 6);

    $exercises = [];
    foreach (array_slice($keywords, 0, 6) as $k) {
      $exercises[] = [
        'title' => "Ejercicio práctico: $k",
        'statement' => "Explica con tus palabras $k y crea un ejemplo real (caso helpdesk o aula).",
        'deliverable' => "Documento breve + evidencia (captura/log) si aplica."
      ];
    }

    $rubric = [
      ['criterio'=>'Claridad técnica', 'niveles'=>['0'=>'Confuso', '1'=>'Aceptable', '2'=>'Claro', '3'=>'Excelente']],
      ['criterio'=>'Evidencias', 'niveles'=>['0'=>'Sin evidencias', '1'=>'Pocas', '2'=>'Adecuadas', '3'=>'Completas']],
      ['criterio'=>'Troubleshooting', 'niveles'=>['0'=>'No aplica', '1'=>'Básico', '2'=>'Correcto', '3'=>'Profesional']],
      ['criterio'=>'Seguridad', 'niveles'=>['0'=>'Ignorada', '1'=>'Mencionada', '2'=>'Aplicada', '3'=>'Integrada']],
    ];

    return [
      'title' => $title,
      'summary' => $this->summary($raw),
      'keywords' => $keywords,
      'guia_didactica' => [
        'objetivos' => $objectives,
        'contenidos' => array_slice($bullets, 0, 12),
        'metodologia' => [
          "Aprender haciendo: caso real → pasos → evidencias.",
          "Trabajo por parejas: rol técnico/rol auditor.",
          "Cierre: mini-retro y checklist de hardening."
        ],
        'evaluacion' => "Evaluación por rúbrica + test tipo examen."
      ],
      'ejercicios' => $exercises,
      'rubrica' => $rubric
    ];
  }

  private function extractBullets(string $raw): array {
    $lines = preg_split("/\r\n|\n|\r/", $raw);
    $out = [];
    foreach ($lines as $l) {
      $l = trim($l);
      if ($l === '') continue;
      if (preg_match('/^[-•*]\s+(.+)/', $l, $m)) $out[] = $m[1];
      elseif (strlen($l) < 120) $out[] = $l;
    }
    return array_values(array_unique($out));
  }

  private function keywords(string $raw): array {
    $raw = mb_strtolower($raw);
    $candidates = preg_split('/[^a-záéíóúñ0-9]+/u', $raw);
    $stop = ['de','la','el','y','o','a','en','para','por','con','sin','del','los','las','un','una','que','se','es','al','como','más','menos','muy','tipo'];
    $freq = [];
    foreach ($candidates as $w) {
      if (mb_strlen($w) < 4) continue;
      if (in_array($w, $stop, true)) continue;
      $freq[$w] = ($freq[$w] ?? 0) + 1;
    }
    arsort($freq);
    return array_slice(array_keys($freq), 0, 10);
  }

  private function summary(string $raw): string {
    $raw = trim(preg_replace('/\s+/', ' ', $raw));
    return mb_substr($raw, 0, 240) . (mb_strlen($raw) > 240 ? '…' : '');
  }
}
