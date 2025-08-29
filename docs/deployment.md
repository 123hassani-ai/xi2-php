# 🚀 Deployment Guide - زیتو (Xi2)

راهنمای کامل استقرار زیتو در محیط‌های مختلف

## 📋 Prerequisites

### System Requirements
- **PHP**: 8.0 یا بالاتر
- **MySQL**: 8.0 یا بالاتر (یا MariaDB 10.6+)
- **Web Server**: Apache/Nginx
- **Memory**: حداقل 512MB RAM
- **Storage**: حداقل 5GB فضای خالی
- **SSL Certificate**: برای محیط پروداکشن

### PHP Extensions
```bash
php -m | grep -E "(pdo|pdo_mysql|gd|json|mbstring|fileinfo|openssl)"
```

باید نصب باشند:
- `pdo` - پایگاه داده
- `pdo_mysql` - MySQL driver
- `gd` - پردازش تصویر
- `json` - JSON support
- `mbstring` - Unicode strings
- `fileinfo` - File type detection
- `openssl` - رمزنگاری

## 🐧 Linux Server (Ubuntu/CentOS)

### 1. Install Dependencies

#### Ubuntu/Debian:
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.1-fpm php8.1-mysql php8.1-gd php8.1-json php8.1-mbstring php8.1-fileinfo

# Install MySQL
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install additional tools
sudo apt install -y git curl unzip
```

#### CentOS/RHEL:
```bash
# Update system
sudo yum update -y

# Install PHP and extensions
sudo yum install -y php81 php81-php-fpm php81-php-mysql php81-php-gd php81-php-json php81-php-mbstring

# Install MySQL
sudo yum install -y mysql-server

# Install Nginx
sudo yum install -y nginx
```

### 2. Configure Web Server

#### Nginx Configuration:
```nginx
# /etc/nginx/sites-available/xi2.conf
server {
    listen 80;
    server_name xi2.your-domain.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name xi2.your-domain.com;
    
    root /var/www/xi2/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # File upload settings
    client_max_body_size 50M;
    
    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    
    # Static files
    location ~* \.(jpg|jpeg|png|gif|webp|svg|css|js|ico|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Block access to sensitive files
    location ~ /\.(ht|env) {
        deny all;
    }
    
    location ~ ^/(storage|src|database)/ {
        deny all;
    }
    
    # API routing
    location /api/ {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    
    # Main routing
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
}
```

#### Apache Configuration:
```apache
# /etc/apache2/sites-available/xi2.conf
<VirtualHost *:80>
    ServerName xi2.your-domain.com
    Redirect permanent / https://xi2.your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName xi2.your-domain.com
    DocumentRoot /var/www/xi2/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512
    
    # Security Headers
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "no-referrer-when-downgrade"
    
    # PHP Settings
    php_admin_value upload_max_filesize 50M
    php_admin_value post_max_size 50M
    php_admin_value max_execution_time 300
    
    # Directory Settings
    <Directory /var/www/xi2/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # URL Rewriting
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>
    
    # Block sensitive directories
    <DirectoryMatch "^/var/www/xi2/(storage|src|database)">
        Require all denied
    </DirectoryMatch>
    
    # Hide .env files
    <Files ".env*">
        Require all denied
    </Files>
</VirtualHost>
```

### 3. Deploy Application

```bash
# Create web directory
sudo mkdir -p /var/www/xi2
cd /var/www

# Clone or upload project
sudo git clone https://github.com/your-username/xi2.git xi2
# OR upload files manually

# Set permissions
sudo chown -R www-data:www-data /var/www/xi2
sudo chmod -R 755 /var/www/xi2
sudo chmod -R 775 /var/www/xi2/storage

# Create storage directories
sudo mkdir -p /var/www/xi2/storage/{uploads,cache,logs}
sudo chmod -R 775 /var/www/xi2/storage
```

### 4. Database Setup

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE xi2_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'xi2_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON xi2_production.* TO 'xi2_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Environment Configuration

```bash
# Create production environment file
sudo cp /var/www/xi2/.env.example /var/www/xi2/.env
sudo nano /var/www/xi2/.env
```

```env
# Production Environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://xi2.your-domain.com

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=xi2_production
DB_USERNAME=xi2_user
DB_PASSWORD=secure_password_here

# File Storage
STORAGE_PATH=/var/www/xi2/storage
MAX_FILE_SIZE=10485760
ALLOWED_EXTENSIONS=jpg,jpeg,png,webp

# Security
SESSION_LIFETIME=86400
JWT_SECRET=your-jwt-secret-key-here
HASH_SALT=your-hash-salt-here

# Performance
CACHE_ENABLED=true
COMPRESS_IMAGES=true
GENERATE_THUMBNAILS=true
```

### 6. Initialize Database

```bash
cd /var/www/xi2
sudo -u www-data php src/database/install.php
```

### 7. Enable Services

```bash
# Enable and start services
sudo systemctl enable nginx mysql php8.1-fpm
sudo systemctl start nginx mysql php8.1-fpm

# Enable site
sudo ln -s /etc/nginx/sites-available/xi2.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 🐳 Docker Deployment

### 1. Docker Compose Configuration

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: xi2-app
    restart: unless-stopped
    volumes:
      - ./storage:/var/www/html/storage
      - ./logs:/var/log
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
      - DB_NAME=xi2_production
      - DB_USERNAME=xi2_user
      - DB_PASSWORD=secure_password
    depends_on:
      - mysql
      - redis
    networks:
      - xi2-network

  nginx:
    image: nginx:alpine
    container_name: xi2-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
      - ./storage:/var/www/html/storage:ro
    depends_on:
      - app
    networks:
      - xi2-network

  mysql:
    image: mysql:8.0
    container_name: xi2-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: xi2_production
      MYSQL_USER: xi2_user
      MYSQL_PASSWORD: secure_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql
    networks:
      - xi2-network

  redis:
    image: redis:alpine
    container_name: xi2-redis
    restart: unless-stopped
    networks:
      - xi2-network

volumes:
  mysql_data:

networks:
  xi2-network:
    driver: bridge
```

### 2. Dockerfile

```dockerfile
FROM php:8.1-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    nginx \
    mysql-client \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        pdo_mysql \
        zip \
        fileinfo

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .
COPY .env.docker .env

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
```

### 3. Deploy with Docker

```bash
# Build and start containers
docker-compose up -d --build

# Initialize database
docker-compose exec app php src/database/install.php

# Check status
docker-compose ps
```

## ☁️ Cloud Deployment

### AWS EC2 + RDS

#### 1. Launch EC2 Instance
```bash
# Connect to instance
ssh -i your-key.pem ubuntu@your-ec2-ip

# Update system
sudo apt update && sudo apt upgrade -y
```

#### 2. Configure RDS Database
```bash
# Connect to RDS instance
mysql -h your-rds-endpoint -u admin -p

# Create database
CREATE DATABASE xi2_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 3. Environment Configuration
```env
# .env for AWS
APP_ENV=production
APP_URL=https://your-domain.com

DB_HOST=your-rds-endpoint
DB_PORT=3306
DB_NAME=xi2_production
DB_USERNAME=admin
DB_PASSWORD=your-rds-password

# S3 Storage (Optional)
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-s3-bucket
```

### Google Cloud Platform

#### 1. App Engine Deployment
```yaml
# app.yaml
runtime: php81

env_variables:
  APP_ENV: production
  DB_CONNECTION: mysql
  DB_HOST: /cloudsql/your-project:region:instance
  DB_DATABASE: xi2_production
  DB_USERNAME: root
  DB_PASSWORD: your-password

handlers:
- url: /storage/.*
  static_dir: storage
  secure: always

- url: /.*
  script: public/index.php
  secure: always
```

#### 2. Deploy Command
```bash
gcloud app deploy
```

## 🔒 Security Hardening

### 1. File Permissions
```bash
# Set secure permissions
find /var/www/xi2 -type f -exec chmod 644 {} \;
find /var/www/xi2 -type d -exec chmod 755 {} \;
chmod 600 /var/www/xi2/.env
chmod -R 775 /var/www/xi2/storage
```

### 2. Firewall Configuration
```bash
# UFW (Ubuntu)
sudo ufw enable
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443

# Deny direct access to PHP files
sudo ufw deny 9000
```

### 3. Fail2Ban Setup
```bash
sudo apt install fail2ban

# Configure jail
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[nginx-http-auth]
enabled = true

[nginx-req-limit]
enabled = true
```

## 📊 Monitoring & Logging

### 1. Log Configuration
```bash
# Create log directories
sudo mkdir -p /var/log/xi2/{access,error,application}
sudo chown www-data:www-data /var/log/xi2 -R
```

### 2. Logrotate Setup
```bash
sudo nano /etc/logrotate.d/xi2
```

```
/var/log/xi2/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### 3. Health Check Endpoint
```php
// public/health.php
<?php
header('Content-Type: application/json');

$checks = [
    'database' => checkDatabase(),
    'storage' => checkStorage(),
    'memory' => checkMemory(),
    'disk' => checkDisk()
];

$healthy = !in_array(false, $checks);
http_response_code($healthy ? 200 : 503);

echo json_encode([
    'status' => $healthy ? 'healthy' : 'unhealthy',
    'timestamp' => date('c'),
    'checks' => $checks
]);
```

## 🚀 Performance Optimization

### 1. PHP OPcache
```ini
; /etc/php/8.1/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=512
opcache.max_accelerated_files=65407
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### 2. Redis Caching
```php
// src/cache/redis.php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$redis->select(0);
```

### 3. Image Optimization
```bash
# Install optimization tools
sudo apt install optipng jpegoptim

# Auto-optimize uploads
sudo crontab -e
```

```cron
# Optimize images hourly
0 * * * * find /var/www/xi2/storage/uploads -name "*.jpg" -mtime -1 -exec jpegoptim --strip-all {} \;
0 * * * * find /var/www/xi2/storage/uploads -name "*.png" -mtime -1 -exec optipng {} \;
```

## 🔄 Backup Strategy

### 1. Database Backup
```bash
#!/bin/bash
# backup-db.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u xi2_user -p xi2_production > /backup/xi2_db_$DATE.sql
gzip /backup/xi2_db_$DATE.sql

# Keep only last 30 days
find /backup -name "xi2_db_*.sql.gz" -mtime +30 -delete
```

### 2. Files Backup
```bash
#!/bin/bash
# backup-files.sh
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf /backup/xi2_storage_$DATE.tar.gz /var/www/xi2/storage
find /backup -name "xi2_storage_*.tar.gz" -mtime +7 -delete
```

### 3. Automated Backup
```cron
# Daily backups at 2 AM
0 2 * * * /path/to/backup-db.sh
30 2 * * * /path/to/backup-files.sh
```

---

**برای راهنمایی بیشتر، به [مستندات فنی](technical-docs.md) مراجعه کنید.**
