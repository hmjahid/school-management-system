# Server Requirements

## Minimum Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ (for frontend build) |
| npm | 9+ |

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

## Database

| Driver | Minimum Version |
|---|---|
| SQLite | 3.35.0+ (default, no setup required) |
| MySQL | 5.7+ or MariaDB 10.3+ |
| PostgreSQL | 10.0+ |

SQLite is used by default and requires no external database server. For production, MySQL or PostgreSQL is recommended.

## Web Server

- **Nginx** (recommended) or **Apache** with `mod_rewrite` enabled
- PHP-FPM (for Nginx)

## Optional Services

| Service | Purpose |
|---|---|
| Redis | Cache and session driver (alternative to database) |
| Twilio / Nexmo / TextLocal / Africa's Talking | SMS notifications |
| Google Maps API key | Embedded map on contact page |

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
