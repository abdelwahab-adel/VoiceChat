# Production Deployment Guide

## Nginx + PHP-FPM

```nginx
# /etc/nginx/sites-available/voicechat
server {
    listen 80;
    server_name voicechat.example.com;
    root /var/www/voicechat/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # Cache static
    location ~* \.(jpg|jpeg|png|gif|webp|svg|css|js|ico|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # WebSocket
    location /ws {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_read_timeout 86400;
    }

    # PHP
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    # Block sensitive
    location ~ /\.(env|git) { deny all; }
    location ~ /(storage|config|app|routes|database|docs|vendor) { deny all; }
}
```

## WebSocket as systemd service

```ini
# /etc/systemd/system/voicechat-ws.service
[Unit]
Description=VoiceChat WebSocket Server
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/voicechat
ExecStart=/usr/bin/php /var/www/voicechat/public/ws.php
Restart=always
RestartSec=5
StandardOutput=append:/var/log/voicechat-ws.log
StandardError=append:/var/log/voicechat-ws.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable voicechat-ws
sudo systemctl start voicechat-ws
```

## Cron

```bash
# Edit crontab
crontab -e

# Add:
* * * * * /usr/bin/php /var/www/voicechat/cron.php >> /var/log/voicechat-cron.log 2>&1
```

## SSL with Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d voicechat.example.com
```

## Performance Tips

1. **Enable OPcache** in `php.ini`:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=256
   opcache.max_accelerated_files=20000
   opcache.revalidate_freq=60
   ```

2. **Use Redis for sessions/cache** (optional):
   - Replace `CacheService` with `Predis` adapter
   - Update `SessionService` to use Redis backend

3. **Database** — Use MySQL 8.0 with InnoDB, run OPTIMIZE TABLE weekly

4. **Static assets** — Use a CDN in production (Cloudflare, etc.)

## Backup

```bash
# Daily database backup
mysqldump -u backup -p voicechat | gzip > /backups/voicechat-$(date +%F).sql.gz

# Weekly uploads backup
tar -czf /backups/uploads-$(date +%F).tar.gz /var/www/voicechat/public/uploads/
```

Add to crontab:
```cron
0 3 * * * /usr/local/bin/backup-voicechat.sh
```
