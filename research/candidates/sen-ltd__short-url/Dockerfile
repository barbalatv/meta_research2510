# syntax=docker/dockerfile:1.6

# --- builder: install composer deps (dev included so the image can run phpunit) ---
FROM php:8.2-cli-alpine AS builder

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build

COPY composer.json phpunit.xml ./

RUN composer install --no-interaction --no-progress --prefer-dist -o \
    && find vendor -type d \( -name tests -o -name test -o -name Tests -o -name docs -o -name doc -o -name examples \) -prune -exec rm -rf {} + \
    && find vendor -type f \( -name "*.md" -o -name "*.rst" -o -name "CHANGELOG*" -o -name "CONTRIBUTING*" -o -name ".gitattributes" -o -name ".gitignore" -o -name "phpunit.xml*" -o -name ".php-cs-fixer*" -o -name "psalm*" \) -delete

# Slim base PHP install before copy.
RUN rm -f /usr/local/bin/php-cgi /usr/local/bin/phpdbg /usr/local/bin/pear /usr/local/bin/peardev /usr/local/bin/pecl /usr/local/bin/phar /usr/local/bin/phar.phar \
    && rm -rf /usr/local/include /usr/local/lib/php/build /usr/local/php/man /usr/local/share/man /usr/local/share/doc

# --- runtime: minimal alpine + php copied fresh from builder -----------------
FROM alpine:3.19 AS runtime

RUN apk add --no-cache \
    libxml2 \
    oniguruma \
    libsodium \
    sqlite-libs \
    readline \
    libedit \
    gnu-libiconv \
    curl \
    libcurl \
    argon2-libs \
    libpng \
    libzip \
    ca-certificates

COPY --from=builder /usr/local /usr/local

WORKDIR /app
COPY --from=builder /build/vendor ./vendor
COPY src ./src
COPY public ./public
COPY tests ./tests
COPY composer.json phpunit.xml LICENSE README.md ./

RUN adduser -D -s /bin/sh runner \
    && mkdir -p /data \
    && chown -R runner:runner /app /data

USER runner

ENV DB_PATH=/data/shortener.db

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
