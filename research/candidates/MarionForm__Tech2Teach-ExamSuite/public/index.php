<?php
declare(strict_types=1);

require __DIR__ . '/../app/config.php';
require APP_PATH . '/helpers.php';
require APP_PATH . '/db.php';
require APP_PATH . '/Router.php';

/**
 * ✅ Autoload corretto:
 * - Namespace "App\..." viene mappato direttamente dentro /app/...
 * - Esempio: App\Controllers\HomeController -> app/Controllers/HomeController.php
 */
spl_autoload_register(function ($class) {
  $prefix = 'App\\';
  if (strpos($class, $prefix) === 0) {
    $relative = substr($class, strlen($prefix)); // Controllers\HomeController
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
    $path = APP_PATH . DIRECTORY_SEPARATOR . $relative . '.php';
    if (file_exists($path)) {
      require $path;
    }
  }
});

$router = new App\Router();

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/
$router->get('/', [App\Controllers\HomeController::class, 'index']);

/*
|--------------------------------------------------------------------------
| INTAKE / PACK DIDÁCTICO
|--------------------------------------------------------------------------
*/
$router->get('/intake/nuevo', [App\Controllers\IntakeController::class, 'new']);
$router->post('/intake/crear', [App\Controllers\IntakeController::class, 'create']);
$router->get('/pack/ver', [App\Controllers\PackController::class, 'view']);

/*
|--------------------------------------------------------------------------
| EXÁMENES
|--------------------------------------------------------------------------
*/
$router->get('/exam/generar', [App\Controllers\ExamController::class, 'generateForm']);
$router->post('/exam/generar', [App\Controllers\ExamController::class, 'generate']);
$router->get('/exam/tomar', [App\Controllers\ExamController::class, 'take']);
$router->post('/exam/corregir', [App\Controllers\ExamController::class, 'grade']);
$router->get('/exam/resultados', [App\Controllers\ExamController::class, 'results']);
$router->get('/exam/export', [App\Controllers\ExamController::class, 'export']);

/*
|--------------------------------------------------------------------------
| DISPATCH
|--------------------------------------------------------------------------
*/
$router->dispatch(
  $_SERVER['REQUEST_METHOD'],
  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);
