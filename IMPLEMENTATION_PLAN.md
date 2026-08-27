# Kebun Tebu MVP - Technical Implementation Plan

## 1. System Overview
**Kebun Tebu MVP** is an enterprise-grade Geographic Information System (GIS) application built for sugarcane plantation monitoring, spatial incident reporting, offline data synchronization, and field officer management.

- **Backend Architecture**: Laravel 11 / 12, Inertia.js, Laravel Sanctum (Bearer Token / Mobile API Auth), Spatie Laravel Permission (RBAC).
- **Frontend Architecture**: Vue 3 (Composition API), Vite, Tailwind CSS (Dark Glassmorphism UI & Adaptive Light Mode), Leaflet GIS Engine + MarkerCluster.
- **Database Engine**: MySQL / MariaDB geospasial schema with spatial polygons (`geometry`), report indexes, and user role relations.

---

## 2. Authentication & Authorization Stack
### A. Web Session & API Bearer Token Authentication (Sanctum)
- **Web SPA Session**: Cookie-based encrypted session authentication for web users (`/login`, `/logout`, `/register`).
- **REST / Mobile API Token Authentication (Sanctum / OAuth style)**:
  - `POST /api/v1/auth/token`: Issues a Sanctum Bearer Token (`plainTextToken`) for mobile devices and offline GIS clients.
  - `GET /api/v1/auth/me`: Fetches authenticated user profile and active Spatie permissions.
  - `POST /api/v1/auth/logout`: Revokes the current active Bearer Token.

### B. Role-Based Access Control (Spatie Laravel Permission)
- **Admin Kebun (`admin`)**:
  - Full access to Analytics Dashboard (`/dashboard`), User & Officer Management (`/dashboard/users`), Plantation Block GIS Configuration (`/dashboard/blocks`), Category Management (`/dashboard/categories`), and Report Status Updates (`OPEN` -> `ON_PROGRESS` -> `CLOSED`).
- **Petugas Lapangan (`field_officer`)**:
  - Access to GIS Spatial Map (`/map`), Incident Field Reporting (`/reports/create`), and Offline Draft Sync (`/reports/sync`).

---

## 3. UI/UX & Compliance Features
1. **SweetAlert2 Confirmation Modals**:
   - Integrated into `AppLayout.vue` for user logout confirmation.
   - Integrated into User Management (`/dashboard/users`) for user deletion, account creation, and password reset actions.
2. **Adaptive Dark / Light Theme System**:
   - Persistent theme switcher stored in `localStorage`.
   - Global sun/moon icon toggle button in `AppLayout.vue` header.
3. **Privacy Policy & GDPR Cookie Consent**:
   - `/privacy-policy` route detailing data collection, spatial GPS coordinates, and session cookies.
   - Interactive `CookieConsent.vue` banner appearing across all pages with accept / privacy policy links.

---

## 4. Key File Structure
```
kebun_tebu/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/AuthController.php           # Sanctum API Bearer Token Auth
│   │   ├── Admin/UserController.php         # User & Spatie Role Management
│   │   ├── MapController.php                # GIS Monitoring Page
│   │   ├── ReportController.php             # Spatial Incident Reports & Offline Sync
│   │   └── DashboardController.php          # Admin Analytics
│   └── Models/
│       └── User.php                         # HasRoles & HasApiTokens
├── database/seeders/
│   ├── RoleSeeder.php                       # Spatie Permissions & Roles Seeder
│   └── AdminSeeder.php                      # Admin & Officer Accounts
├── resources/js/
│   ├── Components/
│   │   └── CookieConsent.vue                # Privacy & Cookie Banner
│   ├── Layouts/
│   │   └── AppLayout.vue                    # Adaptive Glassmorphism Navbar + SweetAlert
│   └── Pages/
│       ├── Map/Index.vue                    # Spatial Map & Filter Drawer
│       ├── Dashboard/Users/Index.vue        # User Management & Roles
│       └── PrivacyPolicy.vue                # Privacy & Cookie Policy Page
└── routes/
    ├── web.php                              # Web Inertia Routes
    └── api.php                              # Protected Sanctum API Endpoints
```