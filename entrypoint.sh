#!/bin/sh

set -e

echo "🚀 Starting Laravel application..."

# Read Docker secrets and set environment variables
echo "🔍 Reading Docker secrets..."

if [ -f "/run/secrets/app_key" ]; then
    export APP_KEY=$(cat /run/secrets/app_key)
    echo "✅ APP_KEY loaded from secret"
fi

if [ -f "/run/secrets/app_url" ]; then
    export APP_URL=$(cat /run/secrets/app_url)
    echo "✅ APP_URL loaded from secret: $APP_URL"
fi

if [ -f "/run/secrets/db_host" ]; then
    export DB_HOST=$(cat /run/secrets/db_host)
    echo "✅ DB_HOST loaded from secret"
fi

if [ -f "/run/secrets/db_port" ]; then
    export DB_PORT=$(cat /run/secrets/db_port)
    echo "✅ DB_PORT loaded from secret"
fi

if [ -f "/run/secrets/db_database" ]; then
    export DB_DATABASE=$(cat /run/secrets/db_database)
    echo "✅ DB_DATABASE loaded from secret"
fi

if [ -f "/run/secrets/db_username" ]; then
    export DB_USERNAME=$(cat /run/secrets/db_username)
    echo "✅ DB_USERNAME loaded from secret"
fi

if [ -f "/run/secrets/db_password" ]; then
    export DB_PASSWORD=$(cat /run/secrets/db_password)
    echo "✅ DB_PASSWORD loaded from secret"
fi

if [ -f "/run/secrets/sanctum_domains" ]; then
    export SANCTUM_STATEFUL_DOMAINS=$(cat /run/secrets/sanctum_domains)
    echo "✅ SANCTUM_STATEFUL_DOMAINS loaded from secret: $SANCTUM_STATEFUL_DOMAINS"
fi

if [ -f "/run/secrets/frontend_url" ]; then
    export FRONTEND_URL=$(cat /run/secrets/frontend_url)
    echo "✅ FRONTEND_URL loaded from secret: $FRONTEND_URL"
fi

if [ -f "/run/secrets/session_driver" ]; then
    export SESSION_DRIVER=$(cat /run/secrets/session_driver)
    echo "✅ SESSION_DRIVER loaded from secret: $SESSION_DRIVER"
fi

if [ -f "/run/secrets/session_domain" ]; then
    export SESSION_DOMAIN=$(cat /run/secrets/session_domain)
    echo "✅ SESSION_DOMAIN loaded from secret: $SESSION_DOMAIN"
fi

# Создаем необходимые директории
echo "📁 Creating directories..."
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/framework/cache
mkdir -p /var/www/bootstrap/cache

# Устанавливаем правильные права
echo "🔒 Setting permissions..."
chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Очищаем bootstrap cache принудительно
echo "🧹 Clearing bootstrap cache..."
rm -rf /var/www/bootstrap/cache/*

# Переключаемся на пользователя laravel для выполнения artisan команд
echo "🧹 Clearing Laravel cache..."
su-exec laravel php artisan config:clear 2>/dev/null || echo "⚠️  Config clear failed, continuing..."
su-exec laravel php artisan cache:clear 2>/dev/null || echo "⚠️  Cache clear failed, continuing..."
su-exec laravel php artisan route:clear 2>/dev/null || echo "⚠️  Route clear failed, continuing..."
su-exec laravel php artisan view:clear 2>/dev/null || echo "⚠️  View clear failed, continuing..."

# Создаем .env файл если его нет
if [ ! -f /var/www/.env ]; then
    echo "📝 Creating .env file..."
    su-exec laravel cp /var/www/.env.example /var/www/.env 2>/dev/null || echo "⚠️  No .env.example found"
fi

# Генерируем ключ приложения если его нет
echo "🔑 Checking application key..."
su-exec laravel php artisan key:generate --force 2>/dev/null || echo "⚠️  Key generation failed"

echo "✅ Laravel initialization complete!"

# Запуск php-fpm от root пользователя чтобы избежать проблем с логированием
echo "🏃 Starting PHP-FPM..."
exec php-fpm
