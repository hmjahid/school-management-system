# Payment Integration Deployment Guide

This guide provides instructions for deploying the payment integration in a production environment.

## Prerequisites

- PHP 8.1+ with required extensions
- Composer
- MySQL 5.7+ or PostgreSQL 10+
- Redis (for queue and cache)
- SSL Certificate (HTTPS required for payment callbacks)

## Environment Configuration

### Required Environment Variables

Add these variables to your `.env` file:

```bash
# Payment Configuration
PAYMENT_DEFAULT_CURRENCY=BDT
PAYMENT_WEBHOOK_SECRET=your_webhook_secret

# bKash Configuration
BKASH_APP_KEY=your_bkash_app_key
BKASH_APP_SECRET=your_bkash_app_secret
BKASH_USERNAME=your_bkash_username
BKASH_PASSWORD=your_bkash_password
BKASH_BASE_URL=https://checkout.pay.bka.sh/v1.2.0-beta
BKASH_CALLBACK_URL=https://yourdomain.com/api/payments/callback/bkash

# Nagad Configuration
NAGAD_MERCHANT_ID=your_merchant_id
NAGAD_MERCHANT_PRIVATE_KEY=`cat /path/to/merchant_private_key.pem`
NAGAD_MERCHANT_CERTIFICATE=`cat /path/to/merchant_certificate.pem`
NAGAD_BASE_URL=https://api.mynagad.com
NAGAD_CALLBACK_URL=https://yourdomain.com/api/payments/callback/nagad

# Rocket Configuration
ROCKET_USERNAME=your_rocket_username
ROCKET_PASSWORD=your_rocket_password
ROCKET_MERCHANT_MOBILE=your_merchant_mobile
ROCKET_MERCHANT_PIN=your_merchant_pin
ROCKET_CALLBACK_URL=https://yourdomain.com/api/payments/callback/rocket

# Logging
PAYMENT_LOG_CHANNEL=payment
LOG_LEVEL=debug
```

## Database Setup

1. Run migrations:
   ```bash
   php artisan migrate
   ```

2. Seed payment gateways:
   ```bash
   php artisan db:seed --class=PaymentGatewaySeeder
   ```

## Queue Configuration

Configure a queue worker to process payment webhooks and notifications:

```bash
# Supervisor configuration for queue worker
[program:payment-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=payments,default --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/worker.log
```

## Web Server Configuration

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    ssl_certificate /path/to/ssl_certificate.crt;
    ssl_certificate_key /path/to/ssl_private.key;
    
    root /path/to/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Webhook endpoints
    location ~ ^/api/payments/(callback|webhook)/ {
        try_files $uri $uri/ /index.php?$query_string;
        
        # Increase timeouts for payment callbacks
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        
        # Disable buffering for webhooks
        fastcgi_request_buffering off;
        client_max_body_size 0;
        proxy_request_buffering off;
    }
}
```

## Setting Up Webhooks

### bKash Webhook Setup

1. Log in to the bKash merchant portal
2. Navigate to Webhook Settings
3. Add the following webhook URL:
   ```
   https://yourdomain.com/api/payments/webhook/bkash
   ```
4. Set the webhook secret in your `.env` file

### Nagad Webhook Setup

1. Log in to the Nagad merchant portal
2. Navigate to API Settings > Webhook Configuration
3. Add the webhook URL:
   ```
   https://yourdomain.com/api/payments/webhook/nagad
   ```
4. Configure the events to listen for (payment.success, payment.failed, etc.)

### Rocket Webhook Setup

1. Contact Rocket support to enable webhooks for your merchant account
2. Provide the webhook URL:
   ```
   https://yourdomain.com/api/payments/webhook/rocket
   ```

## Monitoring and Logging

### Logging Configuration

Payment-related logs are stored in `storage/logs/payment-{date}.log`. To monitor these logs:

```bash
# Tail payment logs
tail -f storage/logs/payment-$(date +'%Y-%m-%d').log
```

### Monitoring

Set up monitoring for the following metrics:

1. Payment success/failure rates
2. Average payment processing time
3. Webhook response times
4. Error rates by payment gateway

### Alerting

Configure alerts for:

- High failure rates (>5% of transactions)
- Payment processing delays (>30 seconds)
- Webhook delivery failures
- SSL certificate expiration

## Security Considerations

1. **API Keys and Secrets**
   - Store all API keys and secrets in environment variables
   - Never commit sensitive information to version control
   - Rotate API keys regularly

2. **Webhook Security**
   - Verify webhook signatures
   - Implement IP whitelisting if supported by the payment gateway
   - Use HTTPS for all webhook endpoints

3. **Data Protection**
   - Encrypt sensitive payment data at rest
   - Implement proper access controls for payment data
   - Regularly audit access to payment information

## Troubleshooting

### Common Issues

1. **Webhook Timeouts**
   - Increase PHP and web server timeouts
   - Move long-running tasks to queues

2. **SSL Certificate Issues**
   - Ensure your SSL certificate is valid and not expired
   - Include the full certificate chain

3. **Callback URL Mismatch**
   - Verify callback URLs match exactly with what's registered with payment providers
   - Check for trailing slashes

4. **Insufficient Permissions**
   - Ensure the web server has write permissions to storage and cache directories
   - Verify database user has appropriate permissions

### Getting Help

For additional support, please contact:
- Email: support@yourschool.edu
- Phone: +880 XXXX-XXXXXX

## Maintenance

### Regular Tasks

1. **Daily**
   - Check payment logs for errors
   - Verify successful webhook deliveries
   - Monitor queue workers

2. **Weekly**
   - Review failed payments
   - Update currency exchange rates
   - Backup payment data

3. **Monthly**
   - Rotate API keys
   - Review and update dependencies
   - Audit user access to payment systems
