<?php
declare(strict_types=1);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function redirect(string $path): void {
  header("Location: $path");
  exit;
}

function view(string $file, array $data = []): void {
  extract($data);
  $viewFile = APP_PATH . '/Views/' . $file . '.php';
  if (!file_exists($viewFile)) {
    http_response_code(500);
    echo "Vista no encontrada: " . e($file);
    exit;
  }
  require APP_PATH . '/Views/layout.php';
}

function req(string $key, $default = null) {
  return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function uuid(): string {
  return bin2hex(random_bytes(16));
}

function ensure_dirs(): void {
  if (!is_dir(EXPORTS_PATH)) mkdir(EXPORTS_PATH, 0777, true);
  if (!is_dir(UPLOADS_PATH)) mkdir(UPLOADS_PATH, 0777, true);
}
