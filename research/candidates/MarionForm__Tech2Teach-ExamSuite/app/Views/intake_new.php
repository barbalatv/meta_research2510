<div class="card">
  <h2>Nuevo pack didáctico</h2>
  <?php if (!empty($error)): ?><div class="bad"><?= e($error) ?></div><?php endif; ?>

  <form method="post" action="/intake/crear">
    <label>Título</label>
    <input name="title" placeholder="Ej: Hardening Windows + Evidencias para Helpdesk" required>

    <label style="margin-top:10px;">Texto bruto (incidencia/temario/apuntes)</label>
    <textarea name="raw_text" rows="10" placeholder="Pega aquí el material..." required></textarea>

    <button class="btn" type="submit" style="margin-top:12px;">Generar pack</button>
  </form>
</div>
