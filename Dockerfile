# ============================================================================
# Multi-stage Dockerfile for DocuSign Signing API
# ============================================================================

# Stage 1: Base Image with PHP and Extensions
FROM php:8.4-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    redis \
    supervisor \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo \
        pdo_pgsql \
        pgsql \
        zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies (without dev dependencies for production)
ARG INSTALL_DEV_DEPENDENCIES=false
RUN if [ "$INSTALL_DEV_DEPENDENCIES" = "true" ]; then \
        composer install --no-scripts --no-autoloader --prefer-dist; \
    else \
        composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    fi

# Copy application files
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ============================================================================
# Stage 2: Development Image
# ============================================================================

FROM base AS development

# Install development tools
RUN apk add --no-cache \
    nodejs \
    npm

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Xdebug configuration
RUN echo "xdebug.mode=develop,debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install development dependencies
RUN composer install --prefer-dist --no-scripts --no-autoloader

# Expose port for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]

# ============================================================================
# Stage 3: Production Image
# ============================================================================

FROM base AS production

# Copy OPcache configuration
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy PHP-FPM configuration
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Optimize for production
RUN php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

# Expose port for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]

# ============================================================================
# Stage 4: Horizon Worker (Queue Processing)
# ============================================================================

FROM base AS horizon

# Copy supervisor configuration
COPY docker/supervisor/horizon.conf /etc/supervisor/conf.d/horizon.conf

# Create log directory
RUN mkdir -p /var/log/supervisor

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/horizon.conf"]

# ============================================================================
# Stage 5: Scheduler (Cron)
# ============================================================================

FROM base AS scheduler

# Install cronie for cron jobs
RUN apk add --no-cache cronie

# Create cron file
RUN echo "* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/cron.log 2>&1" > /etc/crontabs/www-data

CMD ["crond", "-f", "-l", "2"]
