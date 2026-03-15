# Production Deployment Guide

This guide covers deploying Untitled CMS on a Linux server (Ubuntu/Debian). Adjust paths and service names for your distro.

## Prerequisites

- PHP 8.2+ with extensions: `mongodb`, `gd`, `exif`, `fileinfo`, `mbstring`, `xml`, `curl`, `zip`
- [MongoDB PHP extension](https://www.php.net/manual/en/mongodb.installation.php) (`pecl install mongodb`)
- MongoDB 6.0+ (self-hosted or [MongoDB Atlas](https://www.mongodb.com/atlas))
- Composer 2.x
- Node.js 20+ and npm 10+
- A web server: Nginx or Apache

## 1. Clone and Install

```bash
git clone https://github.com/watchtower/untitled-cms.git /var/www/untitled-cms
cd /var/www/untitled-cms

composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

## 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=untitled_cms

SESSION_DRIVER=file        # use 'redis' for multi-server
CACHE_STORE=file           # use 'redis' for multi-server
QUEUE_CONNECTION=database  # use 'redis' for production queues

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

BCRYPT_ROUNDS=12
SESSION_ENCRYPT=true
```

## 3. Storage and Permissions

```bash
php artisan storage:link

chown -R www-data:www-data /var/www/untitled-cms/storage
chown -R www-data:www-data /var/www/untitled-cms/bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## 4. Database Setup

```bash
php artisan migrate --force
php artisan db:seed --force
```

The seeder creates default roles (Admin, Editor, Viewer) and the first admin user. Set the admin password immediately after.

## 5. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

To clear caches when deploying updates:

```bash
php artisan optimize:clear
```

## 6. Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/untitled-cms/public;
    index index.php;

    # SSL (use certbot/Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    client_max_body_size 55M;   # must exceed vault upload limit (50MB)

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Vault private files must never be served directly
    location ^~ /storage/vault/ {
        deny all;
    }
}
```

## 7. Queue Worker (Systemd)

The queue processes image optimization and alt-text generation jobs.

Create `/etc/systemd/system/untitled-cms-queue.service`:

```ini
[Unit]
Description=Untitled CMS Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/untitled-cms
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
systemctl enable untitled-cms-queue
systemctl start untitled-cms-queue
```

## 8. Scheduler (Cron)

Add to `/etc/cron.d/untitled-cms`:

```
* * * * * www-data cd /var/www/untitled-cms && php artisan schedule:run >> /dev/null 2>&1
```

## 9. SSL (Let's Encrypt)

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

## 10. Deployment Updates

```bash
cd /var/www/untitled-cms
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
systemctl restart untitled-cms-queue
```

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` is set and backed up securely
- [ ] MongoDB is not exposed to the public internet (firewall rules)
- [ ] File upload directory (`storage/vault/`) is not web-accessible
- [ ] HTTPS enforced (SSL certificate active)
- [ ] `BCRYPT_ROUNDS=12` or higher
- [ ] Regular MongoDB backups configured (`mongodump`)
- [ ] Log rotation configured for `storage/logs/`

## Multi-Server / Horizontal Scaling

When running multiple app servers:

1. Set `SESSION_DRIVER=redis` and `CACHE_STORE=redis`
2. Use a shared filesystem (NFS, S3) for `storage/vault/` and `storage/app/`
3. Run queue workers on dedicated workers, not web nodes
4. Point all nodes to the same MongoDB instance/Atlas cluster

For S3 vault storage, set `FILESYSTEM_DISK=s3` and configure `AWS_*` environment variables.
