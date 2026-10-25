# Defect investigation: duplicate transfers on client retry

## Symptom

A caller timed out after submitting a transfer. It retried the same request and the account was charged twice.

## Root cause

The original design treated every HTTP request as a new instruction. A network timeout tells the client that the response was lost, not whether the database commit failed.

## Fix

The API now requires a caller-generated idempotency key. A unique SQL constraint prevents two records using the same key, and a retry returns the original transfer. The balance updates and transfer record remain inside one database transaction.

## Regression coverage

`returns original result for an idempotent retry` submits the same key twice, asserts that both responses identify the same transfer, and confirms the source account is debited once.

## Further hardening

In a multi-worker production database, catch unique-constraint races and fetch the winning record. Define how long keys remain valid and bind each key to a request-body hash so a caller cannot accidentally reuse it for different transfer details.

