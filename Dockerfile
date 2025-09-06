# Stage 1: Composer dependencies
FROM serversideup/php:8.4-cli AS composer
WORKDIR /app

# Install composer
USER root
RUN apt-get update && apt-get install -y git

COPY composer.json composer.lock ./

# Install Composer Dependencies
RUN --mount=type=secret,id=COMPOSER_AUTH_JSON_BASE64 \
    export COMPOSER_AUTH="$(cat /run/secrets/COMPOSER_AUTH_JSON_BASE64 | base64 -d)" && \
    composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Optimize PHP Classes
COPY . ./
RUN composer dump-autoload --optimize

# Stage 2: Assets build
FROM node:22 AS assets
WORKDIR /app

# Copy composer vendor so assets build can see CSS/JS from PHP packages
COPY --from=composer /app/vendor ./vendor

# Copy node deps and install
COPY package*.json ./
RUN npm ci

# Copy application code
COPY . ./

# Build assets
RUN npm run build

# Stage 3: Final runtime image
FROM serversideup/php:8.4-unit

ENV SSL_MODE=off
ENV PHP_OPCACHE_ENABLE=1

USER root
RUN apt-get update && apt-get install -y git

# Install the intl extension with root permissions
RUN install-php-extensions intl

# Drop back to our unprivileged user
USER www-data

# Entrypoint scripts
COPY --chmod=755 ./entrypoint.d/ /etc/entrypoint.d/

# Copy app code
COPY --chown=www-data:www-data . /var/www/html

# Copy vendor and build artifacts
COPY --from=composer /app/vendor /var/www/html/vendor
COPY --from=assets /app/public/build /var/www/html/public/build
