<?php

declare(strict_types=1);

namespace Ledger;

use PDO;
use Throwable;

final class TransferService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<string, mixed> */
    public function transfer(
        int $fromAccountId,
        int $toAccountId,
        int $amountMinor,
        string $reference,
        string $idempotencyKey
    ): array {
        $this->validate($fromAccountId, $toAccountId, $amountMinor, $reference, $idempotencyKey);

        $existing = $this->findByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            return $existing + ['duplicate' => true];
        }

        $this->db->beginTransaction();
        try {
            $debit = $this->db->prepare(
                'UPDATE accounts SET balance_minor = balance_minor - :amount '
                . 'WHERE id = :id AND balance_minor >= :amount'
            );
            $debit->execute(['amount' => $amountMinor, 'id' => $fromAccountId]);

            if ($debit->rowCount() !== 1) {
                if (!$this->accountExists($fromAccountId)) {
                    throw new DomainException('ACCOUNT_NOT_FOUND', 'The source account does not exist.', 404);
                }
                throw new DomainException('INSUFFICIENT_FUNDS', 'The source account has insufficient funds.', 409);
            }

            $credit = $this->db->prepare(
                'UPDATE accounts SET balance_minor = balance_minor + :amount WHERE id = :id'
            );
            $credit->execute(['amount' => $amountMinor, 'id' => $toAccountId]);
            if ($credit->rowCount() !== 1) {
                throw new DomainException('ACCOUNT_NOT_FOUND', 'The destination account does not exist.', 404);
            }

            $insert = $this->db->prepare(
                'INSERT INTO transfers '
                . '(idempotency_key, from_account_id, to_account_id, amount_minor, reference) '
                . 'VALUES (:key, :from_id, :to_id, :amount, :reference)'
            );
            $insert->execute([
                'key' => $idempotencyKey,
                'from_id' => $fromAccountId,
                'to_id' => $toAccountId,
                'amount' => $amountMinor,
                'reference' => trim($reference),
            ]);

            $transferId = (int) $this->db->lastInsertId();
            $this->db->commit();

            return $this->findById($transferId) + ['duplicate' => false];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    /** @return list<array<string, mixed>> */
    public function entriesForAccount(int $accountId): array
    {
        if (!$this->accountExists($accountId)) {
            throw new DomainException('ACCOUNT_NOT_FOUND', 'The account does not exist.', 404);
        }

        $statement = $this->db->prepare(
            'SELECT id, from_account_id, to_account_id, amount_minor, reference, created_at '
            . 'FROM transfers WHERE from_account_id = :id OR to_account_id = :id '
            . 'ORDER BY id DESC'
        );
        $statement->execute(['id' => $accountId]);

        return array_map(static function (array $row) use ($accountId): array {
            $outgoing = (int) $row['from_account_id'] === $accountId;
            return [
                'transferId' => (int) $row['id'],
                'direction' => $outgoing ? 'debit' : 'credit',
                'amountMinor' => (int) $row['amount_minor'],
                'counterpartyAccountId' => (int) ($outgoing ? $row['to_account_id'] : $row['from_account_id']),
                'reference' => $row['reference'],
                'createdAt' => $row['created_at'],
            ];
        }, $statement->fetchAll());
    }

    private function validate(int $from, int $to, int $amount, string $reference, string $key): void
    {
        if ($from <= 0 || $to <= 0) {
            throw new DomainException('INVALID_ACCOUNT', 'Account IDs must be positive integers.');
        }
        if ($from === $to) {
            throw new DomainException('SAME_ACCOUNT', 'Source and destination accounts must differ.');
        }
        if ($amount <= 0) {
            throw new DomainException('INVALID_AMOUNT', 'amountMinor must be a positive integer.');
        }
        if (trim($reference) === '' || strlen($reference) > 140) {
            throw new DomainException('INVALID_REFERENCE', 'Reference must contain 1 to 140 characters.');
        }
        if (!preg_match('/^[A-Za-z0-9._:-]{8,80}$/', $key)) {
            throw new DomainException('INVALID_IDEMPOTENCY_KEY', 'Idempotency key must contain 8 to 80 safe characters.');
        }
    }

    private function accountExists(int $id): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM accounts WHERE id = :id');
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    private function findByIdempotencyKey(string $key): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM transfers WHERE idempotency_key = :key');
        $statement->execute(['key' => $key]);
        $row = $statement->fetch();
        return $row === false ? null : $this->serialise($row);
    }

    /** @return array<string, mixed> */
    private function findById(int $id): array
    {
        $statement = $this->db->prepare('SELECT * FROM transfers WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $this->serialise($statement->fetch());
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function serialise(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'fromAccountId' => (int) $row['from_account_id'],
            'toAccountId' => (int) $row['to_account_id'],
            'amountMinor' => (int) $row['amount_minor'],
            'reference' => $row['reference'],
            'createdAt' => $row['created_at'],
        ];
    }
}

