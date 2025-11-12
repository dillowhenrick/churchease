#!/bin/bash
set -e

echo "🚀 Starting container initialization..."

# Get current user info
CURRENT_USER=$(whoami)
CURRENT_UID=$(id -u)

echo "👤 Running as: $CURRENT_USER (UID: $CURRENT_UID)"

# Copy .env.example to .env if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env from .env.example"
    cp .env.example .env
    echo "⚠️  WARNING: Using default .env configuration. Please update for production!"
fi

# Validate critical environment variables
if [ -f .env ]; then
    if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
        echo "⚠️  APP_KEY not set or invalid format"
    fi

    if [ "${APP_ENV:-local}" = "production" ]; then
        # Production-specific validations
        if grep -q "DB_PASSWORD=churchease" .env 2>/dev/null; then
            echo "🚨 CRITICAL: Using default database password in production!"
            echo "   Please set a strong DB_PASSWORD in .env"
        fi

        if grep -q "REDIS_PASSWORD=churchease" .env 2>/dev/null; then
            echo "🚨 CRITICAL: Using default Redis password in production!"
            echo "   Please set a strong REDIS_PASSWORD in .env"
        fi
    fi
fi

# Create required directories (silently ignore if already exist)
echo "📁 Ensuring required directories exist..."
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache 2>/dev/null || true
mkdir -p storage/app/private storage/app/public 2>/dev/null || true

# Ensure proper permissions for storage directories
# This is critical for Docker volumes which may be initialized with wrong ownership
echo "🔒 Setting storage directory permissions..."
# Try to fix permissions if we have write access (either we own it or have sudo)
if [ -w storage/app ]; then
    chmod -R 775 storage/app 2>/dev/null || echo "⚠️  Could not set permissions on storage/app (non-critical)"
else
    echo "⚠️  WARNING: No write access to storage/app. Directory may be owned by root."
    echo "   This can cause file upload failures. Run: docker-compose exec app chown -R appuser:appgroup /var/www/app/storage/app"
fi

# Install PHP dependencies if vendor/autoload.php doesn't exist
if [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Installing PHP dependencies (composer install)..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "✅ Vendor directory already exists"
fi

# Install Node.js dependencies if node_modules is empty
if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
    echo "📦 Installing Node.js dependencies (npm install)..."
    npm install
else
    echo "✅ node_modules already exists"
fi

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating application key..."
    php artisan key:generate --ansi
fi

# Run migrations (only in development, comment out for production)
# echo "🗄️  Running database migrations..."
# php artisan migrate --force

# Clear and cache config (optional, uncomment if needed)
# echo "⚙️  Caching configuration..."
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache

echo "✨ Container ready!"

# Execute the main command (passed as arguments)
exec "$@"