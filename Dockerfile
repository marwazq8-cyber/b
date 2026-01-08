FROM php:8.2-apache

# تثبيت المتطلبات
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    libssl-dev \
    pkg-config \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# تثبيت Swoole
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# تفعيل rewrite
RUN a2enmod rewrite

# توجيه Apache إلى public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# نسخ الملفات
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
