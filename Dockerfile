# =====================================================================
#  ToyLand - ảnh Docker để chạy trên Render (hoặc bất kỳ máy Linux nào)
#
#  PHP 8.3: composer.lock hiện khóa nette/utils + nette/schema chỉ chạy trên
#  PHP < 8.4, và toàn bộ hệ thống đã được kiểm thử trên 8.3.
#
#  Build thử trên máy:   docker build -t toyland .
#  Chạy thử:             docker run --rm -p 8080:8080 --env-file .env toyland
# =====================================================================
FROM php:8.3-apache

# --- Extension PHP: pdo_mysql (MySQL/Aiven), zip (composer giải nén gói),
#     opcache (tăng tốc). Các extension còn lại Laravel cần (mbstring, openssl,
#     fileinfo, tokenizer, dom, ...) đã có sẵn trong ảnh php chính thức.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- Cấu hình PHP cho môi trường chạy thật
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-toyland.ini"

# --- Apache: trỏ gốc web vào public/, bật rewrite cho .htaccess của Laravel,
#     nghe trên cổng $PORT do Render cấp (mặc định 8080 khi chạy trên máy).
ENV PORT=8080 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers \
    && sed -ri 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

WORKDIR /var/www/html

# --- Cài thư viện PHP TRƯỚC khi chép mã nguồn: sửa code mà không đổi
#     composer.lock thì Docker dùng lại lớp này, build nhanh hơn hẳn.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# --- Mã nguồn (những gì bị loại xem .dockerignore)
COPY . .

RUN composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
                storage/logs bootstrap/cache public/uploads \
    && chown -R www-data:www-data storage bootstrap/cache public/uploads \
    && chmod +x docker/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["apache2-foreground"]
