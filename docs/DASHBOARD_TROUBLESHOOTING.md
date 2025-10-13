# Dashboard Troubleshooting Guide

This guide helps you troubleshoot and fix common issues with the School Management System dashboard.

## Issue: Dashboard Not Showing Content

### Symptoms
- Dashboard page loads but shows blank/white screen
- Loading spinner appears indefinitely
- "Loading dashboard data..." message persists
- Error message: "Error loading dashboard data"

### Common Causes & Solutions

#### 1. Authentication Issues

**Problem**: User not authenticated or token expired

**Solution**:
```bash
# Check browser console for errors
# Look for 401 Unauthorized or token-related errors

# Clear localStorage and re-login
localStorage.clear()
# Then login again at /login
```

**Verify Token**:
```javascript
// In browser console
console.log('Token:', localStorage.getItem('token'));
```

#### 2. Backend API Not Running

**Problem**: Laravel backend is not running or not accessible

**Solution**:
```bash
# Start the backend server
cd backend
php artisan serve

# Verify it's running at http://localhost:8001
curl http://localhost:8001/api/admin/dashboard
```

#### 3. CORS Issues

**Problem**: Cross-Origin Resource Sharing errors

**Solution**:
Edit `backend/config/cors.php`:
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173', 'http://localhost:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

#### 4. Missing Database Tables

**Problem**: Database migrations not run or tables missing

**Solution**:
```bash
cd backend

# Run migrations
php artisan migrate

# Seed the database with sample data
php artisan db:seed

# Or refresh everything
php artisan migrate:fresh --seed
```

#### 5. Permission Issues

**Problem**: User doesn't have admin role

**Solution**:
```bash
# Check user roles in database
php artisan tinker

# In tinker:
$user = User::where('email', 'admin@school.com')->first();
$user->roles; // Check if admin role is assigned
$user->assignRole('admin'); // Assign admin role if missing
```

#### 6. API Endpoint Issues

**Problem**: Dashboard API endpoint not configured

**Verify Route**:
```bash
# List all routes
php artisan route:list | grep dashboard

# Should show:
# GET|HEAD  api/admin/dashboard ... DashboardController@index
```

**Test API Directly**:
```bash
# Get auth token first
TOKEN=$(curl -X POST http://localhost:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@school.com","password":"password"}' \
  | jq -r '.access_token')

# Test dashboard endpoint
curl -X GET http://localhost:8001/api/admin/dashboard \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

#### 7. Frontend Environment Variables

**Problem**: Wrong API URL in frontend

**Solution**:
Check `frontend/.env.local` or `frontend/.env`:
```env
VITE_API_BASE_URL=http://localhost:8001/api
```

Restart frontend after changes:
```bash
cd frontend
npm run dev
```

#### 8. React Query Cache Issues

**Problem**: Stale data or cached errors

**Solution**:
```javascript
// Clear React Query cache in browser console
window.location.reload(true); // Hard reload

// Or in code, add to AdminDashboard.jsx:
const queryClient = useQueryClient();
queryClient.clear(); // Clear all caches
```

## Debugging Steps

### Step 1: Check Browser Console

Open DevTools (F12) and check Console tab for errors:
- Look for red error messages
- Check Network tab for failed API requests
- Verify API responses

### Step 2: Check Network Requests

In DevTools Network tab:
1. Filter by XHR/Fetch
2. Look for `/api/admin/dashboard` request
3. Check:
   - Request Headers (Authorization header present?)
   - Response Status (200 OK?)
   - Response Data (valid JSON?)

### Step 3: Check Laravel Logs

```bash
# View Laravel logs
tail -f backend/storage/logs/laravel.log

# Look for errors related to:
# - Authentication
# - Database queries
# - Dashboard controller
```

### Step 4: Enable Debug Mode

**Backend (temporary)**:
Edit `backend/.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

**Frontend**:
Check browser console for debug logs starting with `[AdminDashboard]`

### Step 5: Test with Demo Data

The dashboard automatically falls back to demo data if API fails. If you see data, the API is not responding correctly.

Look for this message in console:
```
[AdminDashboard] Error fetching dashboard data: ...
```

## Quick Fixes

### Fix 1: Reset Everything
```bash
# Backend
cd backend
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan config:clear
php artisan cache:clear
php artisan serve

# Frontend (in new terminal)
cd frontend
npm install
npm run dev
```

### Fix 2: Create Admin User Manually
```bash
cd backend
php artisan tinker
```

In Tinker:
```php
$user = User::create([
    'name' => 'Admin User',
    'email' => 'admin@school.com',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);

$user->assignRole('admin');
exit;
```

### Fix 3: Verify Database Connection
```bash
cd backend
php artisan migrate:status

# If connection fails, check .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Fix 4: Clear All Caches
```bash
# Backend
cd backend
php artisan optimize:clear

# Frontend
cd frontend
rm -rf node_modules/.vite
rm -rf dist
npm run dev
```

## Testing Checklist

- [ ] Backend server running (http://localhost:8001)
- [ ] Frontend server running (http://localhost:5173)
- [ ] Database connected and migrated
- [ ] Admin user exists with proper role
- [ ] Can login successfully
- [ ] Token stored in localStorage
- [ ] `/api/me` returns user with roles
- [ ] `/api/admin/dashboard` returns data
- [ ] No CORS errors in console
- [ ] No authentication errors in console

## API Response Format

The dashboard expects this response structure:

```json
{
  "success": true,
  "stats": {
    "totalStudents": 1245,
    "totalTeachers": 68,
    "totalClasses": 42,
    "totalStaff": 25,
    "totalRevenue": 125400,
    "attendanceRate": 92.5,
    "pendingAssignments": 15,
    "upcomingEvents": 3,
    "newStudentsThisMonth": 42,
    "revenueGrowth": 12.4
  },
  "charts": {
    "monthlyRevenue": {
      "labels": ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
      "data": [8500, 9200, 10200, 9800, 11000, 12500],
      "title": "Monthly Revenue",
      "type": "line",
      "color": "primary"
    },
    "attendanceTrend": {
      "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
      "data": [92, 95, 90, 94, 93, 89],
      "title": "Attendance Rate",
      "type": "line"
    },
    "classDistribution": {
      "labels": ["Class 1", "Class 2", "Class 3"],
      "data": [45, 42, 48],
      "title": "Students by Class",
      "type": "doughnut"
    }
  },
  "recentActivity": [
    {
      "id": 1,
      "type": "enrollment",
      "title": "New Student Enrollment",
      "message": "5 new students enrolled",
      "time": "2 hours ago",
      "color": "success"
    }
  ],
  "quickActions": [
    {
      "id": 1,
      "title": "Add New Student",
      "description": "Register a new student",
      "icon": "user-plus",
      "url": "/admin/students/create"
    }
  ]
}
```

## Still Having Issues?

### Enable Verbose Logging

**Backend**:
```php
// In DashboardController.php, add at the top of index():
\Log::info('Dashboard requested by user', [
    'user_id' => Auth::id(),
    'user_roles' => Auth::user()->roles->pluck('name'),
]);
```

**Frontend**:
```javascript
// In AdminDashboard.jsx, enable debug mode
const DEBUG = true;
if (DEBUG) {
  console.log('[DEBUG] Component rendered');
  console.log('[DEBUG] Dashboard data:', dashboardData);
}
```

### Common Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "401 Unauthorized" | Token expired or invalid | Re-login |
| "403 Forbidden" | User not admin | Assign admin role |
| "404 Not Found" | Route not found | Check API routes |
| "500 Server Error" | Backend error | Check Laravel logs |
| "Network Error" | Backend not running | Start Laravel server |
| "CORS Error" | Cross-origin blocked | Configure CORS |

## Performance Issues

### Dashboard Loads Slowly

1. **Optimize queries**:
```php
// Use eager loading
Student::with('class', 'section')->get();

// Cache results
Cache::remember('dashboard_stats', 300, function() {
    return [/* stats */];
});
```

2. **Reduce chart data**:
```javascript
// Limit data points
charts.monthlyRevenue.data.slice(-6) // Last 6 months only
```

3. **Enable production mode**:
```env
# Backend
APP_ENV=production
APP_DEBUG=false

# Frontend
npm run build
```

## Contact Support

If you've tried all solutions and still have issues:

1. Gather information:
   - Browser console logs
   - Laravel logs (storage/logs/laravel.log)
   - Network request/response
   - Environment details

2. Create an issue on GitHub with:
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - Error messages
   - System information

3. Check existing issues:
   - GitHub Issues: [repository-url]/issues
   - Documentation: /docs
   - API Docs: /docs/API.md

---

Last Updated: 2025-01-XX
Version: 1.0.0