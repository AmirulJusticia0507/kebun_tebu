# Kebun Tebu MVP - User & Feature Walkthrough

## 1. Authentication & Security Options
### Web Login & Register
- **Login URL**: `http://127.0.0.1:8000/login`
- **Default Accounts**:
  - **Admin Kebun**: Email `admin@kebuntebu.id`, Password `password` (Role: `admin`).
  - **Petugas Lapangan**: Email `petugas@kebuntebu.id`, Password `password` (Role: `field_officer`).

### SweetAlert2 Logout Confirmation
- Clicking the **"Keluar"** button in the header triggers a SweetAlert2 popup asking for user confirmation before revoking session credentials.

---

## 2. GIS Spatial Monitoring Map (`/map`)
- **Interactive Control Sidebar**: Accessible on both desktop and mobile screens via the **"Filter & Legenda"** toggle button.
- **Filters Supported**: Category, Block, Date Range, and Incident Status (`OPEN`, `ON_PROGRESS`, `CLOSED`).
- **Spatial Features**:
  - OpenStreetMap, Esri Satellite, and OpenTopoMap base tiles.
  - Marker Clustering & Block Polygons.
  - Export Spatial Reports to **CSV** and **GeoJSON** format.
  - Quick action **"➕ Buat Laporan Lapangan"** buttons.

---

## 3. Spatie User & Role Management (`/dashboard/users`)
- **Admin Access Only**: Only users with the `admin` role can manage user accounts.
- **Features**:
  - List all officers and administrators.
  - Add new field officer/admin with automatic Spatie role assignment.
  - Reset user password via modal.
  - Delete user accounts with SweetAlert2 confirmation dialog.

---

## 4. Theme Adaptability & Cookie Compliance
- **Adaptive Dark / Light Theme**: Click the Sun/Moon icon in the navigation bar to toggle themes.
- **GDPR Cookie Consent**: A sticky cookie consent banner appears at the bottom until accepted, linking to `/privacy-policy`.
