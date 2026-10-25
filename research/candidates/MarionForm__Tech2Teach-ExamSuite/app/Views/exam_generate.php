<div class="card">
  <h2>Generar examen</h2>
  <form method="post" action="/exam/generar">
    <input type="hidden" name="intake_id" value="<?= e($intake['id'] ?? '') ?>">

    <div class="grid">
      <div>
        <label>Variante</label>
        <select name="variant">
          <option value="A">A</option>
          <option value="B">B</option>
          <option value="C">C</option>
        </select>
      </div>
      <div>
        <label>Nº de preguntas</label>
        <input type="number" name="num_questions" value="10" min="5" max="60">
      </div>
    </div>

    <div class="grid">
      <div>
        <label>Dificultad</label>
        <select name="level">
          <option value="mixto">Mixto</option>
          <option value="facil">Fácil</option>
          <option value="medio">Medio</option>
          <option value="dificil">Difícil</option>
        </select>
      </div>
      <div>
        <label>Filtro tema (opcional)</label>
        <input name="topic_hint" placeholder="Ej: active directory, dns, sql, hardening...">
      </div>
    </div>

    <?php if (!empty($intake)): ?>
      <p class="small">Generando para pack: <strong><?= e($intake['title']) ?></strong></p>
    <?php endif; ?>

    <button class="btn" type="submit" style="margin-top:12px;">Crear examen</button>
  </form>

  <p class="small">Necesitas banco de preguntas: ejecuta <code>php scripts/init_db.php</code> + <code>php scripts/seed_questions.php</code>.</p>
</div>
