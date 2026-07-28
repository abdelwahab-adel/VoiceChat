# Setup Guide

## Quick Start (5 minutes)

```bash
# 1. Install dependencies
composer install

# 2. Set up environment
cp .env.example .env
# Edit .env with your DB credentials

# 3. Create database and import
mysql -u root -p -e "CREATE DATABASE voicechat CHARACTER SET utf8mb4;"
mysql -u root -p voicechat < database/schema.sql
mysql -u root -p voicechat < database/seed.sql

# 4. Set permissions
chmod -R 775 storage public/uploads

# 5. Run servers (in two terminals)
php -S 0.0.0.0:8000 -t public          # HTTP
php public/ws.php                      # WebSocket

# 6. Visit http://localhost:8000
#    Login as admin / Admin@12345
```

## Production Deployment

See [DEPLOY.md](DEPLOY.md) for full production setup with Nginx + systemd.

## Mobile API

See [API.md](API.md) for full API documentation.
