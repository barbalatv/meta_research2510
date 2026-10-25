<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('EXPORTS_PATH', STORAGE_PATH . '/exports');
define('UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('DB_PATH', STORAGE_PATH . '/tech2teach.sqlite');

date_default_timezone_set('Europe/Madrid');

ini_set('display_errors', '1');
error_reporting(E_ALL);
