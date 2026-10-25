# API contract

## `GET /health`

Public liveness endpoint. Returns `200` with service name, status and UTC timestamp.

## `POST /api/transfers`

Required headers: `X-API-Key`, `Idempotency-Key`, `Content-Type: application/json`.

```json
{
  "fromAccountId": 1,
  "toAccountId": 2,
  "amountMinor": 1250,
  "reference": "Invoice 42"
}
```

Returns `201` for a new transfer or `200` for a safe retry. Expected errors include `400` validation failures, `401` authentication failure, `404` missing accounts and `409` insufficient funds.

## `GET /api/accounts/{id}/entries`

Requires `X-API-Key`. Returns debit and credit entries in newest-first order.

