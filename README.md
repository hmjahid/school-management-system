# School Management System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Blade](https://img.shields.io/badge/UI-Laravel_Blade-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/docs/blade)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.x-06B6D4?logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

A School Management System built with **Laravel Blade** (repo root = the app): session
auth, SchoolEase-style indigo sidebar, live-stat dashboard, full admin modules
(students, fees, exams/results, admissions, attendance, library, SMS, documents),
a public website with CMS, and a JSON API under `/api/v1` (payments, refunds,
admissions, notifications). The legacy React SPA is preserved in `archive/frontend/`
for reference only.

![School Management System Dashboard Preview](https://via.placeholder.com/1200x600/4F46E5/FFFFFF?text=School+Management+System+Dashboard)

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
- **Payments**: bKash, Nagad, Rocket adapters + offline/bank transfers, refunds
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

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/school-management-system.git
   cd school-management-system
   ```

2. **Install Dependencies**
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   npm install
   ```

3. **Run Migrations & Seeders** (SQLite by default — no DB server required)
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Start development** (app, queue, Vite HMR and log tail all at once)
   ```bash
   composer dev
   ```

   Open **http://127.0.0.1:8000** — `/login`, then `/dashboard`. Admin credentials come from `ADMIN_EMAIL`/`ADMIN_PASSWORD` in `.env` (see `.env.example`).

## 🚀 Deployment

Production deployment and operations guidance:

- Production security checklist: `docs/PRODUCTION-CHECKLIST.md`
- Runbooks (deploy, rollback, credential rotation, incidents): `docs/RUNBOOKS.md`
- Backup & restore: `docs/BACKUP-RESTORE.md`
- Payments deployment notes: `docs/PAYMENT-DEPLOYMENT.md`

### Docker (production-like stack)
```bash
docker-compose up -d
docker-compose exec php php artisan migrate --seed --force
```
Access at: **http://localhost:8080** (nginx → Laravel `public/`)

## 📚 Documentation

- [API — Payments & Refunds](docs/API-PAYMENTS.md)
- [Admissions](docs/ADMISSIONS.md)
- [Dashboard troubleshooting](docs/DASHBOARD_TROUBLESHOOTING.md)
- [Production checklist](docs/PRODUCTION-CHECKLIST.md)
- [Runbooks](docs/RUNBOOKS.md)
- [Backup & Restore](docs/BACKUP-RESTORE.md)

## 🤝 Contributing

Contributions are welcome! Please read our [contributing guidelines](CONTRIBUTING.md) to get started.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📧 Contact

For any inquiries, please contact [your-email@example.com](mailto:your-email@example.com)

---

<div align="center">
  Made with ❤️ using Laravel Blade & Tailwind CSS
</div>
