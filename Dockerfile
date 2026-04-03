FROM php:8.4-fpm-alpine AS php-base

RUN apk add --no-cache fcgi git icu-dev libzip-dev oniguruma-dev unzip wget zip \
    && docker-php-ext-install bcmath intl mbstring opcache pcntl pdo_mysql zip

COPY docker/app/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/app/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build

FROM php-base AS app

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    && rm /usr/local/bin/composer

COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/app/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY docker/app/healthcheck-fpm.sh /usr/local/bin/docker-healthcheck-fpm

RUN chmod +x /usr/local/bin/docker-entrypoint \
    /usr/local/bin/docker-healthcheck-fpm \
    && mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm", "-F"]

FROM php-base AS app-dev

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

COPY docker/app/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY docker/app/healthcheck-fpm.sh /usr/local/bin/docker-healthcheck-fpm

RUN chmod +x /usr/local/bin/docker-entrypoint \
    /usr/local/bin/docker-healthcheck-fpm \
    && mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R 1000:1000 /var/www/html/vendor \
    && chmod -R a+rwX bootstrap/cache \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm", "-F"]

FROM nginx:1.27-alpine AS web

WORKDIR /var/www/html

COPY public ./public
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

RUN mkdir -p /var/www/html/storage/app/public \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

EXPOSE 80
