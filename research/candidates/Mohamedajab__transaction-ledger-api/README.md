# Ledger API

A dependency-free PHP 8.2 REST API for transferring funds between accounts, focused on the reliability and auditability concerns found in payment software.

## Features

- Integer minor-unit amounts to avoid floating-point rounding errors
- Atomic debit/credit updates using a SQL transaction
- Idempotency keys to make retried requests safe
- Parameterised SQL queries
- API-key authentication using a constant-time comparison
- Consistent JSON errors and request validation
- Unit tests using an in-memory SQLite database
- Health endpoint suitable for an integration monitor

## Run locally

Requirements: PHP 8.2+ with `pdo_sqlite`.

```bash
cp .env.example .env
php scripts/init_db.php
LEDGER_API_KEY=local-development-key php -S localhost:8080 -t public public/index.php
```

PowerShell:

```powershell
$env:LEDGER_API_KEY = "local-development-key"
php scripts/init_db.php
php -S localhost:8080 -t public public/index.php
```

Check health:

```bash
curl http://localhost:8080/health
```

Create a transfer:

```bash
curl -X POST http://localhost:8080/api/transfers \
  -H "Content-Type: application/json" \
  -H "X-API-Key: local-development-key" \
  -H "Idempotency-Key: transfer-request-001" \
  -d '{"fromAccountId":1,"toAccountId":2,"amountMinor":1250,"reference":"Coffee supplier"}'
```

List an account's entries:

```bash
curl http://localhost:8080/api/accounts/1/entries -H "X-API-Key: local-development-key"
```

## Tests

```bash
php tests/run.php
```

The suite covers successful transfers, insufficient funds, duplicate idempotency keys, invalid accounts and rollback behaviour.

## API decisions

- `amountMinor` is an integer in pence. A value of `1250` means £12.50.
- A duplicate idempotency key returns the original transfer rather than moving money twice.
- Both balance changes and the audit record commit together or roll back together.
- Error responses include a stable machine-readable `code` and a human-readable `message`.

## Production next steps

Replace SQLite with Azure Database for PostgreSQL, use Microsoft Entra ID or a managed API gateway instead of a shared key, send structured logs to Application Insights, add rate limiting, and run migrations through a deployment pipeline. For concurrent high-volume transfers, use row-level locking and test the chosen database isolation level.

See [DEFECT-NOTES.md](./docs/DEFECT-NOTES.md) for an example investigation record and [API.md](./docs/API.md) for the endpoint contract.

