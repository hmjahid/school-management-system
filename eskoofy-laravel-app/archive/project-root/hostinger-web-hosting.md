# Hosting Guide: School Management System on Hostinger Web Hosting

This guide explains how to deploy the School Management System on Hostinger's shared hosting by building the frontend locally and uploading both frontend and backend.

## Prerequisites

1. Hostinger shared hosting account with:
   - PHP 8.2 or higher
   - MySQL database
   - Access to File Manager or FTP
2. Local development environment with:
   - Node.js and npm (for building frontend)
   - Composer (for PHP dependencies)

## Step 1: Prepare the Frontend for Production

1. Navigate to the frontend directory:
   ```bash
   cd frontend
   ```

2. Install dependencies and build the production version:
   ```bash
   npm install
   npm run build
   ```
   This will create a `dist` folder with optimized static files.

## Step 2: Prepare the Backend for Production

1. Navigate to the backend directory:
   ```bash
   cd ../backend
   ```

2. Install PHP dependencies (without dev dependencies):
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. Generate application key if not set:
   ```bash
   php artisan key:generate
   ```

4. Optimize the application:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## Step 3: Database Setup

1. Log in to your Hostinger control panel
2. Go to "Databases" → "MySQL Databases"
3. Create a new database and note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

## Step 4: Upload Files to Hostinger

### Option A: Using File Manager
1. Log in to Hostinger hPanel
2. Go to "Files" → "File Manager"
3. Navigate to `public_html`
4. Upload the entire `backend` folder
5. Create a new folder called `frontend` in `public_html`
6. Upload the contents of `frontend/dist` to `public_html/frontend`

### Option B: Using FTP
1. Connect to your hosting via FTP
2. Upload the `backend` folder to the root directory
3. Create a `frontend` directory and upload the contents of `frontend/dist`

## Step 5: Configure the Application

1. In Hostinger's File Manager, navigate to `public_html/backend`
2. Make a copy of `.env.example` and rename it to `.env`
3. Update the following in `.env`:
   ```
   APP_ENV=production
   APP_DEBUG=false
   
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_username
   DB_PASSWORD=your_database_password
   
   APP_URL=https://yourdomain.com
   FRONTEND_URL=https://yourdomain.com/frontend
   ```

4. Set proper file permissions:
   ```
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   chmod 755 public
   ```

## Step 6: Set Up the Database

1. Go to Hostinger's phpMyAdmin
2. Select your database
3. Click "Import" and upload your database dump file
4. Or run migrations:
   ```
   php artisan migrate --force
   ```

## Step 7: Configure .htaccess

1. In `public_html/backend/public/.htaccess`, ensure it contains:
   ```apache
   <IfModule mod_rewrite.c>
       <IfModule mod_negotiation.c>
           Options -MultiViews -Indexes
       </IfModule>

       RewriteEngine On

       # Handle Authorization Header
       RewriteCond %{HTTP:Authorization} .
       RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

       # Redirect Trailing Slashes If Not A Folder...
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteCond %{REQUEST_URI} (.+)/$
       RewriteRule ^ %1 [L,R=301]

       # Send Requests To Front Controller...
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteRule ^ index.php [L]
   </IfModule>
   ```

## Step 8: Update Application URLs

In your frontend code (before building), ensure all API calls point to your backend URL, e.g., `https://yourdomain.com/backend/api/...`

## Step 9: Test the Application

1. Visit `https://yourdomain.com/frontend` to access the frontend
2. The frontend should communicate with the backend at `https://yourdomain.com/backend`

## Troubleshooting

1. **500 Server Error**:
   - Check `.env` file permissions (should be 644)
   - Verify database credentials
   - Check storage permissions

2. **Frontend Not Loading**:
   - Clear browser cache
   - Verify all static files were uploaded
   - Check browser console for 404 errors

3. **API Not Working**:
   - Verify CORS headers in Laravel
   - Check API routes in `routes/api.php`
   - Ensure the `.htaccess` file is properly configured

## Performance Tips

1. Enable caching in Laravel
2. Use a CDN for frontend assets
3. Optimize images before uploading
4. Consider upgrading to VPS for better performance

## Security Considerations

1. Keep Laravel and dependencies updated
2. Use HTTPS (free SSL available in Hostinger)
3. Regularly backup your database and files
4. Keep your `.env` file secure and never commit it to version control
