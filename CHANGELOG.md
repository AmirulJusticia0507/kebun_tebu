# Changelog

Semua perubahan penting project ini didokumentasikan di file ini.

Format berdasarkan [Keep a Changelog](https://keepachangelog.com/id/1.0.0/),
dan project ini mengikuti [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased] - Development

### Added
- Initial project setup dengan Laravel 13 + Vue 3 + Inertia.js
- PostgreSQL + PostGIS database configuration
- Authentication system (Login, Register, Password Reset, Email Verification)
- Role-based access control (Admin, Field Officer)
- Core Models: User, Category, Block, Report, ReportDraft, Notification
- Database migrations dengan foreign keys, indexes, soft deletes
- Spatie Activity Log untuk audit trail
- Spatie Laravel Permission untuk RBAC
- Laravel Sanctum untuk API authentication
- Vite + Tailwind CSS + PWA configuration
- Inertia.js Vue 3 layout system (AppLayout, GuestLayout)
- Auth Pages: Login, Register, ForgotPassword, ResetPassword
- Welcome landing page
- Dashboard dengan statistik, chart, recent reports
- Interactive Map page dengan Leaflet + MarkerCluster
- Map features: base layer toggle, block GeoJSON layer, filter sidebar
- Report detail sidebar di map
- QA Test Plan matrices (GPS, Camera/Upload)

### Changed
- N/A

### Deprecated
- N/A

### Removed
- N/A

### Fixed
- Migration order: blocks table sebelum reports table
- PostgreSQL connection configuration

### Security
- N/A

---

## [1.0.0] - 2026-08-27 (Planned MVP Release)

### Added
- Complete MVP features per spesifikasi
- PWA offline-first (IndexedDB + Background Sync)
- Web Push Notifications (VAPID)
- WhatsApp Gateway Integration (Fonnte)
- SLA Escalation & Daily Digest jobs
- PDF/Excel Export (dompdf + laravel-excel)
- Report Draft Management UI
- Admin CRUD: Users, Categories, Blocks
- Map Heatmap & Radius Filter
- GPS Spoofing Detection
- File Upload Security (MIME validation, virus scan)
- Rate Limiting & Throttle
- Comprehensive Test Suite (Pest + Playwright)
- CI/CD Pipeline (GitHub Actions)
- Production Deployment Scripts

### Changed
- N/A

### Deprecated
- N/A

### Removed
- N/A

### Fixed
- N/A

### Security
- CSP Headers implementation
- Input sanitization & validation
- Soft deletes untuk data retention
- Audit logging untuk sensitive actions

---

## [0.1.0] - 2026-08-27 (Initial Foundation)

### Added
- Laravel 13 project initialization
- Composer dependencies setup
- NPM dependencies setup
- Basic folder structure
- Environment configuration
- Documentation: Spesifikasi_MVP, IMPLEMENTATION_PLAN

---

## Legend

| Label | Deskripsi |
|-------|-----------|
| **Added** | Fitur baru |
| **Changed** | Perubahan pada fungsionalitas existing |
| **Deprecated** | Fitur yang akan dihapus di versi mendatang |
| **Removed** | Fitur yang dihapus |
| **Fixed** | Perbaikan bug |
| **Security** | Perbaikan keamanan |

---

## Versioning Scheme

```
MAJOR.MINOR.PATCH
```
- **MAJOR** - Breaking changes, API tidak kompatibel
- **MINOR** - Fitur baru, backward compatible
- **PATCH** - Bug fixes, backward compatible

---

## Release Process

1. Update version di `composer.json` dan `package.json`
2. Update `CHANGELOG.md` dengan ringkasan perubahan
3. Create git tag: `git tag -a v1.0.0 -m "Release v1.0.0"`
4. Push tags: `git push origin --tags`
5. GitHub Actions akan otomatis build & deploy ke staging
6. Manual approve untuk production deploy