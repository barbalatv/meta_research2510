<?php
use App\Models\Exam;

// $exam arriva dal controller
$attempt = Exam::lastAttempt($exam['id']);
?>
<div class="card">
  <h2>Resultados · Examen <?= e($exam['variant']) ?></h2>
  <p class="small">ID examen: <?= e($exam['id']) ?> · Creado: <?= e($exam['created_at']) ?></p>

  <?php if (!$attempt): ?>
    <div class="bad">No hay intentos guardados todavía para este examen.</div>
    <a class="btn" href="/exam/tomar?id=<?= e($exam['id']) ?>">Volver al examen</a>
  <?php else: ?>
    <div class="ok">
      ✅ Nota: <strong><?= e((string)$attempt['score_10']) ?>/10</strong>
      · Aciertos: <?= e((string)$attempt['correct']) ?>/<?= e((string)$attempt['total']) ?>
      · Intento: <?= e((string)$attempt['created_at']) ?>
    </div>

    <hr>

    <h3>Detalle por pregunta</h3>

    <?php
      // Mapa rápido id->pregunta
      $qmap = [];
      foreach ($exam['questions'] as $q) $qmap[$q['id']] = $q;
    ?>

    <?php foreach (($attempt['detail'] ?? []) as $idx => $d): ?>
      <?php $q = $qmap[$d['question_id']] ?? null; ?>
      <div class="card">
        <strong><?= ($idx + 1) ?>)</strong>
        <?= e($q['question_text'] ?? 'Pregunta no encontrada') ?>

        <div style="margin-top:10px;">
          <div class="<?= ($d['is_correct'] ?? 0) ? 'ok' : 'bad' ?>">
            Tu respuesta: <strong><?= e((string)($d['given'] ?? '—')) ?></strong>
            · Correcta: <strong><?= e((string)($d['correct'] ?? '—')) ?></strong>
          </div>
        </div>

        <?php if ($q): ?>
          <?php $choices = json_decode($q['choices_json'], true) ?: []; ?>
          <div class="small" style="margin-top:10px;">
            <strong>Opciones:</strong>
            <?php foreach ($choices as $k => $t): ?>
              <div>[<?= e((string)$k) ?>] <?= e((string)$t) ?></div>
            <?php endforeach; ?>
          </div>

          <div class="small" style="margin-top:10px;">
            Tema: <?= e((string)($d['topic'] ?? $q['topic'] ?? '')) ?>
            · Nivel: <?= e((string)($d['difficulty'] ?? $q['difficulty'] ?? '')) ?>
          </div>

          <?php if (!empty($q['explanation'])): ?>
            <div class="small" style="margin-top:10px;">
              <strong>Explicación:</strong> <?= e((string)$q['explanation']) ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div class="grid">
      <a class="btn" href="/exam/tomar?id=<?= e($exam['id']) ?>">Revisar examen</a>
      <a class="btn secondary" href="/exam/generar">Generar otro examen</a>
    </div>
  <?php endif; ?>
</div>
