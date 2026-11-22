FROM php:8.2-apache

ENV PORT=10000

RUN docker-php-ext-install pdo_mysql \
    && sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .

RUN chown -R www-data:www-data /var/www/html/img/cuidadores

EXPOSE 10000
