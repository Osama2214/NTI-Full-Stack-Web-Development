# Runs the site on any container host (Railway, Render, Fly.io, etc.) using
# PHP's own built-in server — same engine the README already recommends for
# local dev (`php -S`), just bound to the platform's assigned $PORT.
FROM php:8.2-cli-alpine

# pdo_mysql: talk to the platform's managed MySQL database.
# pdo_sqlite: keeps the zero-config local-file fallback working too.
# fileinfo: used by the admin photo-upload endpoints to check MIME types.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions pdo_mysql pdo_sqlite fileinfo

WORKDIR /app

# Install PHPMailer before copying the rest of the source so this layer is
# cached unless composer.json/composer.lock actually change.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

COPY . .

# Writable at runtime for the SQLite fallback DB and the login rate-limit
# file — harmless to create even when DB_HOST is set and MySQL is used
# instead.
RUN mkdir -p data && chmod -R u+rwX data

EXPOSE 8080

# 1) Materialize php/config.php from env vars (no-op if one was already
#    committed/mounted — see php/bootstrap-config.php).
# 2) Serve the app on whatever port the platform assigns.
CMD ["sh", "-c", "php php/bootstrap-config.php && php -S 0.0.0.0:${PORT:-8080} -t ."]
