<div class="card">
  <h2>Pack: <?= e($intake['title']) ?></h2>
  <p class="small">ID: <?= e($intake['id']) ?> · Creado: <?= e($intake['created_at']) ?></p>

  <?php $p = $intake['pack'] ?? null; ?>
  <?php if (!$p): ?>
    <div class="bad">No hay pack generado.</div>
  <?php else: ?>
    <h3>Resumen</h3>
    <p><?= e($p['summary'] ?? '') ?></p>

    <h3>Guía didáctica</h3>
    <div class="card">
      <strong>Objetivos</strong>
      <ul>
        <?php foreach (($p['guia_didactica']['objetivos'] ?? []) as $o): ?>
          <li><?= e($o) ?></li>
        <?php endforeach; ?>
      </ul>
      <strong>Contenidos</strong>
      <ul>
        <?php foreach (($p['guia_didactica']['contenidos'] ?? []) as $c): ?>
          <li><?= e($c) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <h3>Ejercicios</h3>
    <?php foreach (($p['ejercicios'] ?? []) as $ex): ?>
      <div class="card">
        <strong><?= e($ex['title']) ?></strong>
        <p><?= e($ex['statement']) ?></p>
        <p class="small">Entregable: <?= e($ex['deliverable']) ?></p>
      </div>
    <?php endforeach; ?>

    <h3>Rúbrica (base)</h3>
    <div class="card">
      <?php foreach (($p['rubrica'] ?? []) as $r): ?>
        <div><strong><?= e($r['criterio']) ?>:</strong>
          <?php foreach (($r['niveles'] ?? []) as $k=>$v): ?>
            <span class="small">[<?= e((string)$k) ?> <?= e($v) ?>]</span>
          <?php endforeach; ?>
        </div>
        <hr>
      <?php endforeach; ?>
    </div>

    <div class="grid">
      <a class="btn" href="/exam/generar?id=<?= e($intake['id']) ?>">Generar examen para este pack</a>
      <a class="btn secondary" href="/exam/generar">Generar examen general</a>
    </div>
  <?php endif; ?>
</div>
