<?php

declare(strict_types=1);

namespace SenLtd\ShortUrl\Tests;

use PHPUnit\Framework\TestCase;
use SenLtd\ShortUrl\Database;
use SenLtd\ShortUrl\LinkRepository;
use SenLtd\ShortUrl\SlugConflictException;

final class LinkRepositoryTest extends TestCase
{
    private function repo(): LinkRepository
    {
        $pdo = Database::connect(':memory:');
        return new LinkRepository($pdo);
    }

    public function testInsertAutoSlugAssignsBase62FromRowid(): void
    {
        $repo = $this->repo();
        $a = $repo->insertAutoSlug('https://example.com/1');
        $b = $repo->insertAutoSlug('https://example.com/2');
        $this->assertSame(1, $a['id']);
        $this->assertSame(2, $b['id']);
        $this->assertSame('1', $a['slug']);
        $this->assertSame('2', $b['slug']);
        $this->assertSame(0, $a['clicks']);
    }

    public function testInsertWithCustomSlugStores(): void
    {
        $repo = $this->repo();
        $row = $repo->insertWithSlug('myslug', 'https://example.com');
        $this->assertSame('myslug', $row['slug']);
        $this->assertSame('https://example.com', $row['long_url']);
    }

    public function testCustomSlugConflictRaises(): void
    {
        $repo = $this->repo();
        $repo->insertWithSlug('taken', 'https://a.com');
        $this->expectException(SlugConflictException::class);
        $repo->insertWithSlug('taken', 'https://b.com');
    }

    public function testFindBySlugMissingReturnsNull(): void
    {
        $this->assertNull($this->repo()->findBySlug('nope'));
    }

    public function testIncrementClicksAndFetch(): void
    {
        $repo = $this->repo();
        $repo->insertWithSlug('hit', 'https://example.com');
        $r1 = $repo->incrementClicksAndFetch('hit');
        $r2 = $repo->incrementClicksAndFetch('hit');
        $r3 = $repo->incrementClicksAndFetch('hit');
        $this->assertNotNull($r1);
        $this->assertSame(1, (int) $r1['clicks']);
        $this->assertSame(3, (int) $r3['clicks']);
        $this->assertSame('https://example.com', $r3['long_url']);
    }

    public function testIncrementClicksMissingReturnsNull(): void
    {
        $this->assertNull($this->repo()->incrementClicksAndFetch('ghost'));
    }

    public function testDeleteRemovesRow(): void
    {
        $repo = $this->repo();
        $repo->insertWithSlug('bye', 'https://example.com');
        $this->assertTrue($repo->delete('bye'));
        $this->assertNull($repo->findBySlug('bye'));
        $this->assertFalse($repo->delete('bye'));
    }

    public function testTotalsAggregate(): void
    {
        $repo = $this->repo();
        $repo->insertWithSlug('a', 'https://a.com');
        $repo->insertWithSlug('b', 'https://b.com');
        $repo->incrementClicksAndFetch('a');
        $repo->incrementClicksAndFetch('a');
        $repo->incrementClicksAndFetch('b');
        $totals = $repo->totals();
        $this->assertSame(2, $totals['total_links']);
        $this->assertSame(3, $totals['total_clicks']);
    }
}
