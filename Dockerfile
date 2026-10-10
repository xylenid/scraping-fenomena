# syntax=docker/dockerfile:1

###############################################################################
# Stage 1 — Node: install Playwright + Chromium untuk fallback crawler
###############################################################################
FROM node:22-bookworm-slim AS node

ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright

WORKDIR /app/scripts

COPY scripts/package.json scripts/package-lock.json ./
RUN npm ci

# Unduh Chromium + dependensi sistemnya ke /ms-playwright
RUN npx playwright install --with-deps chromium

###############################################################################
# Stage 2 — Runtime: PHP 8.3 CLI + Node.js + Chromium
###############################################################################
FROM php:8.3-cli-bookworm

# Ekstensi PHP: hanya pdo_pgsql yang belum ada di image resmi
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Node.js runtime (dipakai PlaywrightCrawler via shell_exec('node ...'))
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Chromium + library sistem hasil `playwright install --with-deps`
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
COPY --from=node /ms-playwright /ms-playwright

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependensi PHP dulu supaya layer cache tidak invalid saat source berubah
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# Dependensi Node untuk scripts/ (Playwright)
COPY scripts/package.json scripts/package-lock.json ./scripts/
COPY --from=node /app/scripts/node_modules ./scripts/node_modules

# Sisa source code
COPY . .

# Build aset SPA (Vite) untuk welcome.blade.php yang pakai @vite
RUN npm ci \
    && npm run build \
    && rm -rf node_modules

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force && php artisan sources:seed && php artisan config:cache && php artisan serve --host 0.0.0.0 --port ${PORT:-8000} --no-reload"]