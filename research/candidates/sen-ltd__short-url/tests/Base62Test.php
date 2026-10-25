<?php

declare(strict_types=1);

namespace SenLtd\ShortUrl\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SenLtd\ShortUrl\Base62;

final class Base62Test extends TestCase
{
    public function testZeroEncodesToZero(): void
    {
        $this->assertSame('0', Base62::encode(0));
    }

    public function testSmallIntegersRoundTrip(): void
    {
        for ($i = 0; $i < 500; $i++) {
            $this->assertSame($i, Base62::decode(Base62::encode($i)), "roundtrip failed for {$i}");
        }
    }

    public function testBoundaryAtBase(): void
    {
        $this->assertSame('10', Base62::encode(62));
        $this->assertSame(62, Base62::decode('10'));
        $this->assertSame('Z', Base62::encode(61));
        $this->assertSame(61, Base62::decode('Z'));
    }

    public function testLargeIntegerRoundTrip(): void
    {
        $n = 9223372036854775000; // near PHP_INT_MAX on 64-bit
        $this->assertSame($n, Base62::decode(Base62::encode($n)));
    }

    public function testNegativeIntegerRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Base62::encode(-1);
    }

    public function testInvalidCharacterRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Base62::decode('abc!');
    }

    public function testEmptyStringRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Base62::decode('');
    }

    public function testAlphabetOrdering(): void
    {
        // 10 -> 'a', 35 -> 'z', 36 -> 'A', 61 -> 'Z'
        $this->assertSame('a', Base62::encode(10));
        $this->assertSame('z', Base62::encode(35));
        $this->assertSame('A', Base62::encode(36));
    }
}
