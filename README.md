# 🌾 Kebun Tebu - Web-Based Monitoring Map

> **Peta Monitoring Kejadian Lapangan Perkebunan Tebu** - Aplikasi web mobile-first untuk pelaporan dan monitoring kejadian kebun tebu (kebakaran, hama, penyakit, banjir) secara real-time dengan peta interaktif.

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.x-9553E9?style=for-the-badge&logo=inertia)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css)](https://tailwindcss.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql)](https://postgresql.org)
[![PWA](https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa)](https://web.dev/progressive-web-apps/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

---

## 📋 Daftar Isi

- [Tentang Project](#-tentang-project)
- [Tech Stack](#-tech-stack)
- [Fitur Utama](#-fitur-utama)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Struktur Project](#-struktur-project)
- [API Documentation](#-api-documentation)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🎯 Tentang Project

**Kebun Tebu** adalah sistem monitoring berbasis web yang dirancang khusus untuk perkebunan tebu. Aplikasi ini memfasilitasi **petani/petugas lapangan** melaporkan kejadian di kebun (kebakaran, serangan hama, banjir, dll.) berbasis lokasi GPS secara real-time via browser HP, yang kemudian divisualisasikan dalam bentuk **peta interaktif** untuk pemantauan manajemen.

### Target Pengguna
| Role | Deskripsi |
|------|-----------|
| **Petugas Lapangan** | Melaporkan kejadian via form mobile, GPS auto-detect, upload foto |
| **Admin/Manajemen** | Memantau peta, filter data, mengubah status penanganan, export laporan |

### MVP Goals
- ✅ **Mudah & Ringan**: Form pelaporan intuitif, akses lancar via smartphone
- ✅ **Visual Spasial**: Distribusi kejadian + hotspot clustering di peta
- ✅ **Tindak Lanjut Cepat**: Status Open → On Progress → Closed dengan notifikasi

---

## 🛠️ Tech Stack

### Backend
| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Laravel | 13.x |
| Auth | Laravel Sanctum | 4.x |
| Queue/Jobs | Laravel Horizon + Redis | - |
| Activity Log | spatie/laravel-activitylog | 4.x |
| Permissions | spatie/laravel-permission | 8.x |
| Web Push | spatie/laravel-webpush | - |

### Frontend
| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Vue.js | 3.x (Composition API) |
| SPA Bridge | Inertia.js | 2.x |
| State Management | Pinia | 2.x |
| Styling | Tailwind CSS | 3.x |
| Map Engine | Leaflet.js + MarkerCluster | 1.9.x |
| PWA | Vite PWA Plugin + Workbox | - |

### Database & Infrastructure
| Component | Technology |
|-----------|------------|
| Primary DB | PostgreSQL 16 + PostGIS |
| Cache/Queue | Redis 7 |
| File Storage | Local / S3 Compatible |
| Web Server | Nginx + PHP-FPM |
| CI/CD | GitHub Actions |

---

## ✨ Fitur Utama

### 📱 Sisi Petugas Lapangan
- **Form Pelaporan Mobile-First** - Responsive, PWA-ready, installable
- **GPS Auto-Detect** - Browser Geolocation API dengan fallback manual pin
- **Upload Foto + Kompresi** - Client-side compression < 1MB sebelum upload
- **Checklist Dinamis** - Per kategori kejadian (kebakaran, hama, dll.)
- **Offline-First (PWA)** - IndexedDB + Background Sync untuk area tanpa sinyal
- **Voice Note** - Rekam suara singkat (opsional)

### 🗺️ Sisi Manajemen / Dashboard
- **Peta Interaktif** - Leaflet.js + MarkerCluster + Layer Blok (GeoJSON)
- **Filter Multi-Kriteria** - Kategori, Blok, Tanggal, Status
- **Heatmap & Radius Filter** - Visualisasi kepadatan kejadian
- **Manajemen Status** - Open 🔴 → On Progress 🟡 → Closed 🟢
- **Dashboard Statistik** - Chart.js: trend, distribusi, resolution time
- **Export Data** - PDF, Excel, GeoJSON/KML

### 🔔 Notifikasi & Otomasi
- **In-App Notifications** - Bell icon real-time
- **WhatsApp Gateway** - Fonnte/Wablas integration
- **Web Push (VAPID)** - Browser push notification
- **SLA Escalation** - Otomatis notif jika deadline terlampaui
- **Daily Digest** - Ringkasan harian otomatis jam 07:00

---

## 💻 Persyaratan Sistem

### Development
- **PHP** ≥ 8.3
- **Composer** ≥ 2.6
- **Node.js** ≥ 20.x (LTS)
- **npm** ≥ 10.x
- **PostgreSQL** ≥ 15 dengan ekstensi **PostGIS**
- **Redis** ≥ 7
- **Git** ≥ 2.40

### Production (Minimum)
- **VPS** 2 vCPU, 4GB RAM, 50GB SSD
- **Ubuntu** 22.04 LTS / 24.04 LTS
- **Nginx** + **PHP-FPM 8.3**
- **SSL** Let's Encrypt (Certbot)
- **Domain** custom (contoh: kebuntebu.id)

---

## 🚀 Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/username/kebun-tebu.git
cd kebun-tebu
```

### 2. Install Dependencies Backend
```bash
composer install --optimize-autoloader
```

### 3. Install Dependencies Frontend
```bash
npm install
```

### 4. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dengan konfigurasi database:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kebun_tebu
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# PWA VAPID Keys (generate via: php artisan vapid:generate)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:admin@kebuntebu.id

# WhatsApp Gateway (optional)
FONNTE_TOKEN=
FONNTE_SENDER_ID=
```

### 5. Database Setup
```bash
# Create database (PostgreSQL)
createdb kebun_tebu
psql -d kebun_tebu -c "CREATE EXTENSION postgis;"

# Run migrations
php artisan migrate --force

# Seed data awal (admin, kategori, blok contoh)
php artisan db:seed
```

### 6. Storage Link
```bash
php artisan storage:link
```

### 7. Build Assets
```bash
# Development
npm run dev

# Production
npm run build
```

---

## ⚙️ Konfigurasi

### VAPID Keys untuk Web Push
```bash
php artisan vapid:generate
```
Masukkan hasil ke `.env`:
```env
VAPID_PUBLIC_KEY=BFxxxx...
VAPID_PRIVATE_KEY=xxxx...
VAPID_SUBJECT=mailto:admin@kebuntebu.id
```

### Queue Worker (Production)
```bash
# Supervisor config untuk Horizon
php artisan horizon

# Atau manual queue worker
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

### Scheduler (Cron)
```bash
# Tambahkan ke crontab (crontab -e)
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## ▶️ Menjalankan Aplikasi

### Development
```bash
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Vite Dev Server (HMR)
npm run dev

# Terminal 3: Queue Worker (optional)
php artisan queue:work

# Terminal 4: Horizon Dashboard (optional)
php artisan horizon
```

Akses: **http://localhost:8000**

### Production (Docker)
```bash
# Build image
docker build -t kebun-tebu .

# Run dengan docker-compose
docker-compose up -d
```

---

## 🧪 Testing

### Backend Testing (Pest PHP)
```bash
# Run semua test
php artisan test

# Run dengan coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/ReportTest.php
```

### Frontend Testing (Playwright)
```bash
# Install browsers
npx playwright install

# Run E2E tests
npx playwright test

# Run dengan UI
npx playwright test --ui
```

### Static Analysis
```bash
# PHPStan
php artisan phpstan analyse

# Laravel Pint (Code Style)
./vendor/bin/pint

# ESLint
npm run lint
```

---

## 📦 Deployment

### Manual Deploy ke VPS
```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Clear & cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart services
sudo systemctl reload nginx
sudo systemctl restart php8.3-fpm
sudo supervisorctl restart horizon:*
```

### CI/CD Pipeline (GitHub Actions)
Workflow otomatis di `.github/workflows/ci.yml`:
1. **Test** - Pest + PHPStan + Pint
2. **Build** - npm ci + npm run build + Docker build
3. **Deploy** - SSH ke VPS + run deploy script

---

## 📁 Struktur Project

```
kebun-tebu/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/           # Login, Register, Password Reset
│   │   │   ├── ReportController.php
│   │   │   ├── MapController.php
│   │   │   ├── DashboardController.php
│   │   │   └── Api/            # API endpoints untuk mobile/PWA
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php
│   │   │   └── PermissionMiddleware.php
│   │   └── Requests/           # Form Request Validations
│   ├── Models/
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── Block.php
│   │   ├── Report.php
│   │   ├── ReportDraft.php
│   │   └── Notification.php
│   ├── Policies/
│   │   └── ReportPolicy.php
│   └── Notifications/
│       ├── ReportStatusChanged.php
│       └── SlaWarningNotification.php
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   │   ├── Components/         # Vue Components reusable
│   │   ├── Composables/        # Vue Composables (useMap, useGps, etc)
│   │   ├── Layouts/            # AppLayout, GuestLayout
│   │   ├── Pages/              # Inertia Pages
│   │   │   ├── Auth/
│   │   │   ├── Reports/
│   │   │   ├── Map/
│   │   │   └── Dashboard/
│   │   ├── Stores/             # Pinia Stores
│   │   ├── app.js
│   │   └── bootstrap.js
│   └── views/
├── routes/
│   ├── web.php
│   ├── auth.php
│   ├── api.php
│   └── console.php
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── vendor/
├── .env.example
├── artisan
├── composer.json
├── package.json
├── vite.config.js
├── tailwind.config.js
├── phpstan.neon
├── pest.php
├── README.md
├── Spesifikasi_MVP_Kebun_Tebu.md
├── IMPLEMENTATION_PLAN.md
└── CHANGELOG.md
```

---

## 📚 API Documentation

### Base URL
```
Development: http://localhost:8000
Production:  https://kebuntebu.id
```

### Authentication
Gunakan **Laravel Sanctum** token atau session cookie (Inertia).

### Endpoints Utama

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/map` | Peta monitoring (Inertia page) | ✅ |
| GET | `/reports/create` | Form laporan baru | ✅ |
| POST | `/reports` | Simpan laporan | ✅ |
| GET | `/reports/{id}` | Detail laporan | ✅ |
| PATCH | `/reports/{id}/status` | Update status | ✅ Admin |
| GET | `/dashboard` | Dashboard statistik | ✅ Admin |
| GET | `/api/reports` | List laporan (JSON) | ✅ |
| GET | `/api/blocks/geojson` | GeoJSON blok kebun | ✅ |
| POST | `/reports/sync` | Sync offline drafts | ✅ |

### Response Format
```json
{
  "data": {},
  "meta": {},
  "links": {}
}
```

### Error Format
```json
{
  "message": "Validation failed",
  "errors": {
    "field": ["Error message"]
  }
}
```

---

## 🤝 Contributing

### Branch Strategy
- `main` - Production ready
- `develop` - Integration branch
- `feature/*` - Feature branches
- `hotfix/*` - Emergency fixes

### Commit Convention (Conventional Commits)
```
feat: add offline draft sync for reports
fix: resolve GPS accuracy issue on iOS
docs: update API documentation
refactor: extract map components
test: add E2E test for report creation
```

### Pull Request Checklist
- [ ] Tests pass (`php artisan test`)
- [ ] Code style passes (`./vendor/bin/pint`)
- [ ] Static analysis passes (`php artisan phpstan analyse`)
- [ ] Frontend lint passes (`npm run lint`)
- [ ] No console errors in browser
- [ ] Mobile responsive verified
- [ ] Offline functionality tested

---

## 📄 License

Distributed under the **MIT License**. See `LICENSE` for more information.

---

## 👥 Tim & Kontak

| Role | Nama | Kontak |
|------|------|--------|
| Project Owner | [Nama] | email@domain.com |
| Lead Developer | [Nama] | email@domain.com |
| DevOps | [Nama] | email@domain.com |

---

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
- [Vue.js](https://vuejs.org) - The Progressive JavaScript Framework
- [Inertia.js](https://inertiajs.com) - The Modern Monolith
- [Leaflet](https://leafletjs.com) - Open-Source JavaScript Library for Mobile-Friendly Interactive Maps
- [OpenStreetMap](https://openstreetmap.org) - Free Map Data
- [Tailwind CSS](https://tailwindcss.com) - Utility-First CSS Framework
- [Pest PHP](https://pestphp.com) - Delightful PHP Testing Framework

---

> **Dibangun dengan ❤️ untuk petani & perkebunan tebu Indonesia**