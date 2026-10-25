<?php
// $viewFile viene de helpers.php
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tech2Teach ExamSuite</title>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
  <header class="top">
    <div class="wrap">
      <div class="brand">⚡ Tech2Teach ExamSuite</div>
      <nav>
        <a href="/">Inicio</a>
        <a href="/intake/nuevo">Nuevo pack</a>
        <a href="/exam/generar">Generar examen</a>
      </nav>
    </div>
  </header>

  <main class="wrap">
    <?php require $viewFile; ?>
  </main>

  <footer class="wrap foot">
    MVP · PHP + SQLite · Export HTML/JSON/GIFT · Ctrl+P → PDF
  </footer>
</body>
</html>
