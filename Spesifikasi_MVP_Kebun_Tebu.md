
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



3. Estimasi Biaya Infrastruktur (Low Cost MVP)KomponenLayanan DirekomendasikanEstimasi Biaya (Tahap MVP)Hosting & ServerVPS Murah (BiznetGio / Niagahoster / IdCloudHost) atau Railway / RenderRp 50.000 - Rp 100.000 / bulanDatabaseManaged PostgreSQL (Supabase Free Tier) atau Local DB ServerRp 0 / bulanDomain Custom (.id / .com)Registrar Lokal~Rp 150.000 / tahunMap APIOpenStreetMap + Leaflet.jsRp 0 (Free Open Source)TOTAL ESTIMASI INFRASTRUKTUR± Rp 150.000 / tahun + Biaya Hosting4. Skema Tabel Database (ERD)4.1 Tabel usersMenyimpan data otentikasi dan identitas pelapor maupun admin.Nama KolomTipe DataKeterangan / ConstraintsidBIGINTPrimary Key, Auto IncrementnameVARCHAR(100)Nama lengkap penggunaemailVARCHAR(100)Unique, untuk loginpasswordVARCHAR(255)Hash passwordroleENUM'field_officer', 'admin'phone_numberVARCHAR(20)Nomor HP / WhatsAppcreated_atTIMESTAMPWaktu akun dibuat4.2 Tabel categoriesMenyimpan daftar kategori kejadian.Nama KolomTipe DataKeterangan / ConstraintsidINTPrimary Key, Auto IncrementnameVARCHAR(50)Nama kejadian (e.g. Kebakaran, Hama)icon_markerVARCHAR(100)File icon penanda peta (e.g. fire.png)color_codeVARCHAR(10)Kode warna hex (e.g. #FF0000)4.3 Tabel reportsMenyimpan detail laporan kejadian dari lapangan.Nama KolomTipe DataKeterangan / ConstraintsidBIGINTPrimary Key, Auto Incrementuser_idBIGINTForeign Key ➔ users.idcategory_idINTForeign Key ➔ categories.idtitleVARCHAR(150)Judul singkat kejadiandescriptionTEXTKeterangan/kronologi kejadianlatitudeDECIMAL(10, 8)Koordinat Lintang (misal: -7.79560000)longitudeDECIMAL(11, 8)Koordinat Bujur (misal: 110.36950000)block_codeVARCHAR(50)Kode Blok/Wilayah Kebun (e.g. BLOK-A12)photo_urlVARCHAR(255)Path/URL file foto buktistatusENUM'OPEN', 'ON_PROGRESS', 'CLOSED'admin_noteTEXTCatatan tindak lanjut adminhandled_byBIGINTForeign Key ➔ users.id (Admin penanggung jawab)reported_atTIMESTAMPWaktu kejadian dilaporkanupdated_atTIMESTAMPWaktu update terakhir5. Struktur Routing & Controller (Laravel + Inertia)Karena menggunakan Inertia.js, Routing ditangani oleh Laravel dan merender komponen Vue.js:HTTP MethodRoute URLController & MethodRender Vue Page / ActionGET/loginAuthenticatedSessionController@createPages/Auth/Login.vuePOST/loginAuthenticatedSessionController@storeAuthenticate UserGET/reports/createReportController@createPages/Reports/Create.vue (Form Input & GPS)POST/reportsReportController@storeHandle File Upload & Save DB ➔ RedirectGET/mapMapController@indexPages/Map/Index.vue (Leaflet Interactive Map)GET/reports/{id}ReportController@showPages/Reports/Show.vue (Detail Laporan)PATCH/reports/{id}/statusReportStatusController@updateUpdate Status ➔ Refresh PageGET/dashboardDashboardController@indexPages/Dashboard.vue (Ringkasan Statistik)6. QA Test Plan (Pengujian Lapangan)6.1 Matriks Pengujian GPS / Location TaggingID TestSkenario PengujianHasil yang DiharapkanGPS-01Location PermissionBrowser menampilkan pop-up izin lokasi saat form dibuka pertama kali.GPS-02Auto-detect GPSKoordinat Latitude & Longitude terisi otomatis dengan akurasi < 15 meter.GPS-03Penyesuaian Manual (Pin Adjust)Menggeser pin di peta kecil pada form otomatis memperbarui koordinat teks.GPS-04GPS HP Non-AktifTampil peringatan: "GPS Anda belum aktif. Aktifkan GPS atau pilih titik di peta."GPS-05Akses Ditolak (Denied)Tampil opsi input lokasi cadangan (pilih manual via peta).6.2 Matriks Pengujian Upload Foto LapanganID TestSkenario PengujianHasil yang DiharapkanCAM-01Tangkap Foto via Kamera HPKlik tombol kamera membuka kamera bawaan HP, foto tampil di preview.CAM-02Kompresi OtomatisFoto resolusi tinggi (8-15 MB) otomatis dikompres client-side menjadi < 1 MB sebelum diunggah.CAM-03Validasi FormatFile non-gambar (PDF/DOCX) ditolak dengan pesan error validasi.CAM-04Penanganan Sinyal LemahSaat internet lambat/putus, data teks & koordinat yang diisi tidak terhapus (retain form data).
