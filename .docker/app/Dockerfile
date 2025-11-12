###########################################
# Base Image with PHP and System Dependencies
###########################################
FROM php:8.4-fpm AS base

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    wget \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libfcgi-bin \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        mbstring \
        zip \
        bcmath \
        exif \
        pcntl \
        sockets \
        gd \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install PHP-FPM health check script
RUN echo '#!/bin/bash\n\
SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \\\n\
cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q "pong"' \
    > /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP for production
COPY ./.docker/app/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configure PHP-FPM pool
COPY ./.docker/app/www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/app

###########################################
# Node Builder Stage (for Vite assets)
###########################################
FROM node:22-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci --only=production=false

# Copy application code needed for build
COPY . .

# Build Vite assets for production
RUN npm run build


###########################################
# Development Stage
###########################################
FROM base AS development

# Install development tools
RUN apt-get update && apt-get install -y \
    vim \
    nano \
    procps \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js for development (Vite dev server)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Create application user
ARG UID=1000
ARG GID=1000
RUN groupadd -g $GID appgroup \
    && useradd -m -u $UID -g $GID -s /bin/bash appuser \
    && mkdir -p /var/log/php-fpm \
    && chown -R appuser:appgroup /var/log/php-fpm

# Development opcache settings (enable timestamp validation for hot reload)
RUN echo 'opcache.validate_timestamps=1' > /usr/local/etc/php/conf.d/zz-opcache-dev.ini

# Copy entrypoint script
COPY --chmod=755 ./.docker/app/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/app

# Switch to non-root user
USER appuser:appgroup

# Expose Vite dev server port
EXPOSE 5173

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

###########################################
# Production Stage
###########################################
FROM base AS production

# Install Node.js for SSR support
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy built assets from node-builder
COPY --from=node-builder /app/public/build /var/www/app/public/build

# Create application user
ARG UID=1000
ARG GID=1000
RUN groupadd -g $GID appgroup \
    && useradd -m -u $UID -g $GID -s /bin/bash appuser \
    && mkdir -p /var/log/php-fpm \
    && chown -R appuser:appgroup /var/log/php-fpm

# Production opcache settings (disable timestamp validation for performance)
RUN echo 'opcache.validate_timestamps=0' > /usr/local/etc/php/conf.d/zz-opcache-prod.ini \
    && echo 'opcache.max_accelerated_files=20000' >> /usr/local/etc/php/conf.d/zz-opcache-prod.ini \
    && echo 'opcache.memory_consumption=256' >> /usr/local/etc/php/conf.d/zz-opcache-prod.ini \
    && echo 'opcache.interned_strings_buffer=16' >> /usr/local/etc/php/conf.d/zz-opcache-prod.ini \
    && echo 'opcache.fast_shutdown=1' >> /usr/local/etc/php/conf.d/zz-opcache-prod.ini

# Copy application code
COPY --chown=appuser:appgroup . /var/www/app

# Copy entrypoint script
COPY --chmod=755 ./.docker/app/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/app

# Install production dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && php artisan optimize:clear

# Create required directories and set permissions
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/private \
    storage/app/public \
    bootstrap/cache \
    && chown -R appuser:appgroup storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Switch to non-root user
USER appuser:appgroup

# Expose PHP-FPM port
EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
