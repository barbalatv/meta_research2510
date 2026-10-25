<?php
use App\Models\Exam;

$showResults = (string)($_GET['results'] ?? '') === '1';
$attempt = $showResults ? Exam::lastAttempt($exam['id']) : null;
?>
<div class="card">
  <h2>Examen <?= e($exam['variant']) ?></h2>
  <p class="small">ID: <?= e($exam['id']) ?> · Creado: <?= e($exam['created_at']) ?></p>

  <div class="grid">
    <a class="btn secondary" href="/exam/export?id=<?= e($exam['id']) ?>&type=html">Export HTML printable</a>
    <a class="btn secondary" href="/exam/export?id=<?= e($exam['id']) ?>&type=json">Export JSON</a>
    <a class="btn secondary" href="/exam/export?id=<?= e($exam['id']) ?>&type=gift">Export Moodle GIFT</a>
  </div>

  <?php if ($attempt): ?>
    <div class="ok" style="margin-top:12px;">
      ✅ Nota: <strong><?= e((string)$attempt['score_10']) ?>/10</strong> · Aciertos: <?= e((string)$attempt['correct']) ?>/<?= e((string)$attempt['total']) ?>
    </div>
  <?php endif; ?>

  <form method="post" action="/exam/corregir">
    <input type="hidden" name="exam_id" value="<?= e($exam['id']) ?>">

    <?php foreach ($exam['questions'] as $idx => $q): ?>
      <div class="card">
        <strong><?= ($idx+1) ?>)</strong> <?= e($q['question_text']) ?>
        <?php $choices = json_decode($q['choices_json'], true) ?: []; ?>
        <?php foreach ($choices as $k => $t): ?>
          <div style="margin-top:6px;">
            <label>
              <input type="radio" name="ans[<?= e($q['id']) ?>]" value="<?= e((string)$k) ?>">
              [<?= e((string)$k) ?>] <?= e($t) ?>
            </label>
          </div>
        <?php endforeach; ?>

        <?php if ($attempt): ?>
          <?php
            $d = null;
            foreach ($attempt['detail'] as $it) if ($it['question_id'] === $q['id']) { $d = $it; break; }
          ?>
          <?php if ($d): ?>
            <hr>
            <div class="<?= $d['is_correct'] ? 'ok' : 'bad' ?>">
              Tu respuesta: <?= e((string)$d['given']) ?> · Correcta: <?= e((string)$d['correct']) ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <button class="btn" type="submit">Corregir</button>
    <?php if (!$showResults): ?>
      <p class="small">Tras corregir, se guarda el intento y se actualiza analítica por pregunta (seen/wrong).</p>
    <?php endif; ?>
  </form>
</div>
