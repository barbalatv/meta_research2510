<?php

declare(strict_types=1);

use Ledger\Database;
use Ledger\DomainException;
use Ledger\TransferService;

require dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($method === 'GET' && $path === '/health') {
    respond(200, ['status' => 'ok', 'service' => 'ledger-api', 'timestamp' => gmdate(DATE_ATOM)]);
}

$expectedKey = getenv('LEDGER_API_KEY') ?: '';
$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    respond(401, ['error' => ['code' => 'UNAUTHORISED', 'message' => 'A valid X-API-Key header is required.']]);
}

$dbPath = getenv('LEDGER_DB_PATH') ?: dirname(__DIR__) . '/var/ledger.sqlite';
$service = new TransferService(Database::connect($dbPath));

try {
    if ($method === 'POST' && $path === '/api/transfers') {
        $body = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($body)) {
            throw new JsonException('The request body must be a JSON object.');
        }
        $key = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '';
        $result = $service->transfer(
            filter_var($body['fromAccountId'] ?? null, FILTER_VALIDATE_INT) ?: 0,
            filter_var($body['toAccountId'] ?? null, FILTER_VALIDATE_INT) ?: 0,
            filter_var($body['amountMinor'] ?? null, FILTER_VALIDATE_INT) ?: 0,
            is_string($body['reference'] ?? null) ? $body['reference'] : '',
            $key
        );
        respond($result['duplicate'] ? 200 : 201, ['data' => $result]);
    }

    if ($method === 'GET' && preg_match('#^/api/accounts/(\d+)/entries$#', $path, $matches)) {
        respond(200, ['data' => $service->entriesForAccount((int) $matches[1])]);
    }

    respond(404, ['error' => ['code' => 'NOT_FOUND', 'message' => 'Route not found.']]);
} catch (DomainException $error) {
    respond($error->httpStatus, ['error' => ['code' => $error->errorCode, 'message' => $error->getMessage()]]);
} catch (JsonException) {
    respond(400, ['error' => ['code' => 'INVALID_JSON', 'message' => 'The request body is not valid JSON.']]);
} catch (Throwable $error) {
    error_log($error->getMessage());
    respond(500, ['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'An unexpected error occurred.']]);
}

