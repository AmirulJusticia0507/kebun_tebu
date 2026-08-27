# 🌾 Dokumen Spesifikasi Lengkap MVP: Web-Based Monitoring Map (Perkebunan Tebu)

---

## 🛠️ Technical Architecture & Tech Stack

Proyek ini dibangun menggunakan arsitektur **Monolithic Fullstack** (Frontend & Backend dalam 1 repository/project) menggunakan **Inertia.js**. Pendekatan ini memberikan pengalaman *Single Page Application (SPA)* berbasis Vue.js tanpa perlu membuat REST API terpisah secara manual.

* **Backend Framework:** **Laravel** (Versi Terbaru)
* **Frontend Framework:** **Vue.js** (Versi 3 dengan Composition API / `<script setup>`)
* **Bridge / Adapter:** **Inertia.js** *(Menghubungkan Laravel & Vue.js secara seamless)*
* **Styling & UI:** **Tailwind CSS** (Mobile-First, Fully Responsive)
* **Interactive Map:** **Leaflet.js** + **Leaflet.markercluster**
* **Tile Provider (Peta Base):** **OpenStreetMap (OSM)** *(Gratis, tanpa API key)*
* **Database:** **PostgreSQL + PostGIS** *(Rekomendasi Utama)* atau **MySQL**
* **Build Tool:** **Vite** (Bawaan Laravel)

---

## 1. Scope of Work (SOW) & Dokumen Kebutuhan Sistem

### 1.1 Ringkasan Proyek

Sistem ini merupakan aplikasi berbasis web (**Mobile-Responsive / PWA Ready**) yang berfungsi sebagai **Peta Monitoring Kejadian Lapangan** untuk perkebunan tebu. Aplikasi ini dirancang agar petani atau petugas lapangan dapat melaporkan kejadian di kebun secara langsung (*real-time*) melalui browser ponsel, yang kemudian ditampilkan secara visual pada peta interaktif bagi tim manajemen.

### 1.2 Sasaran Utama (MVP Goal)

* **Mudah & Ringan:** Form pelaporan intuitif, dapat diakses lancar via *smartphone* (Chrome/Safari).
* **Visual Spasial:** Menampilkan distribusi kejadian dan area *hotspot* (penumpukan laporan berdekatan).
* **Tindak Lanjut Cepat:** Manajemen dapat memantau dan mengubah status penanganan laporan.

### 1.3 Fitur Utama

#### A. Sisi Petugas Lapangan / Pelapor

1. **Form Pelaporan Lapangan:**
   * **Kategori Kejadian:**
     * 🔥 Kebakaran tebu
     * 🐛 Serangan hama
     * 🦠 Penyakit tanaman
     * 💧 Banjir / Genangan air
     * ⚠️ Kendala lainnya
   * **GPS / Location Tagging:** Otomatis mengambil koordinat lokasi via browser HP (*Geolocator*), dengan opsi penyesuaian titik manual di peta kecil.
   * **Tanggal & Waktu:** Otomatis tercatat saat laporan dibuat.
   * **Upload Foto:** Mengunggah foto bukti kondisi lapangan dari kamera/galeri HP.
   * **Keterangan Tambahan:** Input teks bebas untuk penjelasan singkat.

#### B. Sisi Manajemen / Dashboard Admin

2. **Peta Interaktif (Interactive Map):**
   * Visualisasi *pin point* lokasi kejadian.
   * Fitur *Clustering / Hotspot View* untuk melihat penumpukan kejadian pada area berdekatan.
   * Informasi ringkas (*pop-up*) saat titik lokasi diklik.
3. **Filter Data:**
   * Filter berdasarkan Jenis Kejadian (misal: hanya tampilkan Kebakaran).
   * Filter berdasarkan Wilayah / Blok Kebun.
   * Filter berdasarkan Rentang Tanggal / Periode.
   * Filter berdasarkan Status Penanganan.
4. **Pengelolaan Status Penanganan:**
   * Mengubah status laporan: **Open** 🔴 ➡️ **On Progress** 🟡 ➡️ **Closed** 🟢.
   * Catatan singkat dari petugas tindak lanjut.
5. **Dashboard Ringkas:**
   * Ringkasan statistik (Total laporan, statistik per kategori kejadian, dan persentase laporan selesai).

---

## 2. Broadcast Lowongan Proyek (Teks Iklan/DM)

```text
📢 WTB / FREELANCE OPPORTUNITY: Web-Based Monitoring Map (Perkebunan Tebu)

Halo rekan-rekan developer, saya sedang mencari freelancer / micro-agency untuk membangun proyek Web-Based Monitoring Map sederhana (MVP) untuk pemantauan lapangan perkebunan tebu.

📌 Gambaran Proyek:
Sistem ini bertujuan memfasilitasi petani/petugas lapangan dalam melaporkan kejadian di kebun (seperti kebakaran, serangan hama, banjir, dll.) berbasis lokasi GPS secara real-time via browser HP, yang kemudian divisualisasikan dalam bentuk peta interaktif untuk pemantauan manajemen.

🛠️ Preferred Tech Stack (Monolith Single Repo):
- Backend: Laravel (Versi Terbaru)
- Frontend: Vue.js 3 + Inertia.js + Tailwind CSS
- Map Engine: Leaflet.js + OpenStreetMap (OSM)
- Database: PostgreSQL / MySQL

🔑 Fitur Utama (MVP):
1. Form Input Laporan (Mobile Responsive / PWA Friendly)
2. GPS / Location Tagging (Auto-detect lokasi via HP)
3. Upload Foto & Keterangan Lapangan
4. Interactive Map (Visualisasi pin point & hotspot clustering)
5. Filter Data (Jenis kejadian, wilayah, & periode)
6. Status Penanganan (Open ➔ On Progress ➔ Closed)
7. Dashboard Sederhana (Statistik ringkas)

📩 Cara Melamar:
Bagi yang berminat, silakan kirimkan Direct Message (DM) / kontak saya dengan menyertakan:
- Portofolio / Proyek Sejenis (khususnya Laravel + Vue.js / Interactive Map)
- Estimasi Waktu Pengerjaan (Timeline)
- Estimasi Biaya / Penawaran Harga
- Kontak yang Bisa Dihubungi (No. WhatsApp / Email)

Detail Requirement dan alur kerja yang lebih spesifik akan didiskusikan lebih lanjut setelah penyaringan awal. Terima kasih!
```

---

## 3. Estimasi Biaya Infrastruktur (Low Cost MVP)

| Komponen | Layanan Direkomendasikan | Estimasi Biaya (Tahap MVP) |
| :--- | :--- | :--- |
| **Hosting & Server** | VPS Murah (BiznetGio / Niagahoster / IdCloudHost) atau Railway / Render | Rp 50.000 - Rp 100.000 / bulan |
| **Database** | Managed PostgreSQL (Supabase Free Tier) atau Local DB Server | Rp 0 / bulan |
| **Domain Custom (.id / .com)** | Registrar Lokal | ~Rp 150.000 / tahun |
| **Map API** | OpenStreetMap + Leaflet.js | **Rp 0** (Free Open Source) |
| **TOTAL ESTIMASI INFRASTRUKTUR** | | **± Rp 150.000 / tahun + Biaya Hosting** |

---

## 4. Skema Tabel Database (ERD)

### 4.1 Tabel `users`
Menyimpan data otentikasi dan identitas pelapor maupun admin.

| Nama Kolom | Tipe Data | Keterangan / Constraints |
| :--- | :--- | :--- |
| `id` | BIGINT | Primary Key, Auto Increment |
| `name` | VARCHAR(100) | Nama lengkap pengguna |
| `email` | VARCHAR(100) | Unique, untuk login |
| `password` | VARCHAR(255) | Hash password |
| `role` | ENUM | `'field_officer'`, `'admin'` |
| `phone_number` | VARCHAR(20) | Nomor HP / WhatsApp |
| `created_at` | TIMESTAMP | Waktu akun dibuat |

### 4.2 Tabel `categories`
Menyimpan daftar kategori kejadian.

| Nama Kolom | Tipe Data | Keterangan / Constraints |
| :--- | :--- | :--- |
| `id` | INT | Primary Key, Auto Increment |
| `name` | VARCHAR(50) | Nama kejadian (e.g. Kebakaran, Hama) |
| `icon_marker` | VARCHAR(100) | File icon penanda peta (e.g. `fire.png`) |
| `color_code` | VARCHAR(10) | Kode warna hex (e.g. `#FF0000`) |

### 4.3 Tabel `reports`
Menyimpan detail laporan kejadian dari lapangan.

| Nama Kolom | Tipe Data | Keterangan / Constraints |
| :--- | :--- | :--- |
| `id` | BIGINT | Primary Key, Auto Increment |
| `user_id` | BIGINT | Foreign Key ➔ `users.id` |
| `category_id` | INT | Foreign Key ➔ `categories.id` |
| `title` | VARCHAR(150) | Judul singkat kejadian |
| `description` | TEXT | Keterangan/kronologi kejadian |
| `latitude` | DECIMAL(10, 8) | Koordinat Lintang (misal: `-7.79560000`) |
| `longitude` | DECIMAL(11, 8) | Koordinat Bujur (misal: `110.36950000`) |
| `block_code` | VARCHAR(50) | Kode Blok/Wilayah Kebun (e.g. `BLOK-A12`) |
| `photo_url` | VARCHAR(255) | Path/URL file foto bukti |
| `status` | ENUM | `'OPEN'`, `'ON_PROGRESS'`, `'CLOSED'` |
| `admin_note` | TEXT | Catatan tindak lanjut admin |
| `handled_by` | BIGINT | Foreign Key ➔ `users.id` (Admin penanggung jawab) |
| `reported_at` | TIMESTAMP | Waktu kejadian dilaporkan |
| `updated_at` | TIMESTAMP | Waktu update terakhir |

---

## 5. Struktur Routing & Controller (Laravel + Inertia)

Karena menggunakan **Inertia.js**, Routing ditangani oleh Laravel dan merender komponen **Vue.js**:

| HTTP Method | Route URL | Controller & Method | Render Vue Page / Action |
| :--- | :--- | :--- | :--- |
| **GET** | `/login` | `AuthenticatedSessionController@create` | `Pages/Auth/Login.vue` |
| **POST** | `/login` | `AuthenticatedSessionController@store` | Authenticate User |
| **GET** | `/reports/create` | `ReportController@create` | `Pages/Reports/Create.vue` (Form Input & GPS) |
| **POST** | `/reports` | `ReportController@store` | Handle File Upload & Save DB ➔ Redirect |
| **GET** | `/map` | `MapController@index` | `Pages/Map/Index.vue` (Leaflet Interactive Map) |
| **GET** | `/reports/{id}` | `ReportController@show` | `Pages/Reports/Show.vue` (Detail Laporan) |
| **PATCH** | `/reports/{id}/status` | `ReportStatusController@update` | Update Status ➔ Refresh Page |
| **GET** | `/dashboard` | `DashboardController@index` | `Pages/Dashboard.vue` (Ringkasan Statistik) |

---

## 6. QA Test Plan (Pengujian Lapangan)

### 6.1 Matriks Pengujian GPS / Location Tagging

| ID Test | Skenario Pengujian | Hasil yang Diharapkan |
| :--- | :--- | :--- |
| **GPS-01** | *Location Permission* | Browser menampilkan pop-up izin lokasi saat form dibuka pertama kali. |
| **GPS-02** | Auto-detect GPS | Koordinat Latitude & Longitude terisi otomatis dengan akurasi < 15 meter. |
| **GPS-03** | Penyesuaian Manual (*Pin Adjust*) | Menggeser *pin* di peta kecil pada form otomatis memperbarui koordinat teks. |
| **GPS-04** | GPS HP Non-Aktif | Tampil peringatan: *"GPS Anda belum aktif. Aktifkan GPS atau pilih titik di peta."* |
| **GPS-05** | Akses Ditolak (*Denied*) | Tampil opsi input lokasi cadangan (pilih manual via peta). |

### 6.2 Matriks Pengujian Upload Foto Lapangan

| ID Test | Skenario Pengujian | Hasil yang Diharapkan |
| :--- | :--- | :--- |
| **CAM-01** | Tangkap Foto via Kamera HP | Klik tombol kamera membuka kamera bawaan HP, foto tampil di *preview*. |
| **CAM-02** | Kompresi Otomatis | Foto resolusi tinggi (8-15 MB) otomatis dikompres *client-side* menjadi < 1 MB sebelum diunggah. |
| **CAM-03** | Validasi Format | File non-gambar (PDF/DOCX) ditolak dengan pesan *error* validasi. |
| **CAM-04** | Penanganan Sinyal Lemah | Saat internet lambat/putus, data teks & koordinat yang diisi tidak terhapus (*retain form data*). |

---

## 7. Fitur Lanjutan & Roadmap (Post-MVP)

### 7.1 PWA & Offline-First (Prioritas Tinggi)
| Fitur | Deskripsi | Teknologi |
|-------|-----------|-----------|
| **Service Worker & Cache API** | Form pelaporan tetap bisa dibuka & diisi offline | Workbox / Vite PWA Plugin |
| **Background Sync** | Auto-submit data + foto saat koneksi pulih | Background Sync API |
| **IndexedDB Local Storage** | Simpan draft laporan (teks, koordinat, foto blob) sebelum upload | idb / Dexie.js |
| **Add to Home Screen** | Prompt install PWA seperti native app | Web App Manifest |
| **Push Notification (Web Push)** | Notif real-time: status berubah, assign tugas, deadline SLA | VAPID + Laravel WebPush |

### 7.2 Master Data Management (Admin CRUD)
| Entitas | Field Utama | Catatan |
|---------|-------------|---------|
| **Blok / Wilayah Kebun** | `code`, `name`, `polygon` (GeoJSON), `pic_user_id`, `hectare` | Batas spasial untuk filter & layer peta |
| **Kategori Kejadian** | `name`, `icon_marker`, `color_code`, `sla_hours`, `checklist_template` | SLA untuk escalation otomatis |
| **User Management** | CRUD petugas, reset password, aktif/nonaktif, role assign | Hanya admin |

### 7.3 Map & Spasial Lanjutan
| Fitur | Deskripsi |
|-------|-----------|
| **Layer Batas Blok (GeoJSON)** | Overlay polygon blok kebun dari tabel `blocks`, warna per PIC |
| **Base Map Selector** | Toggle: OSM / Satellite (Esri) / Terrain / Custom Tile |
| **Heatmap Layer** | Visualisasi kepadatan kejadian per periode (Leaflet.heat) |
| **Radius Filter** | "Tampilkan laporan dalam radius X meter dari titik ini" |
| **Export GeoJSON / KML** | Download data spasial untuk GIS Team / QGIS |

### 7.4 Fitur Laporan Lanjutan
| Fitur | Deskripsi |
|-------|-----------|
| **Draft / Simpan Sementara** | Petugas isi separuh, lanjut besok (status `DRAFT`) |
| **Duplicate Report** | Salin laporan existing → ubah lokasi/deskripsi cepat |
| **Photo Annotation** | Tulis/tandai di foto (canvas overlay: lingkari area, tambah teks) |
| **Voice Note** | Rekam suara singkat (Web Audio API → MP3/Opus) |
| **Dynamic Checklist per Kategori** | Template: Kebakaran → "Api masih menyala?", "Jumlah hektar terbakar" |

### 7.5 Notifikasi & Komunikasi
| Channel | Trigger | Implementasi |
|---------|---------|--------------|
| **In-App (Bell Icon)** | Status berubah, assign ke saya, mention | Laravel Notifications + DB driver |
| **WhatsApp Gateway** | Laporan OPEN baru, SLA akan habis, daily digest | Fonnte / Wablas / Gateway API |
| **Email Digest** | Ringkasan harian/mingguan untuk manajemen | Laravel Mail + Queue |

### 7.6 Reporting & Export
| Fitur | Format | Jadwal |
|-------|--------|--------|
| **Laporan Harian** | PDF (print) + Excel (data) | Otomatis jam 07:00 |
| **Laporan Mingguan/Bulanan** | PDF + Excel + Chart trend | Otomatis Senin / 1 tanggal |
| **Custom Export** | Filter: blok, kategori, periode, status, petugas | On-demand via UI |
| **Dashboard Chart** | Trend per kategori, resolution time, workload per petugas | Chart.js / ApexCharts |

---

## 8. Perluasan Skema Database (ERD Tambahan)

### 8.1 Tabel `blocks` (Wilayah/Blok Kebun)
| Nama Kolom | Tipe Data | Keterangan / Constraints |
|------------|-----------|--------------------------|
| `id` | BIGINT | Primary Key, Auto Increment |
| `code` | VARCHAR(20) | Unique, kode blok (e.g. `BLOK-A12`) |
| `name` | VARCHAR(100) | Nama blok |
| `polygon` | GEOMETRY(POLYGON) | **PostGIS**: batas area (SRID 4326) |
| `hectare` | DECIMAL(8,2) | Luas areal (Ha) |
| `pic_user_id` | BIGINT | Foreign Key → `users.id` (Petugas PIC) |
| `is_active` | BOOLEAN | Default `true` |
| `created_at` / `updated_at` | TIMESTAMP | |

### 8.2 Tabel `report_drafts` (Draft Laporan Offline)
| Nama Kolom | Tipe Data | Keterangan |
|------------|-----------|------------|
| `id` | BIGINT | PK |
| `user_id` | BIGINT | FK → `users.id` |
| `category_id` | INT | FK → `categories.id` |
| `title` | VARCHAR(150) | |
| `description` | TEXT | |
| `latitude` / `longitude` | DECIMAL | |
| `block_code` | VARCHAR(50) | |
| `photo_paths` | JSON | Array path foto lokal (IndexedDB sync) |
| `voice_note_path` | VARCHAR(255) | Path file audio lokal |
| `checklist_data` | JSON | Jawaban checklist dinamis |
| `synced_at` | TIMESTAMP | Nullable, terisi setelah sync ke server |
| `created_at` / `updated_at` | TIMESTAMP | |

### 8.3 Tambahan Kolom pada `categories`
| Nama Kolom | Tipe Data | Keterangan |
|------------|-----------|------------|
| `sla_hours` | INT | SLA penanganan (jam), default 24 |
| `checklist_template` | JSON | Template checklist: `[{"label":"Api masih menyala?","type":"boolean"},{"label":"Hektar terbakar","type":"number"}]` |

### 8.4 Tambahan Kolom pada `reports`
| Nama Kolom | Tipe Data | Keterangan |
|------------|-----------|------------|
| `block_id` | BIGINT | FK → `blocks.id` (lebih relasional dari `block_code`) |
| `voice_note_url` | VARCHAR(255) | Path file suara |
| `checklist_answers` | JSON | Jawaban checklist per kategori |
| `resolved_at` | TIMESTAMP | Waktu status jadi `CLOSED` |
| `sla_deadline` | TIMESTAMP | `reported_at` + `categories.sla_hours` |
| `deleted_at` | TIMESTAMP | Soft delete |

### 8.5 Tabel `notifications`
| Nama Kolom | Tipe Data | Keterangan |
|------------|-----------|------------|
| `id` | BIGINT | PK |
| `type` | VARCHAR(100) | Class notifikasi (e.g. `ReportStatusChanged`) |
| `notifiable_type` / `notifiable_id` | Polymorphic | User/Blok yang menerima |
| `data` | JSON | Payload notifikasi |
| `read_at` | TIMESTAMP | Nullable |
| `channel` | ENUM | `database`, `whatsapp`, `email`, `push` |
| `sent_at` | TIMESTAMP | Nullable |

### 8.6 Tabel `activity_logs` (Audit Trail)
| Nama Kolom | Tipe Data | Keterangan |
|------------|-----------|------------|
| `id` | BIGINT | PK |
| `log_name` | VARCHAR(100) | e.g. `report`, `user` |
| `description` | TEXT | "Mengubah status laporan #123 dari OPEN ke ON_PROGRESS" |
| `subject_type` / `subject_id` | Polymorphic | Model terkait |
| `causer_type` / `causer_id` | Polymorphic | User yang melakukan aksi |
| `properties` | JSON | Data lama & baru (before/after) |
| `created_at` / `updated_at` | TIMESTAMP | |

---

## 9. Keamanan & Validasi Lanjutan

| Area | Implementasi |
|------|--------------|
| **File Upload Security** | Validasi MIME (finfo), max 5MB, rename hash SHA256, strip EXIF, virus scan (ClamAV via queue) |
| **GPS Spoofing Detection** | Cek `coords.accuracy` < 50m, `speed` < 30m/s, `altitude` konsisten, reject mock location (Android) |
| **Rate Limiting** | Max 20 laporan/jam per user, 100 req/menit API (Laravel Throttle) |
| **Authorization Policy** | `ReportPolicy`: `viewAny` (admin), `create` (field_officer), `update` (owner/admin), `delete` (admin) |
| **Audit Trail** | `spatie/laravel-activitylog` → tabel `activity_logs` |
| **Soft Deletes** | `Illuminate\Database\Eloquent\SoftDeletes` pada `reports`, `blocks`, `users` |
| **Data Retention** | Scheduler: hapus draft > 30 hari, anonimkan data laporan > 2 tahun (GDPR-ready) |

---

## 10. Teknis, DevOps & Testing

### 10.1 Queue & Background Jobs
| Job | Trigger | Queue |
|-----|---------|-------|
| `CompressImageJob` | Upload foto | `images` |
| `SendWhatsAppNotificationJob` | Status berubah / SLA | `notifications` |
| `GenerateDailyReportJob` | Scheduler 07:00 | `reports` |
| `SyncOfflineDraftsJob` | PWA online event | `sync` |
| `VirusScanJob` | Upload file | `security` |

**Worker:** Laravel Horizon (Redis) untuk monitoring & scaling.

### 10.2 Scheduler (Cron)
```php
// app/Console/Kernel.php
$schedule->command('reports:daily-digest')->dailyAt('07:00');
$schedule->command('reports:auto-close-stale')->dailyAt('02:00'); // > 30 hari OPEN
$schedule->command('drafts:cleanup')->dailyAt('03:00'); // > 30 hari
$schedule->command('sla:check-escalation')->hourly(); // Notif SLA akan habis
```

### 10.3 Database Optimization (PostgreSQL + PostGIS)
```sql
-- Index komposit untuk query map & filter
CREATE INDEX idx_reports_spatial ON reports USING GIST (
    ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
);
CREATE INDEX idx_reports_filter ON reports (status, category_id, reported_at DESC);
CREATE INDEX idx_reports_block_date ON reports (block_id, reported_at DESC);

-- Partial index untuk OPEN reports
CREATE INDEX idx_reports_open ON reports (category_id, reported_at) 
WHERE status = 'OPEN';
```

### 10.4 Testing Strategy
| Level | Tool | Coverage Target |
|-------|------|-----------------|
| **Unit/Feature (Backend)** | Pest PHP | > 80% |
| **E2E (Mobile Web)** | Playwright (mobile viewport) | Critical paths: login, create report offline→sync, map filter |
| **Visual Regression** | Playwright + pixelmatch | Map rendering, form states |
| **Load Test** | k6 / Artillery | 100 concurrent users, 50 reports/min |

### 10.5 CI/CD Pipeline (GitHub Actions)
```yaml
# .github/workflows/ci.yml
jobs:
  test:
    runs-on: ubuntu-latest
    services: { postgres, redis }
    steps: [checkout, setup-php, composer install, php artisan test]
  build:
    needs: test
    steps: [npm ci, npm run build, docker build -t kebun-tebu:$SHA]
  deploy:
    needs: build
    if: github.ref == 'refs/heads/main'
    runs-on: self-hosted (VPS)
    steps: [deployer deploy]
```

### 10.6 Monitoring & Observability
| Tool | Metric |
|------|--------|
| **Laravel Telescope** (local/staging) | Query, jobs, requests, exceptions |
| **Sentry** (production) | Error tracking, performance |
| **Uptime Kuma** | Health check endpoint `/up` |
| **Log Stack** | Loki + Grafana (Docker) |

---

## 11. Estimasi Biaya Tambahan (Post-MVP)

| Komponen | Estimasi | Catatan |
|----------|----------|---------|
| **WhatsApp Gateway (Fonnte)** | Rp 150.000 - 300.000 / bulan | Pay per message / monthly |
| **Push Notification (VAPID)** | Rp 0 | Self-hosted via Laravel WebPush |
| **Sentry (Team Plan)** | $26/bln (~Rp 400k) | Error tracking production |
| **PostGIS Managed (Supabase Pro)** | $25/bln (~Rp 380k) | Jika butuh > 500MB / production |
| **VPS Upgrade (4GB RAM)** | Rp 150.000 - 250.000 / bulan | Untuk Horizon + Queue worker |
| **Domain SSL (Wildcard)** | ~Rp 300.000 / tahun | *.kebuntebu.id |
| **Total Estimasi Bulanan (Post-MVP)** | **± Rp 1.000.000 - 1.500.000 / bulan** | Termasuk infra + layanan 3rd party |

---

## 12. Glosarium & Singkatan

| Istilah | Arti |
|---------|------|
| **MVP** | Minimum Viable Product |
| **PWA** | Progressive Web App |
| **SLA** | Service Level Agreement (waktu tanggap maksimum) |
| **GeoJSON** | Format data spasial berbasis JSON |
| **PostGIS** | Ekstensi PostgreSQL untuk data geometri/spasial |
| **IndexedDB** | Database NoSQL di browser (offline storage) |
| **Background Sync** | API browser untuk sync data saat online |
| **VAPID** | Voluntary Application Server Identification (Web Push auth) |
| **Horizon** | Dashboard & manajemen queue Laravel (Redis) |
| **Soft Delete** | Hapus logis (kolom `deleted_at`), data tidak benar-benar terhapus |