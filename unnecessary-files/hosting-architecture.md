# School Management System - Hosting Architecture

## Deployment Options

This document outlines the hosting and deployment options for the School Management System. The application is designed to be flexible and can be deployed on various hosting environments, from shared web hosting to cloud infrastructure.

## 1. System Requirements

### Backend (Laravel)
- PHP 8.1 or higher
- Composer 2.0 or higher
- Database: MySQL 5.7+/MariaDB 10.3+/PostgreSQL 10.0+/SQLite 3.8.8+
- Web Server: Nginx or Apache with mod_rewrite
- PHP Extensions:
  - BCMath
  - Ctype
  - cURL
  - DOM
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML

### Frontend (React)
- Node.js 16.0 or higher
- npm 7.0 or higher / Yarn 1.22 or higher
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Recommended (for production)
- Redis (for caching and queues)
- Supervisor (for queue workers)
- SSL Certificate (HTTPS)
- Backup solution

## 2. Web Hosting Deployment (e.g., Hostinger, Bluehost, SiteGround)

### Setup Instructions

1. **Upload Files**
   - Upload the entire project to your web hosting's `public_html` directory
   - Set the web root to point to the `public` folder

2. **Environment Configuration**
   - Create a `.env` file in the root directory
   - Generate application key: `php artisan key:generate`
   - Configure database connection
   - Set `APP_ENV=production` and `APP_DEBUG=false`

3. **Install Dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm install && npm run build
   ```

4. **Database Setup**
   - Create a new MySQL database
   - Run migrations: `php artisan migrate --force`
   - Seed initial data: `php artisan db:seed --force`

5. **Cron Jobs**
   Set up the following cron job to run every minute:
   ```
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

6. **Queue Workers**
   - For shared hosting, you might need to use the `database` queue driver
   - Set up a cron job to run the queue worker:
     ```
     * * * * * cd /path-to-your-project && php artisan queue:work --tries=3 >> /dev/null 2>&1
     ```

## 3. VPS/Cloud Hosting (e.g., AWS, DigitalOcean, Linode)

### Recommended Server Specifications

| Resource | Minimum | Recommended | Production (1000+ users) |
|----------|---------|-------------|--------------------------|
| CPU      | 1 core  | 2 cores     | 4+ cores                |
| RAM      | 1GB     | 2GB         | 8GB+                    |
| Storage  | 20GB    | 40GB        | 100GB+ (SSD)            |
| OS       | Ubuntu 20.04/22.04 LTS | - | - |

### Setup Instructions

1. **Server Setup**
   ```bash
   # Update system packages
   sudo apt update && sudo apt upgrade -y
   
   # Install required packages
   sudo apt install -y nginx mysql-server php8.1-fpm php8.1-{common,cli,gd,mbstring,mysql,xml,xmlrpc,curl,zip,redis}
   
   # Install Node.js & npm
   curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
   sudo apt install -y nodejs
   
   # Install Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

2. **Database Setup**
   ```bash
   sudo mysql_secure_installation
   mysql -u root -p
   ```
   ```sql
   CREATE DATABASE school_management;
   CREATE USER 'school_user'@'localhost' IDENTIFIED BY 'secure_password';
   GRANT ALL PRIVILEGES ON school_management.* TO 'school_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Application Setup**
   ```bash
   # Clone repository
   git clone https://github.com/yourusername/school-management-system.git /var/www/school
   cd /var/www/school
   
   # Install dependencies
   composer install --optimize-autoloader --no-dev
   npm install && npm run build
   
   # Set permissions
   sudo chown -R www-data:www-data /var/www/school
   sudo chmod -R 775 /var/www/school/storage /var/www/school/bootstrap/cache
   
   # Environment setup
   cp .env.example .env
   nano .env  # Edit configuration
   php artisan key:generate
   ```

4. **Nginx Configuration**
   ```nginx
   server {
       listen 80;
       server_name yourdomain.com www.yourdomain.com;
       root /var/www/school/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";

       index index.php;

       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

5. **SSL Certificate (Let's Encrypt)**
   ```bash
   sudo apt install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
   ```

6. **Queue Workers**
   ```bash
   sudo nano /etc/supervisor/conf.d/laravel-worker.conf
   ```
   ```ini
   [program:laravel-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/school/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/www/school/storage/logs/worker.log
   stopwaitsecs=3600
   ```
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-worker:*
   ```

## 4. Docker Deployment

For development or production with Docker:

1. **Requirements**
   - Docker 20.10+
   - Docker Compose 1.29+

2. **Setup**
   ```bash
   git clone https://github.com/yourusername/school-management-system.git
   cd school-management-system
   cp .env.example .env
   # Edit .env as needed
   
   # Start containers
   docker-compose up -d
   
   # Install dependencies
   docker-compose exec app composer install
   docker-compose exec app npm install
   docker-compose exec app npm run build
   
   # Run migrations
   docker-compose exec app php artisan migrate --seed
   ```

3. **Access**
   - Web: http://localhost:8000
   - PHPMyAdmin: http://localhost:8080
   - Redis Commander: http://localhost:8081

## 5. Scaling Considerations

### Vertical Scaling
- Upgrade server resources (CPU, RAM)
- Use OPcache for PHP
- Configure database caching
- Enable Redis for sessions and cache

### Horizontal Scaling
- Load balancing with multiple app servers
- Database read replicas
- Redis cluster for distributed caching
- CDN for static assets

## 6. Backup Strategy

### Database Backups
```bash
# Daily backup (keep 30 days)
0 2 * * * /usr/bin/mysqldump -u username -p'password' school_management | gzip > /backups/school_db_$(date +\%Y\%m\%d).sql.gz
find /backups -name "school_db_*.sql.gz" -type f -mtime +30 -delete
```

### File Backups
```bash
# Weekly backup
0 3 * * 0 tar -czf /backups/school_files_$(date +\%Y\%m\%d).tar.gz /var/www/school
find /backups -name "school_files_*.tar.gz" -type f -mtime +90 -delete
```

## 7. Monitoring & Maintenance

### Recommended Tools
- **Monitoring**: Laravel Horizon, Laravel Telescope, New Relic
- **Logging**: Papertrail, Loggly, or ELK Stack
- **Uptime Monitoring**: UptimeRobot, Pingdom
- **Security**: Fail2Ban, Cloudflare WAF

### Maintenance Commands
```bash
# Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue management
php artisan queue:restart
php artisan queue:failed

# Storage link
php artisan storage:link
```

## 8. Security Best Practices

1. **Server Security**
   - Keep system packages updated
   - Configure firewall (UFW)
   - Disable root login and use SSH keys
   - Regular security audits

2. **Application Security**
   - Use HTTPS with HSTS
   - Enable CSRF protection
   - Input validation and output escaping
   - Regular dependency updates
   - Security headers

3. **Data Protection**
   - Encrypt sensitive data
   - Regular backups
   - GDPR compliance
   - Data retention policy

## 9. Troubleshooting

### Common Issues
1. **Permission Issues**
   ```bash
   sudo chown -R www-data:www-data /var/www/school
   sudo chmod -R 775 /var/www/school/storage /var/www/school/bootstrap/cache
   ```

2. **Queue Workers Not Running**
   - Check supervisor status: `sudo supervisorctl status`
   - View logs: `sudo tail -f /var/log/supervisor/laravel-worker-*.log`

3. **Scheduled Tasks Not Running**
   - Verify cron job is set up correctly
   - Check Laravel scheduler log: `storage/logs/scheduler.log`

## 10. Support

For additional support:
1. Check the project documentation
2. Open an issue on GitHub
3. Contact support@example.com

---
*Last Updated: October 11, 2025*
