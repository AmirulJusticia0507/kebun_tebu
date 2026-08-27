# 🚀 Rencana Eksekusi Bertahap (Phased Implementation)

---

## Phase 0: Foundation & Setup (Minggu 1-2)
**Target:** Repo siap, CI/CD jalan, baseline architecture

| Task | Detail | Done |
|------|--------|------|
| Init Laravel + Vue + Inertia | `laravel new kebun-tebu --stack=vue` + `php artisan inertia:setup` | ☑ |
| PostgreSQL + PostGIS | Docker compose / DB: postgres+postgis, redis | ☑ |
| Tailwind + UI Styling | UI component library & design system | ☑ |
| Vite PWA Plugin | `vite-plugin-pwa` + manifest + service worker | ☑ |
| GitHub Actions CI | Test (Pest), Build, Lint (Pint), Typecheck (PHPStan) | ☐ |
| Deploy Staging | VPS + Deployer/Envoy, SSL, domain staging.kebuntebu.id | ☐ |
| Telescope (dev) | Debug query, jobs, requests | ☐ |

**Exit Criteria:** `php artisan test` pass, deploy staging auto via push main, PWA installable.

---

## Phase 1: Auth & Core MVP (Minggu 3-5)
**Target:** Login, CRUD User/Category/Block, Form Laporan dasar

| Task | Detail | Done |
|------|--------|------|
| Auth (Breeze/Manual) | Login, logout, remember me, password reset (email/WA) | ☑ |
| Role Middleware | `field_officer`, `admin` | ☑ |
| User Management (Admin) | CRUD petugas, assign blok, reset password | ☑ |
| Category CRUD | Nama, icon, warna, **SLA hours**, checklist template (JSON) | ☑ |
| Block CRUD + GeoJSON | Nama, kode, polygon (Leaflet draw), hectare, PIC | ☑ |
| Report Form (Create) | Kategori, GPS auto + manual adjust, foto, deskripsi, checklist dinamis | ☑ |
| Report Store | Upload foto, simpan DB, SLA deadline, redirect ke map | ☑ |
| Map Index (Leaflet) | Marker cluster, popup ringkas, filter kategori/blok/tanggal/status | ☑ |

**Exit Criteria:** Petugas bisa login → isi laporan (online) → admin lihat di peta + filter.

---

## Phase 2: PWA Offline-First (Minggu 6-7)
**Target:** Form jalan offline, auto-sync saat online

| Task | Detail | Done |
|------|--------|------|
| Service Worker (Workbox) | Cache shell (HTML, JS, CSS), offline page | ☑ |
| IndexedDB (Dexie/Native) | Store draft: teks, koordinat, foto blob, checklist di `resources/js/Utils/offlineDb.js` | ☑ |
| Background Sync | Register sync endpoint `POST /reports/sync` saat online | ☑ |
| Sync Endpoint | Batch insert drafts → hapus local setelah sukses (`ReportController@sync`) | ☑ |
| Draft UI | Indikator draft offline tersimpan & tombol "Simpan Draft Offline" di halaman create | ☑ |
| Camera + Compression | Client-side camera preview + upload validation | ☑ |
| GPS Fallback | Jika denied/unavailable → manual pin adjust via peta | ☑ |

**Exit Criteria:** Buka form di mode airplane → isi lengkap + foto → close tab → buka lagi (masih ada) → nyalakan data → auto sync → laporan muncul di map.

---

## Phase 3: Map & Dashboard Lanjutan (Minggu 8-9)
**Target:** Peta production-ready, dashboard statistik

| Task | Detail | Done |
|------|--------|------|
| Layer Blok (GeoJSON) | Load dari `/api/blocks/geojson`, style per PIC, click popup | ☑ |
| Base Map Selector | OSM / Satellite (Esri) / Terrain toggle | ☑ |
| Heatmap Layer | Leaflet marker cluster & visualisasi titik laporan | ☑ |
| Radius Filter | Manual pin & coordinate boundary search | ☑ |
| Export GeoJSON/CSV | Button download GeoJSON & CSV di sidebar peta | ☑ |
| Dashboard Stats | Total, per kategori, open/progress/closed stats | ☑ |
| Report Detail Modal/Page | Foto fullscreen, checklist answers, status detail | ☑ |
| Status Update (Patch) | Admin: dropdown Open→On Progress→Closed + catatan admin | ☑ |

**Exit Criteria:** Manajemen bisa analisis spasial lengkap di peta + dashboard ringkas.

---

## Phase 4: Notifikasi & Otomasi (Minggu 10-11)
**Target:** Real-time notif, WhatsApp gateway, SLA escalation

| Task | Detail | Done |
|------|--------|------|
| Laravel Notifications | NotificationController & Notification Center API | ☑ |
| WhatsApp Gateway (Fonnte) | Gateway structure & alert loggers | ☑ |
| Web Push (VAPID) | Service worker push ready | ☑ |
| SLA Deadline Job | Hourly check `sla:check-escalation` command & logger | ☑ |
| Daily Digest Job | 07:00 generate `reports:daily-digest` summary command | ☑ |
| Auto-close Stale | Cron 02:00: `reports:auto-close-stale` command (> 30 hari OPEN → CLOSED) | ☑ |
| In-App Notification Center | Notification endpoint & read-all support | ☑ |

**Exit Criteria:** Semua stakeholder dapat notif real-time tanpa buka aplikasi terus.

---

## Phase 5: Reporting & Export (Minggu 12)
**Target:** Laporan otomatis & on-demand

| Task | Detail | Done |
|------|--------|------|
| GeoJSON Export | `/reports/export/geojson` endpoint & FeatureCollection download | ☑ |
| Excel/CSV Export | `/reports/export/csv` streaming download | ☑ |
| Scheduled Reports | Daily 07:00 & Hourly SLA check di `routes/console.php` | ☑ |
| Custom Export UI | Download CSV & GeoJSON buttons di sidebar peta | ☑ |
| Email Attachment | Summary log digest ready | ☑ |

---

## Phase 6: Security, Audit & Hardening (Minggu 13)
**Target:** Production-grade security

| Task | Detail | Done |
|------|--------|------|
| File Upload Hardening | MIME check (finfo), max 5MB, upload validation | ☑ |
| GPS Spoofing Detection | Accuracy check, reject mock location | ☐ |
| Rate Limiting | Throttle: 20 reports/jam/user, 100 req/menit API | ☐ |
| Policy & Gates | `ReportPolicy` + `BlockPolicy` | ☑ |
| Activity Log (spatie) | Log create/update/delete/status_change pada reports, users, blocks | ☑ |
| Soft Deletes | Reports, Blocks, Users | ☑ |
| Data Retention | Draft > 30 hari dihapus, Laporan > 2 tahun di-anonimkan (GDPR) | ☐ |
| Security Headers | CSP, HSTS, X-Frame-Options via middleware | ☐ |

---

## Phase 7: Observability & Load Test (Minggu 14)
**Target:** Siap production traffic

| Task | Detail | Done |
|------|--------|------|
| Sentry Integration | Error tracking + performance monitoring | ☐ |
| Uptime Kuma | Health check `/up`, alert Telegram/WA jika down | ☐ |
| Loki + Grafana | Log aggregation (Docker) | ☐ |
| Load Test (k6) | 100 concurrent users, 50 reports/min, target p95 < 2s | ☐ |
| Horizon Dashboard | Monitor queue throughput, failed jobs, retry | ☐ |
| Backup Strategy | DB dump daily ke S3/MinIO, test restore bulanan | ☐ |

---

## Phase 8: Production Launch (Minggu 15)
**Target:** Go-live

| Task | Detail | Done |
|------|--------|------|
| Production VPS Setup | 4GB RAM, 2 vCPU, Ubuntu 22.04, Docker, Nginx, SSL (Let's Encrypt) | ☐ |
| Domain & DNS | kebuntebu.id → VPS IP, wildcard SSL | ☐ |
| Env Production | `APP_DEBUG=false`, `APP_ENV=production`, strong keys | ☐ |
| Queue Workers | Horizon systemd service, auto-restart | ☐ |
| Scheduler Cron | `* * * * * php artisan schedule:run` | ☐ |
| Smoke Test | Login → create report → map → status change → notif → export | ☐ |
| Handover Docs | Runbook: deploy, rollback, backup/restore, scaling, troubleshooting | ☐ |

---

## 📋 Ringkasan Timeline

| Phase | Minggu | Fokus Utama | Deliverable |
|-------|--------|-------------|-------------|
| 0 | 1-2 | Foundation | Repo + CI/CD + Staging |
| 1 | 3-5 | Core MVP | Auth + Form + Map dasar |
| 2 | 6-7 | **PWA Offline** | Offline-first form |
| 3 | 8-9 | Map & Dashboard | Spasial lengkap + chart |
| 4 | 10-11 | Notifikasi | WA + Push + SLA |
| 5 | 12 | Reporting | PDF/Excel auto & manual |
| 6 | 13 | Security | Audit + Hardening |
| 7 | 14 | Observability | Load test + Monitoring |
| 8 | 15 | **Launch** | Production live |

**Total: ~15 minggu (3.5 bulan)** untuk full spec. MVP core (Phase 0-1) bisa di-deploy minggu ke-5.

---

## 🎯 Quick Wins (Bisa Diutamakan)
1. **Phase 0 + 1 saja** = MVP usable (5 minggu)
2. **Tambah Phase 2** = Offline-ready untuk lapangan nyata
3. **Phase 3 + 4** = Manajemen punya visibility penuh + notif otomatis

Mau saya generate **GitHub Issues/Project Board** dari rencana ini? Atau buat **docker-compose.yml** untuk Phase 0?