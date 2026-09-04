# Server Requirements

## Software Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ (for frontend build only) |
| npm | 9+ (for frontend build only) |

## PHP Extensions

The following PHP extensions must be enabled (most are enabled by default):

- Ctype
- cURL
- DOM
- Fileinfo
- Filter
- Hash
- Mbstring
- OpenSSL
- PCRE
- PDO
- Session
- Tokenizer
- XML

## Hardware Requirements

### RAM

| Setup | Minimum RAM | Recommended RAM |
|---|---|---|
| Development (php artisan serve) | 512 MB | 1 GB |
| Production (small school, <100 users) | 1 GB | 2 GB |
| Production (medium school, 100-500 users) | 2 GB | 4 GB |
| Production (large / multi-branch, 500+ users) | 4 GB | 8 GB |

PHP-FPM typically uses 20-50 MB per worker. With `PHP_CLI_SERVER_WORKERS=4`, account for ~200 MB just for PHP. Add 256-512 MB for MySQL/PostgreSQL if running on the same server.

### Storage

| Component | Space Required |
|---|---|
| Application code | ~50 MB |
| User uploads (documents, photos, logos) | 500 MB - 5 GB (varies) |
| Database | 100 MB - 1 GB (varies) |
| Logs | 50 - 500 MB |
| OS + system packages | 5 - 10 GB |
| **Total minimum** | **~10 GB** |
| **Recommended** | **25 - 50 GB** |

Use a separate volume or directory for `storage/` and uploaded files to make backups and scaling easier.

### CPU

| Setup | Minimum vCPU | Recommended vCPU |
|---|---|---|
| Development | 1 | 2 |
| Production (small) | 1 | 2 |
| Production (medium) | 2 | 4 |
| Production (large) | 4 | 8 |

## Database

| Driver | Minimum Version |
|---|---|
| SQLite | 3.35.0+ (default, no setup required) |
| MySQL | 5.7+ or MariaDB 10.3+ |
| PostgreSQL | 10.0+ |

SQLite is used by default and requires no external database server. For production, MySQL or PostgreSQL is recommended.

## VPS vs Shared Hosting

| Feature | Shared Hosting | VPS |
|---|---|---|
| Cost | $3-10/month | $5-20/month |
| Root access | No | Yes |
| Custom PHP config | Limited | Full control |
| Queue workers | Not supported | Supported |
| Cron jobs | Limited panel | Full control |
| WebSocket / real-time | Not supported | Supported |
| Performance | Shared resources | Dedicated resources |
| **Verdict** | Works for basic use | **Recommended for production** |

### Recommended VPS Providers

| Provider | Starting Price | Notes |
|---|---|---|
| Hetzner | ~$4.50/mo | Best value, EU data centers |
| DigitalOcean | $6/mo | Good docs, easy scaling |
| Linode (Akamai) | $5/mo | Reliable, global regions |
| Vultr | $6/mo | Wide location range |
| AWS Lightsail | $5/mo | Easy AWS ecosystem integration |
| Google Cloud | ~$7/mo | Free tier available |

## Hosting Cost in Bangladesh (BDT)

### Recommended Plans for This Application

| School Size | RAM | Storage | Plan Type | Monthly Cost (BDT) |
|---|---|---|---|---|
| Small (<100 students) | 2 GB | 50 GB SSD | Unmanaged VPS | ৳1,500 - ৳2,500 |
| Medium (100-500 students) | 4 GB | 100 GB SSD | Managed VPS | ৳3,000 - ৳5,000 |
| Large (500+ students) | 8 GB | 150 GB SSD | Managed VPS | ৳6,000 - ৳9,000 |

### Local VPS Providers (Bangladesh Data Center, BDIX Connected)

| Provider | Plan | RAM | Storage | Monthly Cost |
|---|---|---|---|---|
| XeonBD | KVM Basic | 2 GB | 50 GB SSD | ৳1,550 |
| XeonBD | KVM Standard | 4 GB | 100 GB SSD | ৳2,050 |
| XeonBD | VPS 2022v3 | 4 GB | 60 GB SSD | ৳3,150 |
| XeonBD | VPS 2022v4 | 6 GB | 100 GB SSD | ৳4,250 |
| Alpha Net | KVM Basic | 2 GB | 50 GB SSD | ৳1,550 |
| Alpha Net | KVM Standard | 4 GB | 100 GB SSD | ৳2,500 |
| Alpha Net | KVM Advance | 8 GB | 150 GB SSD | ৳3,900 |
| HostServerBD | VPS-1 Starter | 1 GB | 20 GB NVMe | ৳1,200 |
| HostServerBD | VPS-2 Standard | 2 GB | 40 GB NVMe | ৳2,200 |
| HostServerBD | VPS-4 Professional | 4 GB | 80 GB NVMe | ৳3,800 |
| Hostever | BDIX Managed 2G | 2 GB | — | ৳2,600 |
| Hostever | BDIX Managed 4G | 4 GB | — | ৳4,500 |
| BD IT CENTER | Standard A | 2 GB | — | ৳1,500 |
| BD IT CENTER | Professional A | 4 GB | — | ৳3,500 |

### Managed VPS with Control Panel (cPanel/DirectAdmin)

| Provider | Plan | RAM | Panel | Monthly Cost |
|---|---|---|---|---|
| XeonBD | DirectAdmin Personal | 2 GB | DirectAdmin | ৳1,500 |
| XeonBD | cPanel Solo VPS | 2 GB | cPanel | ৳2,875 |
| XeonBD | Starter (KVM) | 2 GB | cPanel | ৳4,500 |
| Alpha Net | 60GB Managed | 2 GB | cPanel + WHM | ৳3,520 |
| Alpha Net | 80GB Managed | 4 GB | cPanel + WHM | ৳5,400 |

### Shared Hosting (Budget Option)

| Provider | Plan | Storage | Annual Cost |
|---|---|---|---|
| RoyelHost | Basic | — | ৳796/year |
| RoyelHost | Standard | — | ৳1,196/year |
| RoyelHost | Business | — | ৳1,800/year |
| XeonBD | Starter | — | From ৳75/month |

### Annual Cost Summary (Recommended)

| Setup | Monthly | Annual | Best For |
|---|---|---|---|
| Shared hosting | ৳100-250 | ৳1,200-3,000 | Testing only |
| Unmanaged VPS (2 GB) | ৳1,500-2,200 | ৳18,000-26,400 | Small school, DIY admin |
| Managed VPS (4 GB) | ৳3,000-4,500 | ৳36,000-54,000 | Medium school, recommended |
| Managed VPS (8 GB) | ৳6,000-9,000 | ৳72,000-1,08,000 | Large school, multiple branches |

### Payment Methods (Bangladesh)

Most local providers accept:
- bKash / Nagad / Rocket
- Bank transfer
- Credit/debit cards (Visa, Mastercard)
- No international currency conversion needed (BDT pricing)

### Shared Hosting Compatibility

This application **can** run on shared hosting (cPanel, Plesk, etc.) with these caveats:

- PHP 8.2+ must be available (check with host)
- `shell_exec` or `proc_open` must not be disabled (required by some Laravel features)
- Queue workers cannot run — jobs will process on next web request (set `QUEUE_CONNECTION=sync` in `.env`)
- Use SQLite instead of MySQL if MySQL is not provided
- The `composer dev` command won't work — deploy pre-built assets instead

## Web Server

- **Nginx** (recommended) or **Apache** with `mod_rewrite` enabled
- PHP-FPM (for Nginx)
- HTTPS certificate (Let's Encrypt free, or Cloudflare free tier)

### Nginx Config Example

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/school/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realroot$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Optional Services

| Service | Purpose | Cost |
|---|---|---|
| Redis | Cache and session driver (alternative to database) | Free on most VPS |
| Twilio | SMS notifications | ~$0.01/SMS |
| Nexmo / Vonage | SMS notifications | ~$0.02/SMS |
| TextLocal | SMS notifications (Bangladesh) | Varies |
| Africa's Talking | SMS notifications (Africa) | Varies |
| Google Maps API | Embedded map on contact page | Free tier available |
| Cloudflare | CDN, SSL, DDoS protection | Free tier available |
| Mailgun / SendGrid | Transactional email | Free tier available |

## Queue Worker

The application uses a database queue by default. For production:

```bash
php artisan queue:work --sleep=3 --tries=3
```

Or configure Redis/SQS as the queue driver in `.env`.

## Production Deployment Checklist

1. `cp .env.example .env` and configure `.env` (set `APP_URL`, database, mail, etc.)
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm ci && npm run build`
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. Point web server document root to the `public/` directory
8. Set up a cron job for scheduling: `* * * * * cd /var/www/school && php artisan schedule:run >> /dev/null 2>&1`
9. Set up queue worker as a systemd service or supervisor
10. Enable HTTPS (Let's Encrypt or Cloudflare)
