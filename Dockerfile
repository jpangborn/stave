FROM serversideup/php:8.3-unit

ENV SSL_MODE=off
ENV PHP_OPCACHE_ENABLE=1

USER root

RUN apt-get update && apt-get install -y git

USER www-data

COPY --chmod=755 ./entrypoint.d/ /etc/entrypoint.d/
COPY --chown=www-data:www-data . /var/www/html
