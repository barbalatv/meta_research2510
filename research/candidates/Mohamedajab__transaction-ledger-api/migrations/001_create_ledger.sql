PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    balance_minor INTEGER NOT NULL CHECK (balance_minor >= 0),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transfers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    idempotency_key TEXT NOT NULL UNIQUE,
    from_account_id INTEGER NOT NULL,
    to_account_id INTEGER NOT NULL,
    amount_minor INTEGER NOT NULL CHECK (amount_minor > 0),
    reference TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_account_id) REFERENCES accounts(id),
    FOREIGN KEY (to_account_id) REFERENCES accounts(id),
    CHECK (from_account_id <> to_account_id)
);

CREATE INDEX IF NOT EXISTS idx_transfers_from_account ON transfers(from_account_id, created_at);
CREATE INDEX IF NOT EXISTS idx_transfers_to_account ON transfers(to_account_id, created_at);

