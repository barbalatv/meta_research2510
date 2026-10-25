<?php

declare(strict_types=1);

namespace SenLtd\ShortUrl;

use PDO;
use PDOException;
use RuntimeException;

/**
 * All SQL in one place, all via prepared statements.
 *
 * Slug generation strategy:
 *   - If the caller supplied a slug, try to INSERT with it; on unique
 *     constraint violation we raise SlugConflictException so the
 *     route can respond 409.
 *   - If the caller didn't supply a slug, we INSERT with a
 *     placeholder, read back the generated rowid, encode it with
 *     Base62, and UPDATE the slug. This is one extra query per
 *     shorten but keeps the ID space collision-free for free.
 */
final class LinkRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function insertWithSlug(string $slug, string $longUrl): array
    {
        $createdAt = gmdate('c');
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO links (slug, long_url, clicks, created_at) VALUES (:slug, :url, 0, :created_at)'
            );
            $stmt->execute([
                ':slug'       => $slug,
                ':url'        => $longUrl,
                ':created_at' => $createdAt,
            ]);
        } catch (PDOException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new SlugConflictException("slug '{$slug}' already exists");
            }
            throw $e;
        }

        return [
            'id'         => (int) $this->pdo->lastInsertId(),
            'slug'       => $slug,
            'long_url'   => $longUrl,
            'clicks'     => 0,
            'created_at' => $createdAt,
        ];
    }

    public function insertAutoSlug(string $longUrl): array
    {
        $createdAt = gmdate('c');
        $placeholder = '__pending_' . bin2hex(random_bytes(8));

        $stmt = $this->pdo->prepare(
            'INSERT INTO links (slug, long_url, clicks, created_at) VALUES (:slug, :url, 0, :created_at)'
        );
        $stmt->execute([
            ':slug'       => $placeholder,
            ':url'        => $longUrl,
            ':created_at' => $createdAt,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $slug = Base62::encode($id);

        $update = $this->pdo->prepare('UPDATE links SET slug = :slug WHERE id = :id');
        try {
            $update->execute([':slug' => $slug, ':id' => $id]);
        } catch (PDOException $e) {
            if ($this->isUniqueViolation($e)) {
                // This can only happen if a human stole the slug in
                // between. Fall back to id-plus-suffix.
                $slug = Base62::encode($id) . '_' . bin2hex(random_bytes(2));
                $update->execute([':slug' => $slug, ':id' => $id]);
            } else {
                throw $e;
            }
        }

        return [
            'id'         => $id,
            'slug'       => $slug,
            'long_url'   => $longUrl,
            'clicks'     => 0,
            'created_at' => $createdAt,
        ];
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, slug, long_url, clicks, created_at FROM links WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Atomically bump clicks and return the long_url in a single
     * transaction. Uses `UPDATE ... RETURNING` which SQLite supports
     * from 3.35 onwards. Returns null if the slug doesn't exist.
     */
    public function incrementClicksAndFetch(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'UPDATE links SET clicks = clicks + 1 WHERE slug = :slug RETURNING id, slug, long_url, clicks, created_at'
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function delete(string $slug): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM links WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);
        return $stmt->rowCount() > 0;
    }

    public function totals(): array
    {
        $row = $this->pdo->query('SELECT COUNT(*) AS c, COALESCE(SUM(clicks), 0) AS s FROM links')->fetch();
        return [
            'total_links'  => (int) $row['c'],
            'total_clicks' => (int) $row['s'],
        ];
    }

    private function isUniqueViolation(PDOException $e): bool
    {
        // SQLite surfaces unique violations as "23000" SQLSTATE or
        // with message containing "UNIQUE constraint failed".
        return str_contains((string) $e->getMessage(), 'UNIQUE constraint failed');
    }
}

final class SlugConflictException extends RuntimeException
{
}
