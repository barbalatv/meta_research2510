<?php

declare(strict_types=1);

use Ledger\Database;
use Ledger\DomainException;
use Ledger\TransferService;

require dirname(__DIR__) . '/src/bootstrap.php';

function serviceFixture(): array
{
    $db = Database::connect(':memory:');
    $db->exec(file_get_contents(dirname(__DIR__) . '/migrations/001_create_ledger.sql'));
    $db->exec("INSERT INTO accounts (name, balance_minor) VALUES ('Source', 10000), ('Target', 1000)");
    return [$db, new TransferService($db)];
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . '; expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

$tests = [];

$tests['moves money and records the transfer'] = function (): void {
    [$db, $service] = serviceFixture();
    $result = $service->transfer(1, 2, 2500, 'Invoice 42', 'transfer-0001');
    assertSameValue(false, $result['duplicate'], 'First request is not a duplicate');
    assertSameValue([7500, 3500], array_map('intval', $db->query('SELECT balance_minor FROM accounts ORDER BY id')->fetchAll(PDO::FETCH_COLUMN)), 'Balances change');
    assertSameValue(1, (int) $db->query('SELECT COUNT(*) FROM transfers')->fetchColumn(), 'Transfer is recorded');
};

$tests['returns original result for an idempotent retry'] = function (): void {
    [$db, $service] = serviceFixture();
    $first = $service->transfer(1, 2, 500, 'Retry-safe payment', 'transfer-0002');
    $second = $service->transfer(1, 2, 500, 'Retry-safe payment', 'transfer-0002');
    assertSameValue($first['id'], $second['id'], 'Same transfer is returned');
    assertSameValue(true, $second['duplicate'], 'Retry is marked duplicate');
    assertSameValue(9500, (int) $db->query('SELECT balance_minor FROM accounts WHERE id = 1')->fetchColumn(), 'Account debited once');
};

$tests['rejects insufficient funds without partial updates'] = function (): void {
    [$db, $service] = serviceFixture();
    try {
        $service->transfer(1, 2, 10001, 'Too large', 'transfer-0003');
        throw new RuntimeException('Expected insufficient-funds error');
    } catch (DomainException $error) {
        assertSameValue('INSUFFICIENT_FUNDS', $error->errorCode, 'Correct error code');
    }
    assertSameValue([10000, 1000], array_map('intval', $db->query('SELECT balance_minor FROM accounts ORDER BY id')->fetchAll(PDO::FETCH_COLUMN)), 'Balances remain unchanged');
};

$tests['rolls back debit when destination does not exist'] = function (): void {
    [$db, $service] = serviceFixture();
    try {
        $service->transfer(1, 999, 1000, 'Missing target', 'transfer-0004');
        throw new RuntimeException('Expected account-not-found error');
    } catch (DomainException $error) {
        assertSameValue('ACCOUNT_NOT_FOUND', $error->errorCode, 'Correct error code');
    }
    assertSameValue(10000, (int) $db->query('SELECT balance_minor FROM accounts WHERE id = 1')->fetchColumn(), 'Debit is rolled back');
};

$failures = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo "PASS: {$name}" . PHP_EOL;
    } catch (Throwable $error) {
        $failures++;
        echo "FAIL: {$name} — {$error->getMessage()}" . PHP_EOL;
    }
}

echo PHP_EOL . count($tests) . ' tests, ' . $failures . ' failures' . PHP_EOL;
exit($failures === 0 ? 0 : 1);

