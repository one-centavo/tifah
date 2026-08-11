# ---------- Stage 1: Vendor (Composer) ----------
FROM composer:2.10 AS vendor
WORKDIR /app

# Solo los archivos de dependencias primero -> cachea la descarga
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --no-scripts \
    --no-autoloader

# Copiamos el código fuente necesario para el autoloader
COPY database/ database/
COPY app/ app/
COPY routes/ routes/

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# ---------- Stage 2: Frontend (Vite + Tailwind 4) ----------
FROM node:22-alpine AS frontend
WORKDIR /app

COPY package.json pnpm-lock.yaml* vite.config.js ./

RUN corepack enable pnpm

RUN pnpm config set package-import-method copy \
    && pnpm install --frozen-lockfile

COPY resources/ resources/
COPY public/ public/
COPY app/ app/

RUN pnpm run build

# ---------- Stage 3: Runtime (PHP-FPM) ----------
FROM php:8.3-fpm-alpine

# Utilidades del sistema
RUN apk add --no-cache unzip curl

# Instalador optimizado de extensiones PHP oficiales
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo_mysql \
    zip \
    bcmath \
    opcache \
    gd \
    mbstring \
    exif \
    pcntl \
    intl

ARG user=laravel_user
ARG uid=1000

RUN adduser -G www-data -u ${uid} -h /home/${user} -D ${user}

WORKDIR /var/www/html

# Copiar archivos del proyecto con los permisos correspondientes
COPY --chown=${user}:www-data . .
COPY --from=vendor --chown=${user}:www-data /app/vendor/ ./vendor/
COPY --from=frontend --chown=${user}:www-data /app/public/build/ ./public/build/

# Discovery de paquetes de Laravel
RUN php artisan package:discover --ansi

RUN mkdir -p storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R ${user}:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

USER ${user}

CMD ["php-fpm"]