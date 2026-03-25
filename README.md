# School Management System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Blade](https://img.shields.io/badge/UI-Laravel_Blade-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/docs/blade)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.x-06B6D4?logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

A School Management System built with **Laravel Blade** only: session auth, **SchoolEase**-style indigo sidebar, dashboard overview (live stats from your DB), and the same module URLs as the old React app (`/dashboard/students`, `/dashboard/cms/pages`, etc.). Module screens are Blade placeholders ready to wire to your controllers. JSON API routes remain under `/api` if needed.

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
- **Framework**: Laravel 10+
- **API**: RESTful API
- **Authentication**: Laravel Sanctum
- **Database**: MySQL/PostgreSQL
- **Caching**: Redis
- **Search**: Laravel Scout with Meilisearch

### Frontend (server-rendered)
- **Templates**: Laravel Blade (`backend/resources/views`)
- **Routes**: `backend/routes/web.php` (session authentication)
- **Assets**: Vite + Tailwind CSS 4 (`backend/vite.config.js`)
- The old React SPA is preserved in `archive/frontend/` for reference only; the `frontend/` folder at the repo root is a short pointer README.

## 🛠️ Installation

### Prerequisites
- PHP 8.1+
- Composer 2.0+
- Node.js 16+
- MySQL 5.7+ or PostgreSQL 10+
- Redis (Optional, for caching)

### Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/school-management-system.git
   cd school-management-system
   ```

2. **Install Backend Dependencies**
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. **Install Vite / Tailwind (for Blade assets)**
   ```bash
   cd backend
   npm install
   ```

4. **Configure Environment**
   - Update `backend/.env` with your database and app settings (ensure `SESSION_DRIVER` is set, e.g. `database` or `file`, for web login)

5. **Run Migrations & Seeders**
   ```bash
   cd backend
   php artisan migrate --seed
   ```

6. **Start development**
   ```bash
   cd backend
   php artisan serve
   # optional: in another terminal, for hot-reload of CSS/JS
   npm run dev
   ```

   Open **http://127.0.0.1:8000** — **/login**, then **/dashboard** (sidebar + stats). Demo users: [demo-credentials.md](demo-credentials.md).

## 🚀 Deployment

Choose your preferred deployment method:

### 1. Shared Web Hosting
See [Hosting Guide](hosting-architecture.md#2-web-hosting-deployment-eg-hostinger-bluehost-siteground) for detailed instructions.

### 2. VPS/Cloud Hosting
See [VPS Deployment Guide](hosting-architecture.md#3-vpscloud-hosting-eg-aws-digitalocean-linode) for complete setup.

### 3. Docker
```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan migrate --seed
```
Access at: **http://localhost:8080** (nginx → Laravel `public/`)

## 📚 Documentation

- [Architecture Overview](architecture-plan.md)
- [API Documentation](docs/API.md) (Coming Soon)
- [Payment Integration](payment-integration-prompt.md)
- [Hosting Guide](hosting-architecture.md)

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
