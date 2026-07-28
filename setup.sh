#!/usr/bin/env bash
# ===========================================
# VoiceChat — Initial setup script
# ===========================================
set -e

echo "=========================================="
echo "🎙️  VoiceChat — Setup"
echo "=========================================="

# Check PHP
if ! command -v php >/dev/null 2>&1; then
    echo "❌ PHP is not installed"
    exit 1
fi
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "✅ PHP $PHP_VERSION"

if ! command -v composer >/dev/null 2>&1; then
    echo "❌ Composer is not installed. Install from https://getcomposer.org"
    exit 1
fi
echo "✅ Composer found"

# Install dependencies
echo ""
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Copy env if missing
if [ ! -f .env ]; then
    echo ""
    echo "📝 Creating .env from .env.example..."
    cp .env.example .env
    echo "⚠️  Please edit .env with your database credentials"
fi

# Generate JWT secret if not set
if grep -q "change-this" .env 2>/dev/null; then
    echo ""
    echo "🔐 Generating JWT secret..."
    JWT_SECRET=$(php -r "echo bin2hex(random_bytes(48));")
    sed -i "s|JWT_SECRET=.*|JWT_SECRET=$JWT_SECRET|" .env
fi

# Set permissions
echo ""
echo "🔒 Setting permissions..."
mkdir -p storage/logs storage/cache storage/sessions public/uploads
chmod -R 775 storage public/uploads

# Ask for database import
echo ""
read -p "Do you want to import the database now? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "MySQL host [127.0.0.1]: " DB_HOST
    DB_HOST=${DB_HOST:-127.0.0.1}
    read -p "MySQL port [3306]: " DB_PORT
    DB_PORT=${DB_PORT:-3306}
    read -p "MySQL user [root]: " DB_USER
    DB_USER=${DB_USER:-root}
    read -s -p "MySQL password: " DB_PASS
    echo ""
    read -p "Database name [voicechat]: " DB_NAME
    DB_NAME=${DB_NAME:-voicechat}

    echo ""
    echo "🗄️  Creating database $DB_NAME..."
    mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    echo "📥 Importing schema..."
    mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p"$DB_PASS" $DB_NAME < database/schema.sql

    echo "📥 Importing seed data..."
    mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p"$DB_PASS" $DB_NAME < database/seed.sql

    # Update .env with DB credentials
    sed -i "s|DB_HOST=.*|DB_HOST=$DB_HOST|" .env
    sed -i "s|DB_PORT=.*|DB_PORT=$DB_PORT|" .env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_NAME|" .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USER|" .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" .env

    echo "✅ Database imported"
fi

echo ""
echo "=========================================="
echo "✅ Setup complete!"
echo "=========================================="
echo ""
echo "▶️  Start the HTTP server:"
echo "    php -S 0.0.0.0:8000 -t public"
echo ""
echo "▶️  Start the WebSocket server (in another terminal):"
echo "    php public/ws.php"
echo ""
echo "🌐 Open http://localhost:8000"
echo ""
echo "👤 Default credentials:"
echo "    Admin: admin / Admin@12345"
echo "    Demo:  demo / Demo@12345"
echo ""
