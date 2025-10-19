FROM php:8.2-apache

# Paquetes nativos
RUN apt-get update -y \
 && apt-get install -y --no-install-recommends libsqlite3-dev zlib1g-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_sqlite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
 && a2enmod rewrite

WORKDIR /var/www/html
