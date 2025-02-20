FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    ffmpeg \
    && docker-php-ext-install pdo pdo_pgsql sockets mysqli

RUN pecl install ev && docker-php-ext-enable ev

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . /var/www

RUN mkdir -p /var/www/sessions /var/www/uploads && chown -R www-data:www-data /var/www/sessions /var/www/uploads

COPY --chown=www-data:www-data . /var/www

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
