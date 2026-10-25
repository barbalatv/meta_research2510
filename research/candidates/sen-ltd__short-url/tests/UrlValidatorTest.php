<?php

declare(strict_types=1);

namespace SenLtd\ShortUrl\Tests;

use PHPUnit\Framework\TestCase;
use SenLtd\ShortUrl\UrlValidator;
use SenLtd\ShortUrl\ValidationException;

final class UrlValidatorTest extends TestCase
{
    public function testAcceptsHttps(): void
    {
        UrlValidator::validateUrl('https://example.com/path?q=1');
        $this->expectNotToPerformAssertions();
    }

    public function testAcceptsHttp(): void
    {
        UrlValidator::validateUrl('http://example.com');
        $this->expectNotToPerformAssertions();
    }

    public function testRejectsFtp(): void
    {
        $this->expectException(ValidationException::class);
        UrlValidator::validateUrl('ftp://example.com/file');
    }

    public function testRejectsJavascriptScheme(): void
    {
        $this->expectException(ValidationException::class);
        UrlValidator::validateUrl('javascript:alert(1)');
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        UrlValidator::validateUrl('');
    }

    public function testRejectsTooLong(): void
    {
        $long = 'https://example.com/' . str_repeat('a', 2100);
        $this->expectException(ValidationException::class);
        UrlValidator::validateUrl($long);
    }

    public function testRejectsMissingHost(): void
    {
        $this->expectException(ValidationException::class);
        UrlValidator::validateUrl('http://');
    }

    public function testSlugHappyPath(): void
    {
        UrlValidator::validateSlug('abc-123_XYZ');
        $this->expectNotToPerformAssertions();
    }

    public function testSlugRejectsSpace(): void
    {
        $this->expectException(ValidationException::class);
        UrlValidator::validateSlug('has space');
    }

    public function testSlugRejectsTooLong(): void
    {
        $this->expectException(ValidationException::class);
        UrlValidator::validateSlug(str_repeat('a', 33));
    }

    public function testSlugRejectsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        UrlValidator::validateSlug('');
    }
}
