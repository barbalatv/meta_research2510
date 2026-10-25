<?php

declare(strict_types=1);

namespace SenLtd\ShortUrl;

/**
 * Pure URL + slug validation.
 *
 * URLs must be http/https, parseable, have a host, and fit inside
 * MAX_URL_LENGTH bytes. Slugs (when supplied by the caller) must
 * match [A-Za-z0-9_-]{1,32} — this is the same alphabet as Base62
 * plus `_` and `-` so human-picked slugs and auto-generated ones can
 * coexist in the same column without collision by character class.
 */
final class UrlValidator
{
    public const MAX_URL_LENGTH = 2048;
    public const MAX_SLUG_LENGTH = 32;

    public static function validateUrl(string $url): void
    {
        if ($url === '') {
            throw new ValidationException('url is required');
        }
        if (strlen($url) > self::MAX_URL_LENGTH) {
            throw new ValidationException('url exceeds ' . self::MAX_URL_LENGTH . ' bytes');
        }
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new ValidationException('url must be a fully qualified http(s) URL');
        }
        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new ValidationException('url scheme must be http or https');
        }
        if ($parts['host'] === '') {
            throw new ValidationException('url must have a host');
        }
    }

    public static function validateSlug(string $slug): void
    {
        if ($slug === '') {
            throw new ValidationException('slug is required');
        }
        if (strlen($slug) > self::MAX_SLUG_LENGTH) {
            throw new ValidationException('slug exceeds ' . self::MAX_SLUG_LENGTH . ' characters');
        }
        if (preg_match('/\A[A-Za-z0-9_-]+\z/', $slug) !== 1) {
            throw new ValidationException('slug must match [A-Za-z0-9_-]{1,32}');
        }
    }
}
