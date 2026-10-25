<?php

declare(strict_types=1);

use Ledger\Database;

require dirname(__DIR__) . '/src/bootstrap.php';

$root = dirname(__DIR__);
$path = getenv('LEDGER_DB_PATH') ?: $root . '/var/ledger.sqlite';
$db = Database::connect($path);
$db->exec(file_get_contents($root . '/migrations/001_create_ledger.sql'));

$count = (int) $db->query('SELECT COUNT(*) FROM accounts')->fetchColumn();
if ($count === 0) {
    $insert = $db->prepare('INSERT INTO accounts (name, balance_minor) VALUES (:name, :balance)');
    $insert->execute(['name' => 'Demo Current Account', 'balance' => 250000]);
    $insert->execute(['name' => 'Demo Savings Account', 'balance' => 50000]);
}

echo "Ledger database ready at {$path}" . PHP_EOL;

