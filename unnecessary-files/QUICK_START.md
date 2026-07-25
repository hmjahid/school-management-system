# Quick Start Guide - School Management System Dashboard

Get your dashboard running in 5 minutes!

## Prerequisites

Make sure you have installed:
- PHP 8.2+
- Composer 2.0+
- Node.js 16+
- MySQL/MariaDB
- Git

## Quick Setup

### 1. Clone & Navigate
```bash
git clone <repository-url>
cd school-management-system
```

### 2. Backend Setup (2 minutes)
```bash
# Navigate to backend
cd backend

# Install dependencies
composer install

# Create environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_db
DB_USERNAME=root
DB_PASSWORD=your_password

# Create database
mysql -u root -p -e "CREATE DATABASE school_db;"

# Run migrations and seeders
php artisan migrate:fresh --seed

# Start backend server
php artisan serve
```

Backend will run at: **http://localhost:8001**

### 3. Frontend Setup (2 minutes)
```bash
# Open new terminal
cd frontend

# Install dependencies
npm install

# Create environment file
cp .env.example .env.local

# Configure API URL in .env.local
VITE_API_BASE_URL=http://localhost:8001/api

# Start frontend server
npm run dev
```

Frontend will run at: **http://localhost:5173**

### 4. Login & Access Dashboard
```bash
# Open browser
http://localhost:5173/login

# Login with default credentials
Email: admin@school.com
Password: password

# Dashboard URL
http://localhost:5173/dashboard
```

## That's It! 🎉

You should now see:
- ✅ Statistics cards (Students, Teachers, Revenue)
- ✅ Charts (Revenue trends, Attendance)
- ✅ Recent activity feed
- ✅ Quick action buttons

## Quick Test

Run the automated test script:
```bash
chmod +x test-dashboard.sh
./test-dashboard.sh
```

## Troubleshooting

### Dashboard Not Loading?

**Quick Fix #1 - Reset Everything**
```bash
# Backend
cd backend
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan serve

# Frontend (new terminal)
cd frontend
rm -rf node_modules/.vite
npm run dev
```

**Quick Fix #2 - Check Services**
```bash
# Is backend running?
curl http://localhost:8001/api

# Is frontend running?
curl http://localhost:5173
```

**Quick Fix #3 - Verify Login**
```bash
# Test login API
curl -X POST http://localhost:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@school.com","password":"password"}'
```

### Common Issues

| Issue | Solution |
|-------|----------|
| "Connection refused" | Start backend: `php artisan serve` |
| "401 Unauthorized" | Re-login at `/login` |
| "403 Forbidden" | Run: `php artisan db:seed --class=AdminUserSeeder` |
| Blank dashboard | Check browser console (F12) |
| "CORS error" | Verify VITE_API_BASE_URL in .env.local |

## Docker Option (Alternative)

Prefer Docker? Run this instead:
```bash
# Start all services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --seed

# Access at
Frontend: http://localhost:5173
Backend: http://localhost:8001
```

## Next Steps

1. **Explore Features**
   - Add students: `/dashboard/students`
   - Manage classes: `/dashboard/classes`
   - Process payments: `/dashboard/payments`

2. **Customize**
   - Update school info in settings
   - Add your logo
   - Configure payment gateways

3. **Learn More**
   - Full docs: `docs/DASHBOARD_TROUBLESHOOTING.md`
   - Architecture: `architecture-plan.md`
   - API routes: `php artisan route:list`

## Development Commands

```bash
# Backend
php artisan serve              # Start server
php artisan migrate            # Run migrations
php artisan db:seed            # Seed database
php artisan tinker             # Interactive shell
php artisan route:list         # List all routes
php artisan optimize:clear     # Clear all caches

# Frontend
npm run dev                    # Start dev server
npm run build                  # Build for production
npm run preview                # Preview production build
```

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@school.com | password |
| Teacher | teacher@school.com | password |
| Student | student@school.com | password |
| Parent | parent@school.com | password |

**⚠️ Change these in production!**

## Production Deployment

See detailed guides:
- Shared hosting: `hostinger-deployment.md`
- VPS/Cloud: `hosting-architecture.md`
- Docker: Use `docker-compose.yml`

## Need Help?

1. **Run diagnostics**: `./test-dashboard.sh`
2. **Check logs**: `tail -f backend/storage/logs/laravel.log`
3. **Browser console**: Open DevTools (F12)
4. **Documentation**: `docs/DASHBOARD_TROUBLESHOOTING.md`

## Support

- 📖 Documentation: `/docs`
- 🐛 Issues: GitHub Issues
- 💬 Discussions: GitHub Discussions
- 📧 Email: support@example.com

---

**Status**: Ready for development ✅
**Version**: 1.0.0
**Last Updated**: 2025-01-XX

Happy Coding! 🚀