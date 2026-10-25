<?php

declare(strict_types=1);

namespace SenLtd\ShortUrl;

use InvalidArgumentException;

/**
 * Hand-written Base62 encoder/decoder.
 *
 * Alphabet: 0-9 a-z A-Z (note: lowercase before uppercase, which is
 * not the same order Bitly or YouTube use, but it's the standard
 * ordering in most Base62 implementations and doesn't matter as long
 * as encode and decode agree).
 *
 * Integers only, no padding, no checksum. Used for auto-generated
 * slugs where the input is the SQLite rowid.
 */
final class Base62
{
    public const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function encode(int $value): string
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Base62::encode only supports non-negative integers');
        }
        if ($value === 0) {
            return '0';
        }

        $out = '';
        while ($value > 0) {
            $rem = $value % 62;
            $out = self::ALPHABET[$rem] . $out;
            $value = intdiv($value, 62);
        }
        return $out;
    }

    public static function decode(string $s): int
    {
        if ($s === '') {
            throw new InvalidArgumentException('Base62::decode cannot decode empty string');
        }
        $result = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];
            $pos = strpos(self::ALPHABET, $ch);
            if ($pos === false) {
                throw new InvalidArgumentException("Base62::decode invalid character: {$ch}");
            }
            $result = $result * 62 + $pos;
        }
        return $result;
    }
}
