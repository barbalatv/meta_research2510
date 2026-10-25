<?php

declare(strict_types=1);

namespace SenLtd\ShortUrl\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use SenLtd\ShortUrl\Database;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

final class HttpTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        putenv('SHORT_URL_TEST=1');
        require_once __DIR__ . '/../public/index.php';
    }

    private function app(string $adminToken = 'testsecret'): App
    {
        $pdo = Database::connect(':memory:');
        return \short_url_app($pdo, $adminToken);
    }

    private function appNoAdmin(): App
    {
        $pdo = Database::connect(':memory:');
        return \short_url_app($pdo, null);
    }

    private function request(App $app, string $method, string $path, ?array $body = null, array $headers = []): array
    {
        $req = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost' . $path);
        if ($body !== null) {
            $stream = (new StreamFactory())->createStream(json_encode($body));
            $req = $req->withBody($stream)->withHeader('Content-Type', 'application/json');
        }
        foreach ($headers as $k => $v) {
            $req = $req->withHeader($k, $v);
        }
        $res = $app->handle($req);
        return [
            'status'  => $res->getStatusCode(),
            'body'    => (string) $res->getBody(),
            'headers' => $res->getHeaders(),
        ];
    }

    public function testHealth(): void
    {
        $r = $this->request($this->app(), 'GET', '/health');
        $this->assertSame(200, $r['status']);
        $d = json_decode($r['body'], true);
        $this->assertSame('ok', $d['status']);
        $this->assertSame(0, $d['total_links']);
    }

    public function testShortenAutoSlug(): void
    {
        $r = $this->request($this->app(), 'POST', '/shorten', ['url' => 'https://example.com']);
        $this->assertSame(201, $r['status']);
        $d = json_decode($r['body'], true);
        $this->assertSame('1', $d['slug']);
        $this->assertSame('https://example.com', $d['long_url']);
        $this->assertStringEndsWith('/1', $d['short_url']);
        $this->assertArrayHasKey('Location', $r['headers']);
    }

    public function testShortenCustomSlug(): void
    {
        $r = $this->request($this->app(), 'POST', '/shorten', ['url' => 'https://example.com', 'slug' => 'my-slug']);
        $this->assertSame(201, $r['status']);
        $d = json_decode($r['body'], true);
        $this->assertSame('my-slug', $d['slug']);
    }

    public function testCustomSlugConflictReturns409(): void
    {
        $app = $this->app();
        $this->request($app, 'POST', '/shorten', ['url' => 'https://a.com', 'slug' => 'x']);
        $r = $this->request($app, 'POST', '/shorten', ['url' => 'https://b.com', 'slug' => 'x']);
        $this->assertSame(409, $r['status']);
        $d = json_decode($r['body'], true);
        $this->assertSame('slug_conflict', $d['error']);
    }

    public function testShortenBadUrlReturns422(): void
    {
        $r = $this->request($this->app(), 'POST', '/shorten', ['url' => 'not-a-url']);
        $this->assertSame(422, $r['status']);
        $d = json_decode($r['body'], true);
        $this->assertSame('validation_failed', $d['error']);
    }

    public function testShortenRejectsFtp(): void
    {
        $r = $this->request($this->app(), 'POST', '/shorten', ['url' => 'ftp://example.com/x']);
        $this->assertSame(422, $r['status']);
    }

    public function testShortenRejectsBadSlug(): void
    {
        $r = $this->request($this->app(), 'POST', '/shorten', ['url' => 'https://a.com', 'slug' => 'bad slug!']);
        $this->assertSame(422, $r['status']);
    }

    public function testShortenInvalidJson(): void
    {
        $app = $this->app();
        $req = (new ServerRequestFactory())->createServerRequest('POST', 'http://localhost/shorten');
        $stream = (new StreamFactory())->createStream('not json');
        $req = $req->withBody($stream)->withHeader('Content-Type', 'application/json');
        $res = $app->handle($req);
        $this->assertSame(400, $res->getStatusCode());
    }

    public function testRedirect302IncrementsClicks(): void
    {
        $app = $this->app();
        $this->request($app, 'POST', '/shorten', ['url' => 'https://example.com']);
        $r = $this->request($app, 'GET', '/1');
        $this->assertSame(302, $r['status']);
        $this->assertSame('https://example.com', $r['headers']['Location'][0]);

        $info = $this->request($app, 'GET', '/1/info');
        $d = json_decode($info['body'], true);
        $this->assertSame(1, $d['clicks']);

        $this->request($app, 'GET', '/1');
        $this->request($app, 'GET', '/1');
        $info2 = json_decode($this->request($app, 'GET', '/1/info')['body'], true);
        $this->assertSame(3, $info2['clicks']);
    }

    public function testRedirectMissingReturns404(): void
    {
        $r = $this->request($this->app(), 'GET', '/ghost');
        $this->assertSame(404, $r['status']);
    }

    public function testInfoMissingReturns404(): void
    {
        $r = $this->request($this->app(), 'GET', '/ghost/info');
        $this->assertSame(404, $r['status']);
    }

    public function testDeleteRequiresAuth(): void
    {
        $app = $this->app();
        $this->request($app, 'POST', '/shorten', ['url' => 'https://example.com', 'slug' => 'doomed']);
        $r = $this->request($app, 'DELETE', '/doomed');
        $this->assertSame(401, $r['status']);
    }

    public function testDeleteWithAuthSucceeds(): void
    {
        $app = $this->app();
        $this->request($app, 'POST', '/shorten', ['url' => 'https://example.com', 'slug' => 'doomed']);
        $r = $this->request($app, 'DELETE', '/doomed', null, ['Authorization' => 'Bearer testsecret']);
        $this->assertSame(204, $r['status']);
        $r2 = $this->request($app, 'GET', '/doomed/info');
        $this->assertSame(404, $r2['status']);
    }

    public function testDeleteDisabledWhenNoToken(): void
    {
        $app = $this->appNoAdmin();
        $this->request($app, 'POST', '/shorten', ['url' => 'https://example.com', 'slug' => 'x']);
        $r = $this->request($app, 'DELETE', '/x', null, ['Authorization' => 'Bearer anything']);
        $this->assertSame(403, $r['status']);
    }

    public function testDeleteMissingSlug(): void
    {
        $r = $this->request($this->app(), 'DELETE', '/ghost', null, ['Authorization' => 'Bearer testsecret']);
        $this->assertSame(404, $r['status']);
    }

    public function testHomePageServesHtml(): void
    {
        $r = $this->request($this->app(), 'GET', '/');
        $this->assertSame(200, $r['status']);
        $this->assertStringContainsString('short-url', $r['body']);
        $this->assertStringContainsString('text/html', $r['headers']['Content-Type'][0]);
    }

    public function testHealthTotalsAfterActivity(): void
    {
        $app = $this->app();
        $this->request($app, 'POST', '/shorten', ['url' => 'https://a.com']);
        $this->request($app, 'POST', '/shorten', ['url' => 'https://b.com']);
        $this->request($app, 'GET', '/1');
        $this->request($app, 'GET', '/2');
        $this->request($app, 'GET', '/2');
        $r = $this->request($app, 'GET', '/health');
        $d = json_decode($r['body'], true);
        $this->assertSame(2, $d['total_links']);
        $this->assertSame(3, $d['total_clicks']);
    }
}
