# 🌾 Kebun Tebu MVP - Sistem Monitoring & Pelaporan Kebun Tebu Interaktif

Sistem manajemen dan pelaporan kondisi lapangan perkebunan tebu berbasis web (PWA) interaktif dengan peta Spasial/GIS, kemampuan kerja offline (*Offline-First*), manajemen SLA, serta otomasi jadwal pelaporan harian.

---

## 🚀 Fitur Utama

- **🗺️ Interactive GIS Map Monitoring**: Peta interaktif berbasis LeafletJS dengan layer batas blok kebun (GeoJSON), *Marker Clustering*, *Base Map selector* (OSM, Esri Satellite, Terrain), serta filter per kategori/blok/status/tanggal.
- **📱 PWA Offline-First Reporting**: Form pelaporan dapat digunakan tanpa jaringan internet. Draft disimpan otomatis di *IndexedDB* dan disinkronkan ke server secara otomatis ketika jaringan kembali terhubung.
- **📍 Auto GPS & Mini-Map Pin Adjust**: Deteksi lokasi akurasi tinggi via GPS perangkat dengan opsi penyesuaian pin manual langsung di peta.
- **👥 Role-Based Access Control (RBAC)**: Pembagian hak akses antara `admin` (Manajemen) dan `field_officer` (Petugas Lapangan).
- **📋 Dynamic Checklist & SLA Management**: Checklist dinamis per kategori kejadian serta estimasi SLA *deadline* penanganan otomatis.
- **⏱️ Automated Artisan Commands**:
  - `php artisan sla:check-escalation`: Pengawasan & peringatan SLA mendekati tenggat waktu.
  - `php artisan reports:auto-close-stale`: Penutupan otomatis laporan status `OPEN` > 30 hari.
  - `php artisan reports:daily-digest`: Ringkasan laporan harian untuk manajemen.
- **📥 Export Data Spasial & Laporan**: Download langsung seluruh laporan dalam format **CSV** atau **GeoJSON**.

---

## 📋 Prasyarat Sistem

Pastikan perangkat Anda telah terinstal:
- **PHP**: `>= 8.2` (dengan ekstensi `pdo_pgsql`, `mbstring`, `openssl`, `fileinfo`)
- **Database**: PostgreSQL (dengan ekstensi `PostGIS` aktif)
- **Composer**: `>= 2.x`
- **Node.js**: `>= 18.x` & **npm**

---

## 🛠️ Langkah Instalasi & Pengaturan

### 1. Clone Repository & Install Dependency
```bash
git clone https://github.com/AmirulJusticia0507/kebun_tebu.git
cd kebun_tebu

# Install dependency PHP backend
composer install

# Install dependency JavaScript frontend
npm install
```

### 2. Konfigurasi Environment (`.env`)
Salin file `.env.example` ke `.env` dan atur konfigurasi database PostgreSQL Anda:
```bash
cp .env.example .env
```

Sesuaikan parameter database di file `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kebun_tebu
DB_USERNAME=postgres
DB_PASSWORD=root
```

Generate App Key:
```bash
php artisan key:generate
```

### 3. Migration & Seeding Database
Jalankan migrasi database beserta data awal (User Default & Kategori Default):
```bash
php artisan migrate:fresh --seed
```

---

## 🔑 Kredensial Default (Seeder)

Setelah menjalankan `db:seed`, Anda dapat menguji login dengan akun default berikut:

| Peran | Email | Password |
| :--- | :--- | :--- |
| **Admin Kebun** | `admin@kebuntebu.id` | `password` |
| **Petugas Lapangan** | `petugas@kebuntebu.id` | `password` |

---

## ⚙️ Cara Menjalankan Aplikasi (Development)

Untuk menjalankan seluruh layanan aplikasi secara lokal, buka 2 atau 3 terminal terpisah:

### 🔹 Terminal 1: Laravel Backend Server
Menjalankan server backend API Laravel:
```bash
php artisan serve
```
Aplikasi backend akan berjalan di: `http://127.0.0.1:8000` (atau via Laragon `http://kebun_tebu.test`).

---

### 🔹 Terminal 2: Vite Frontend Dev Server
Menjalankan kompilasi frontend Vue + Inertia + Tailwind CSS dengan *Hot Reload*:
```bash
npm run dev
```

---

### 🔹 Terminal 3 (Opsional): Scheduler Otomatis (SLA & Daily Digest)
Menjalankan worker pengawas tugas terjadwal (SLA escalation, auto-close stale, daily digest):
```bash
php artisan schedule:work
```

---

## 📦 Build untuk Produksi

Untuk melakukan kompilasi aset frontend dan Service Worker PWA versi produksi:
```bash
npm run build
```

---

## 📑 Perintah Artisan Khusus Kebun Tebu

| Perintah | Deskripsi |
| :--- | :--- |
| `php artisan sla:check-escalation` | Memeriksa laporan yang mendekati atau melewati SLA hours |
| `php artisan reports:auto-close-stale` | Menutup otomatis laporan `OPEN` berusia > 30 hari |
| `php artisan reports:daily-digest` | Membuat ringkasan laporan harian untuk manajemen |
