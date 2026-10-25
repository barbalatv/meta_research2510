<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SenLtd\ShortUrl\Database;
use SenLtd\ShortUrl\LinkRepository;
use SenLtd\ShortUrl\Middleware\JsonRequestLogger;
use SenLtd\ShortUrl\SlugConflictException;
use SenLtd\ShortUrl\UrlValidator;
use SenLtd\ShortUrl\ValidationException;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

const SHORT_URL_VERSION = '1.0.0';
const SHORT_URL_MAX_BODY_BYTES = 4096;

/**
 * Build the Slim app. A PDO can be injected for tests.
 */
function short_url_app(?\PDO $pdo = null, ?string $adminToken = null): \Slim\App
{
    $pdo ??= Database::connect();
    $adminToken ??= (getenv('ADMIN_TOKEN') ?: '') ?: null;

    $repo = new LinkRepository($pdo);

    $app = AppFactory::create();
    $app->add(new JsonRequestLogger());
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware(false, false, false);

    $json = static function (ResponseInterface $res, array $payload, int $status = 200): ResponseInterface {
        $res->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $res
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    };

    $publicBase = static function (ServerRequestInterface $request): string {
        $uri = $request->getUri();
        $scheme = $uri->getScheme() ?: 'http';
        $host = $uri->getHost() ?: 'localhost';
        $port = $uri->getPort();
        $authority = $host;
        if ($port !== null && !(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
            $authority .= ':' . $port;
        }
        return $scheme . '://' . $authority;
    };

    $app->get('/health', function (ServerRequestInterface $req, ResponseInterface $res) use ($json, $repo) {
        $totals = $repo->totals();
        return $json($res, [
            'status'       => 'ok',
            'version'      => SHORT_URL_VERSION,
            'total_links'  => $totals['total_links'],
            'total_clicks' => $totals['total_clicks'],
        ]);
    });

    $app->post('/shorten', function (ServerRequestInterface $req, ResponseInterface $res) use ($json, $repo, $publicBase) {
        $raw = (string) $req->getBody();
        if (strlen($raw) > SHORT_URL_MAX_BODY_BYTES) {
            return $json($res, ['error' => 'payload_too_large'], 413);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $json($res, ['error' => 'invalid_json', 'message' => 'body must be a JSON object'], 400);
        }

        $url = isset($data['url']) && is_string($data['url']) ? trim($data['url']) : '';
        $customSlug = isset($data['slug']) && is_string($data['slug']) ? trim($data['slug']) : null;

        try {
            UrlValidator::validateUrl($url);
            if ($customSlug !== null && $customSlug !== '') {
                UrlValidator::validateSlug($customSlug);
            }
        } catch (ValidationException $e) {
            return $json($res, ['error' => 'validation_failed', 'message' => $e->getMessage()], 422);
        }

        try {
            $row = ($customSlug !== null && $customSlug !== '')
                ? $repo->insertWithSlug($customSlug, $url)
                : $repo->insertAutoSlug($url);
        } catch (SlugConflictException $e) {
            return $json($res, ['error' => 'slug_conflict', 'message' => $e->getMessage()], 409);
        }

        $shortUrl = $publicBase($req) . '/' . $row['slug'];
        $body = [
            'slug'       => $row['slug'],
            'short_url'  => $shortUrl,
            'long_url'   => $row['long_url'],
            'created_at' => $row['created_at'],
        ];

        return $json($res, $body, 201)->withHeader('Location', $shortUrl);
    });

    $app->get('/{slug:[A-Za-z0-9_-]{1,32}}/info', function (ServerRequestInterface $req, ResponseInterface $res, array $args) use ($json, $repo) {
        $row = $repo->findBySlug((string) $args['slug']);
        if ($row === null) {
            return $json($res, ['error' => 'not_found'], 404);
        }
        return $json($res, [
            'slug'       => $row['slug'],
            'long_url'   => $row['long_url'],
            'clicks'     => (int) $row['clicks'],
            'created_at' => $row['created_at'],
        ]);
    });

    $app->delete('/{slug:[A-Za-z0-9_-]{1,32}}', function (ServerRequestInterface $req, ResponseInterface $res, array $args) use ($json, $repo, $adminToken) {
        if ($adminToken === null) {
            return $json($res, [
                'error'   => 'admin_disabled',
                'message' => 'DELETE is disabled because ADMIN_TOKEN is not set',
            ], 403);
        }
        $auth = $req->getHeaderLine('Authorization');
        $expected = 'Bearer ' . $adminToken;
        if (!hash_equals($expected, $auth)) {
            return $json($res, ['error' => 'unauthorized'], 401);
        }
        $ok = $repo->delete((string) $args['slug']);
        if (!$ok) {
            return $json($res, ['error' => 'not_found'], 404);
        }
        return $res->withStatus(204);
    });

    $app->get('/{slug:[A-Za-z0-9_-]{1,32}}', function (ServerRequestInterface $req, ResponseInterface $res, array $args) use ($json, $repo) {
        $row = $repo->incrementClicksAndFetch((string) $args['slug']);
        if ($row === null) {
            return $json($res, ['error' => 'not_found'], 404);
        }
        return $res
            ->withHeader('Location', $row['long_url'])
            ->withHeader('Cache-Control', 'no-store')
            ->withStatus(302);
    });

    $app->get('/', function (ServerRequestInterface $req, ResponseInterface $res) {
        $res->getBody()->write(short_url_home_page());
        return $res->withHeader('Content-Type', 'text/html; charset=utf-8');
    });

    return $app;
}

function short_url_home_page(): string
{
    return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>short-url — a tiny PHP URL shortener</title>
<style>
  body { font-family: -apple-system, system-ui, sans-serif; max-width: 720px; margin: 40px auto; padding: 0 20px; color: #111; }
  h1 { margin-bottom: 4px; }
  h1 small { color: #888; font-size: 14px; font-weight: 400; }
  form { display: flex; gap: 8px; margin: 20px 0; }
  input[type=text] { flex: 1; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 15px; }
  button { padding: 8px 16px; border: 0; border-radius: 4px; background: #0066cc; color: #fff; font-size: 15px; cursor: pointer; }
  button:hover { background: #0052a3; }
  code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: ui-monospace, monospace; }
  pre { background: #0a0a0a; color: #eee; padding: 12px 16px; border-radius: 6px; overflow-x: auto; font-family: ui-monospace, monospace; font-size: 13px; }
  a { color: #06c; }
  .out { margin-top: 8px; padding: 10px 14px; background: #f6f6f6; border-left: 4px solid #0066cc; border-radius: 3px; min-height: 20px; font-family: ui-monospace, monospace; font-size: 13px; word-break: break-all; }
  .err { border-left-color: #d00; color: #900; }
</style>
</head>
<body>
<h1>short-url <small>a tiny PHP URL shortener</small></h1>
<p>POST a URL, get a Base62 slug. The slug is derived from the row's rowid, so it's collision-free without any retry loop.</p>

<form id="f">
  <input id="u" type="text" placeholder="https://example.com" autofocus>
  <button type="submit">Shorten</button>
</form>
<div id="out" class="out">—</div>

<h2>Endpoints</h2>
<ul>
  <li><code>POST /shorten</code> — body <code>{"url": "...", "slug"?: "custom"}</code></li>
  <li><code>GET /:slug</code> — 302 redirect, bumps click counter</li>
  <li><code>GET /:slug/info</code> — JSON metadata</li>
  <li><code>DELETE /:slug</code> — requires <code>Authorization: Bearer &lt;ADMIN_TOKEN&gt;</code></li>
  <li><code>GET /health</code> — status + totals</li>
</ul>

<h2>Try it</h2>
<pre>curl -s -X POST http://localhost:8000/shorten \
  -H "Content-Type: application/json" \
  -d '{"url":"https://sen.ltd"}'</pre>

<p><a href="https://github.com/sen-ltd/short-url">GitHub</a> · companion to <a href="https://github.com/sen-ltd/url-shortener-rs">url-shortener-rs</a></p>

<script>
document.getElementById('f').addEventListener('submit', async (e) => {
  e.preventDefault();
  const out = document.getElementById('out');
  const url = document.getElementById('u').value.trim();
  out.classList.remove('err');
  out.textContent = 'shortening...';
  try {
    const r = await fetch('/shorten', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({url}),
    });
    const j = await r.json();
    if (r.ok) {
      out.textContent = j.short_url + '  →  ' + j.long_url;
    } else {
      out.classList.add('err');
      out.textContent = (j.error || 'error') + ': ' + (j.message || '');
    }
  } catch (err) {
    out.classList.add('err');
    out.textContent = 'network error';
  }
});
</script>
</body>
</html>
HTML;
}

if (!getenv('SHORT_URL_TEST') && PHP_SAPI !== 'cli') {
    $app = short_url_app();
    $app->run();
}
