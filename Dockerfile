# =============================================================================
# Artisans Task Manager API - Production Dockerfile
# Optimized for Render.com deployment with PHP 8.2 + Laravel 12
# =============================================================================

FROM php:8.4-fpm

# -----------------------------------------------------------------------------
# 1. System Dependencies (combined into single layer to reduce image size)
# -----------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# -----------------------------------------------------------------------------
# 2. PHP Extensions (including Opcache for performance)
# -----------------------------------------------------------------------------
RUN docker-php-ext-install \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# -----------------------------------------------------------------------------
# 3. Opcache Configuration (Critical for Laravel Performance)
# -----------------------------------------------------------------------------
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# -----------------------------------------------------------------------------
# 4. PHP Production Configuration
# -----------------------------------------------------------------------------
RUN echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "upload_max_filesize=64M" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "max_execution_time=60" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/laravel.ini

# -----------------------------------------------------------------------------
# 5. Install Composer
# -----------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -----------------------------------------------------------------------------
# 6. Create non-root user for security
# -----------------------------------------------------------------------------
RUN groupadd -g 1000 appgroup \
    && useradd -u 1000 -g appgroup -m appuser

# -----------------------------------------------------------------------------
# 7. Set working directory
# -----------------------------------------------------------------------------
WORKDIR /var/www

# -----------------------------------------------------------------------------
# 8. Copy application files
# -----------------------------------------------------------------------------
COPY --chown=appuser:appgroup . .

# -----------------------------------------------------------------------------
# 9. Install PHP dependencies (production only)
# -----------------------------------------------------------------------------
RUN composer install \
    --no-interaction \
    --optimize-autoloader \
    --no-dev \
    --prefer-dist \
    && composer clear-cache

# -----------------------------------------------------------------------------
# 10. Laravel Optimizations (cache config, routes, views)
# -----------------------------------------------------------------------------
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# -----------------------------------------------------------------------------
# 11. Set proper permissions
# -----------------------------------------------------------------------------
RUN chown -R appuser:appgroup /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# -----------------------------------------------------------------------------
# 12. Switch to non-root user
# -----------------------------------------------------------------------------
USER appuser

# -----------------------------------------------------------------------------
# 13. Expose default port (Render will override via $PORT)
# -----------------------------------------------------------------------------
EXPOSE 8080

# -----------------------------------------------------------------------------
# 14. Startup Command (uses Render's $PORT or defaults to 8080)
# -----------------------------------------------------------------------------
CMD php artisan migrate --force \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
