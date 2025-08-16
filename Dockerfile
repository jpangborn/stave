# Stage 1: Build assets with Node
FROM node:22 AS assets
WORKDIR /app

# Install Node deps
COPY package*.json ./
RUN npm ci

# Copy source and build
COPY . .
RUN npm run build

# Stage 2: PHP runtime (no Node in final image)
FROM serversideup/php:8.4-unit

ENV SSL_MODE=off
ENV PHP_OPCACHE_ENABLE=1

USER root

# Install Git
RUN apt-get update && apt-get install -y git

USER www-data

# Copy entrypoint scripts
COPY --chmod=755 ./entrypoint.d/ /etc/entrypoint.d/

# Copy app source
COPY --chown=www-data:www-data . /var/www/html

# Copy built assets from Node stage into public/build
COPY --from=assets /app/public/build /var/www/html/public/build
