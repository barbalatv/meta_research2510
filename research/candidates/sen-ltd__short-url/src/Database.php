<?php

declare(strict_types=1);

namespace SenLtd\ShortUrl;

use PDO;

/**
 * PDO SQLite setup.
 *
 * Schema is created idempotently on every boot. We intentionally
 * keep the column set small — (id, slug, long_url, clicks,
 * created_at) — because the demo is explicitly a 300-line portfolio
 * project, not a production link manager.
 *
 * id is INTEGER PRIMARY KEY (which aliases sqlite's rowid), so it
 * monotonically increases and is the natural seed for the Base62
 * slug generator.
 */
final class Database
{
    public static function connect(?string $path = null): PDO
    {
        $path ??= getenv('DB_PATH') ?: '/data/shortener.db';
        $dsn = $path === ':memory:' ? 'sqlite::memory:' : 'sqlite:' . $path;

        if ($path !== ':memory:') {
            $dir = dirname($path);
            if ($dir !== '' && !is_dir($dir)) {
                @mkdir($dir, 0o755, true);
            }
        }

        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // WAL only helps file-backed DBs. :memory: rejects it silently
        // on some builds, so we only ask when it's useful.
        if ($path !== ':memory:') {
            $pdo->exec('PRAGMA journal_mode = WAL');
        }
        $pdo->exec('PRAGMA foreign_keys = ON');

        self::migrate($pdo);
        return $pdo;
    }

    public static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS links (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                slug       TEXT NOT NULL UNIQUE,
                long_url   TEXT NOT NULL,
                clicks     INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL
            );
        SQL);
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_links_created_at ON links(created_at)');
    }
}
