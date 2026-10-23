FROM dunglas/frankenphp:php8.3-bookworm

# Install ekstensi PHP yang dibutuhkan Laravel: pdo_mysql, gd, intl, zip, opcache
RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache \
    @composer

WORKDIR /app

# Salin konfigurasi Caddyfile untuk FrankenPHP
COPY Caddyfile /etc/caddy/Caddyfile
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Layer Caching: Salin file dependensi Composer terlebih dahulu
COPY composer.json composer.lock ./

# Instal dependensi composer produksi
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# Salin seluruh kode aplikasi Laravel ke container
COPY . /app

# Finalisasi dump autoloader
RUN composer dump-autoload --optimize

# Konfigurasi environment bawaan FrankenPHP
ENV SERVER_NAME=":80"
ENV APP_ENV="production"
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

# Atur permission direktori storage dan cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 80 443 443/udp

ENTRYPOINT ["/usr/local/bin/docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
