# short-url

A tiny Slim 4 URL shortener backed by PDO SQLite and a hand-written Base62 encoder.

Intentionally small: about 300 lines of PHP across five source files. The point is to show how little code a "real" URL shortener actually needs when you're happy with SQLite and prepared statements. It's the PHP companion to [`url-shortener-rs`](https://github.com/sen-ltd/url-shortener-rs) — same problem, same design, different language.

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| POST   | `/shorten` | Shorten a URL. Body: `{"url": "...", "slug"?: "custom"}`. Returns 201 + `Location`. Custom-slug conflict → 409. |
| GET    | `/:slug` | 302 redirect. Atomically increments the click counter. |
| GET    | `/:slug/info` | JSON metadata (no redirect, no click bump). |
| DELETE | `/:slug` | Admin delete. Requires `Authorization: Bearer $ADMIN_TOKEN`. 403 if `ADMIN_TOKEN` is unset. |
| GET    | `/health` | `{status, version, total_links, total_clicks}`. |
| GET    | `/` | HTML form. |

### Validation

- URL must be http or https, parseable, and ≤2048 bytes (422 otherwise).
- Custom slugs must match `[A-Za-z0-9_-]{1,32}`.
- Request body is capped at 4 KiB (413).

### Admin auth

`DELETE` is disabled by default: if you run the container without `ADMIN_TOKEN` set, every DELETE returns 403. Set `-e ADMIN_TOKEN=…` and the endpoint becomes live. The token is compared with `hash_equals()` so it's constant-time.

## Configuration

| Env var | Default | Description |
|---------|---------|-------------|
| `DB_PATH` | `/data/shortener.db` | SQLite file path. Use `:memory:` for ephemeral. |
| `ADMIN_TOKEN` | *(unset)* | Bearer token for `DELETE /:slug`. If unset, DELETE is 403. |

## Run with Docker

```sh
docker build -t short-url .
docker run --rm -p 8000:8000 \
  -e DB_PATH=/tmp/shortener.db \
  -e ADMIN_TOKEN=mysecret \
  short-url

curl -s -X POST http://localhost:8000/shorten \
  -H "Content-Type: application/json" \
  -d '{"url":"https://sen.ltd"}'
# → {"slug":"1","short_url":"http://localhost:8000/1","long_url":"https://sen.ltd",...}

curl -sI http://localhost:8000/1
# → HTTP/1.1 302 Found
# → Location: https://sen.ltd

curl -s http://localhost:8000/1/info
# → {"slug":"1","long_url":"https://sen.ltd","clicks":1,...}
```

## Run locally without Docker

```sh
composer install
composer serve    # php -S 0.0.0.0:8000 -t public public/index.php
```

## Tests

```sh
composer test
```

44 PHPUnit tests across four suites: Base62 round-trips, URL/slug validation, `LinkRepository` with `:memory:` SQLite, and the HTTP layer exercised through Slim's router.

## Design notes

- **Slug generation.** Auto-generated slugs are Base62 encodings of the SQLite rowid. Inserts first go in with a temporary placeholder slug so we can read the assigned rowid back, then a single `UPDATE` sets the real slug. That's one extra query per shorten, but it's collision-free by construction — no retry loop, no birthday paradox, no random nonce.
- **Click counting.** `GET /:slug` uses SQLite's `UPDATE ... RETURNING` (available from 3.35) to bump the counter and read back the row in one statement. No read-modify-write race.
- **Admin safe-default.** DELETE is off unless you opt in with `ADMIN_TOKEN`. It's static — no rotation, no scopes — but it's enough for a portfolio-sized service, and it means forgetting to set an env var fails closed, not open.
- **Hand-written Base62.** `src/Base62.php` is about 40 lines, no dependencies, tested at the 0/small/boundary/near-`PHP_INT_MAX` cases.

## Out of scope on purpose

- Rate limiting (see `url-shortener-rs` for a sliding-window example)
- Multi-writer fanout (SQLite is single-writer; use Postgres if you need concurrency)
- Expiring links
- User accounts / per-user scoping
- A tracking pixel or referer logging

## License

MIT

<!-- sen-publish:links -->
## Links

- 📝 記事: https://sen.ltd/portfolio/articles/short-url/
- 📝 dev.to: https://dev.to/sendotltd/a-portfolio-sized-url-shortener-in-300-lines-of-php-3i22
<!-- /sen-publish:links -->
