FROM php:8.2-apache

# تفعيل rewrite
RUN a2enmod rewrite

# إضافات PHP المطلوبة
RUN docker-php-ext-install pdo pdo_mysql

# تثبيت Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# تثبيت Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# نسخ المشروع كامل
COPY . /var/www/html

# جعل public هو جذر الموقع
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# صلاحيات
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
