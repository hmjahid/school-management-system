# School Management System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Blade](https://img.shields.io/badge/UI-Laravel_Blade-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/docs/blade)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.x-06B6D4?logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

A School Management System built with **Laravel Blade** (this folder is the Laravel product
inside the **Eskoofy monorepo** — see the root `README.md` and `AGENTS.md` for the repo-wide
layout and BD/INT variant strategy): session auth, Eskoofy-style indigo sidebar,
live-stat dashboard, full admin modules (students, fees, exams/results, admissions,
attendance, library, SMS, documents), a public website with CMS, and a JSON API under
`/api/v1` (payments, refunds, admissions, notifications). The legacy React SPA is preserved
in `archive/frontend/` for reference only.

> **Sibling products** (same UI, same feature set, different stack):
> `eskoofy-php/` (raw PHP, shared hosting) and `eskoofy-theme/` (WordPress). Keep the
> shared Blade templates and the CSS/JS in sync across all three.

## 🌟 Features

### 🏫 Academic Management
- Class & Section Management
- Subject Management
- Timetable & Scheduling
- Attendance Tracking
- Examination & Grading
- Result Processing

### 💰 Finance & Payments
- Fee Structure Management
- Online Payment Integration (bKash, Nagad, Cards)
- Invoice Generation
- Payment History & Receipts
- Financial Reports

### 👥 User Management
- Multi-role System (Admin, Teacher, Student, Parent)
- Role-based Access Control
- User Profiles & Dashboards
- Bulk User Import/Export

### 📱 Modern UI/UX
- Responsive Design
- Dark/Light Mode
- Multi-language Support (English/Bengali)
- Interactive Dashboards
- Real-time Notifications

### 📚 Additional Modules
- Library Management
- Transport Management
- Hostel Management
- Notice Board
- Events & Calendar
- SMS & Email Notifications

## 🚀 Technology Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.2+)
- **API**: RESTful JSON under `/api/v1` with a standard `{success, message, data}` envelope
- **Authentication**: Laravel Sanctum (web login via session routes)
- **Database**: SQLite (default dev) / MySQL 8 / PostgreSQL
- **Payments**: bKash, Nagad, Rocket (BD) + Stripe, PayPal, Paddle (INT) + offline/bank transfers, refunds
- **Caching/Queues**: database (default) or Redis
- **Search**: DB-native (public + dashboard search)

### Frontend (server-rendered)

- **Templates**: Laravel Blade (`resources/views`)
- **Routes**: `routes/web.php` (public site) + `routes/dashboard.php` (admin), `routes/api.php` + mounted groups for `/api/v1`
- **Assets**: Vite + Tailwind CSS 4 (`vite.config.js`)
- The old React SPA is preserved in `archive/frontend/` for reference only; the `frontend/` folder at the repo root is a short pointer README.

## 🛠️ Installation

### Prerequisites
- PHP 8.2+
- Composer 2.0+
- Node.js 18+
- SQLite (dev, no external DB needed) or MySQL 8 / PostgreSQL 10+
- Redis (optional, for caching)

### Quick Start

1. **Install Dependencies**
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   npm install
   ```

2. **Run Migrations & Seeders** (SQLite by default — no DB server required)
   ```bash
   php artisan migrate:fresh --seed
   ```

3. **Start development** (app, queue, Vite HMR and log tail all at once)
   ```bash
   composer dev
   ```

   Open **http://127.0.0.1:8000** — `/login`, then `/dashboard`. Admin credentials come from
   `ADMIN_EMAIL`/`ADMIN_PASSWORD` in `.env` (default `admin@school.com` /
   `ChangeMe!2026$Tr0ng` in this repo's `.env`). Student and guardian portals use
   `/student/login` and `/guardian/login`.

## 🚀 Deployment

Production deployment and operations guidance:

- Production security checklist: `../docs/operations/PRODUCTION-CHECKLIST.md`
- Runbooks (deploy, rollback, credential rotation, incidents): `../docs/operations/RUNBOOKS.md`
- Backup & restore: `../docs/operations/BACKUP-RESTORE.md`
- Payments deployment notes: `../docs/operations/PAYMENT-DEPLOYMENT.md`

### Docker (production-like stack)
```bash
docker-compose up -d
docker-compose exec php php artisan migrate --seed --force
```
Access at: **http://localhost:8080** (nginx → Laravel `public/`)

## 📚 Documentation

- [User Manual](docs/USER-MANUAL.md) — how school staff use every module
- [Setup Guide](docs/SETUP-GUIDE.md) — install, configure, deploy
- [API — Payments & Refunds](../docs/operations/API-PAYMENTS.md)
- [Admissions](../docs/operations/ADMISSIONS.md)
- [Dashboard troubleshooting](../docs/operations/DASHBOARD_TROUBLESHOOTING.md)
- [Production checklist](../docs/operations/PRODUCTION-CHECKLIST.md)
- [Runbooks](../docs/operations/RUNBOOKS.md)
- [Backup & Restore](../docs/operations/BACKUP-RESTORE.md)

## 🤝 Contributing

This is a private monorepo product folder. Work happens through the repo-level plan
(`WORKPLAN.md`) and the per-product `AGENTS.md` conventions. Before editing shared Blade
templates or the app's CSS/JS, remember they are mirrored by `eskoofy-php/` and
`eskoofy-theme/`.

## 📄 License

MIT — see the repo root for the project overview.
