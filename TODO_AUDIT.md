# Audit Status dan TODO Kebun Tebu

Tanggal audit: 8 Oktober 2026  
Dasar audit: kode, route, konfigurasi, build produksi, dan test yang tersedia di repository.

## Ringkasan

| Fase | Status audit | Ringkasan |
|---|---|---|
| Phase 2: Frontend Vue/Inertia | Selesai | Halaman autentikasi, dashboard, peta, daftar/form/detail laporan, dan halaman admin tersedia. Build produksi berhasil. |
| Phase 3: Core Controllers | Selesai dengan catatan | Controller auth, dashboard, map, report, status report, notification, dan admin tersedia serta route terdaftar. Masih ada gap otorisasi dan konsistensi endpoint. |
| Phase 4: QA Test Plan | Belum selesai | Baru ada satu file test awal; test runner belum dapat berjalan dan beberapa test tidak sesuai implementasi. |
| Phase 4b: PWA Offline-First | Implementasi selesai, menunggu QA perangkat | Form, IndexedDB (termasuk foto), retry saat online, idempotency, cache form, manifest, dan service worker sudah tersambung. |
| Phase 5: Notifications | Implementasi selesai, menunggu konfigurasi/QA | Database notification, WhatsApp, Web Push, SLA/digest, queue retry, dan notification center sudah tersedia. |
| Phase 6: Security, Observability & Launch Prep | Sebagian | Auth, role middleware, policy, audit log, validasi upload, dan health endpoint tersedia; hardening, monitoring, CI, dan deployment belum siap. |

## Detail per fase

### [x] Phase 2 — Frontend Vue/Inertia Pages

Sudah tersedia:

- Login, register, lupa password, dan reset password.
- Dashboard statistik dan laporan terbaru.
- Peta Leaflet dengan filter serta interaksi laporan.
- Daftar, form pembuatan, dan detail laporan.
- Pengelolaan user, kategori, dan blok untuk admin.
- Layout aplikasi, cookie consent, dan privacy policy.
- Build produksi berhasil melalui `npm run build`.

Catatan lanjutan:

- [ ] Tambahkan pengujian browser untuk alur kritis dan tampilan mobile.
- [ ] Verifikasi manual seluruh state kosong, error, loading, dan aksesibilitas dasar.

### [x] Phase 3 — Core Controllers, dengan catatan

Sudah tersedia:

- Web auth dan API token Sanctum.
- Dashboard aggregation.
- Map query dan filter.
- CRUD laporan dasar, upload foto, status, CSV/GeoJSON export, dan endpoint offline sync.
- CRUD admin untuk user, kategori, dan blok.
- Notification center untuk membaca dan menandai notifikasi.
- Seluruh route berhasil dimuat oleh `php artisan route:list` (50 route).

TODO sebelum dinyatakan production-ready:

- [ ] Batasi update status hanya untuk admin. Saat ini `ReportPolicy::update()` juga mengizinkan pemilik laporan.
- [ ] Terapkan policy pada detail laporan dan export; saat ini semua user terautentikasi dapat membuka detail/export seluruh laporan.
- [ ] Selaraskan URL dokumentasi/test export dengan route aktual: `/reports/export/csv` dan `/reports/export/geojson`.
- [ ] Perkuat validasi endpoint sync (batas koordinat, field opsional, foto, idempotency/client UUID, dan transaksi database).
- [ ] Cegah laporan ganda ketika retry sync terjadi.
- [ ] Evaluasi registrasi publik yang mengizinkan pengguna memilih role `admin`.

### [ ] Phase 4 — QA Test Plan Implementation

Yang sudah ada:

- Dependency Pest dan plugin Laravel sudah tercantum di `composer.json`.
- `tests/Feature/ReportTest.php` berisi draft skenario auth, laporan, export, GPS, offline, dan SLA.

Masalah saat audit:

- `php artisan test` gagal karena `phpunit.xml.dist` tidak tersedia.
- Bootstrap Pest (`tests/Pest.php`) dan `TestCase.php` tidak tersedia.
- Factory yang dipanggil oleh test tidak tersedia di `database/factories`.
- Test tidak mengimpor model `User`, `Category`, dan `Report`.
- Test membuat laporan melalui `/reports/create`, padahal route penyimpanan adalah `POST /reports`.
- Test export memakai URL yang tidak sesuai route aktual.
- Ekspektasi status validasi `318` tidak benar untuk Laravel.
- Test filter mengirim filter sebagai argumen terpisah ke `get()`, bukan query string.
- Test offline dan SLA saat ini hanya menguji array/waktu lokal, bukan perilaku aplikasi.

TODO:

- [ ] Tambahkan konfigurasi dan bootstrap Pest yang valid.
- [ ] Tambahkan factory minimal untuk User, Category, Block, dan Report.
- [ ] Perbaiki test yang ada hingga seluruhnya benar-benar berjalan.
- [ ] Test auth: login, logout, throttling, register, reset password, dan larangan eskalasi role.
- [ ] Test authorization per role untuk dashboard, report, status, export, dan CRUD admin.
- [ ] Test validasi laporan, upload foto, SLA deadline, filter, CSV, dan GeoJSON.
- [ ] Test sync offline termasuk duplikasi, payload parsial, kegagalan batch, dan retry.
- [ ] Test scheduled commands dan pembuatan/pengiriman notifikasi.
- [ ] Tambahkan E2E mobile untuk login → buat laporan → offline → kembali online → sync → tampil di peta.
- [ ] Jalankan Pint/PHPStan/test/build di CI.

### [x] Phase 4b — PWA Offline-First (menunggu QA perangkat)

Yang sudah ada:

- `vite-plugin-pwa` dan Workbox terpasang.
- Manifest dan runtime cache untuk font serta tile OpenStreetMap dikonfigurasi.
- Helper IndexedDB menyediakan save/get/delete/clear draft.
- Endpoint web dan API untuk sync laporan tersedia.
- Build menghasilkan manifest dan service worker.
- Form menyimpan laporan dan foto Blob ke IndexedDB ketika offline/koneksi terputus.
- Draft dipisahkan berdasarkan pengguna dan otomatis disinkronkan saat online.
- Draft hanya dihapus setelah server mengonfirmasi keberhasilan.
- UUID client dan unique constraint mencegah duplikasi ketika request diulang.
- UI menampilkan status koneksi, jumlah draft, status sync, dan retry manual.
- Form laporan memakai cache `NetworkFirst`; cache halaman dibersihkan saat logout.
- Registrasi service worker dan ikon manifest sudah memakai aset hasil build yang valid.

Validasi yang masih diperlukan:

- [ ] Uji offline reload, cache form, GPS, foto, kehilangan koneksi saat submit, dan reconnect.
- [ ] Verifikasi installability serta perilaku service worker di perangkat Android target.

### [x] Phase 5 — Notifications (menunggu konfigurasi provider dan QA perangkat)

Yang sudah ada:

- Tabel/model notification dan endpoint notification center.
- Scheduler untuk SLA check, auto-close, dan daily digest.
- Deadline SLA dihitung ketika laporan dibuat.
- Command SLA dan digest sudah dapat menghitung data dan menulis log.
- Notification database dikirim untuk laporan baru, perubahan status, SLA, dan daily digest.
- Notification center menyediakan daftar, buka detail, serta tandai satu/semua dibaca.
- Integrasi WhatsApp Fonnte memiliki timeout, retry, logging, dan pencatatan delivery.
- Web Push memiliki subscription per pengguna, VAPID, delivery, dan pembersihan subscription kedaluwarsa.
- Pengiriman eksternal berjalan melalui queue dengan retry/backoff dan failed jobs.
- Warning SLA dan daily digest dilindungi dari pengiriman berulang.

Validasi yang masih diperlukan:

- [ ] Isi kredensial Fonnte dan uji nomor WhatsApp nyata.
- [ ] Jalankan `php artisan webpush:generate-keys`, isi VAPID keys, lalu uji browser/perangkat target.
- [ ] Jalankan queue worker dan scheduler secara persisten di production.
- [ ] Test command scheduler dan kegagalan provider.

### [~] Phase 6 — Security, Observability & Launch Prep

Yang sudah ada:

- Session auth, Sanctum, CSRF, password hashing, role middleware, dan policy dasar.
- Validasi tipe/ukuran foto sampai 5 MB.
- Activity log pada perubahan Report.
- Laravel logging dan health endpoint `/up`.
- Soft delete dan index database pada entitas utama.

TODO keamanan prioritas tinggi:

- [ ] Tutup self-registration role admin atau batasi registrasi hanya untuk field officer.
- [ ] Tambahkan rate limit untuk login, token API, sync, export, dan endpoint sensitif.
- [ ] Audit seluruh authorization dan konsistensikan pengecekan `role` dengan Spatie roles.
- [ ] Batasi export berdasarkan role dan scope data.
- [ ] Harden upload: nama acak, content inspection, image re-encode/EXIF stripping, dan malware scan sesuai kebutuhan risiko.
- [ ] Tinjau pengecualian CSRF yang tidak digunakan (`stripe/*`) dan cookie exception placeholder.
- [ ] Tambahkan security headers, HTTPS/cookie production settings, CORS review, dan secrets management.

TODO observability dan launch:

- [ ] Tambahkan structured logging dengan request/user/report correlation ID.
- [ ] Tambahkan exception/error monitoring dan alerting.
- [ ] Monitor queue, scheduler, failed jobs, SLA command, storage, database, dan disk.
- [ ] Buat CI untuk install, lint/static analysis, test, dan build.
- [ ] Siapkan konfigurasi production: `APP_DEBUG=false`, queue worker, scheduler, cache, mail, storage link, dan backup.
- [ ] Tambahkan runbook deploy, rollback, restore backup, incident response, dan smoke test.
- [ ] Lakukan migration rehearsal dan uji restore backup sebelum launch.
- [ ] Jalankan security review, load test, dan UAT lapangan pada perangkat target.

## Urutan pengerjaan yang direkomendasikan

1. Perbaiki celah role admin dan authorization Phase 3/6.
2. Hidupkan fondasi Pest lalu kunci perilaku yang sudah ada dengan feature tests.
3. Selesaikan alur offline end-to-end dengan idempotent sync.
4. Bangun notification pipeline database, lalu WhatsApp dan Web Push.
5. Tambahkan CI, monitoring, backup/restore, UAT, dan checklist launch.

## Hasil verifikasi audit

- `php artisan route:list`: berhasil, 50 route terdaftar.
- `npm run build`: berhasil; 863 module ditransformasi dan aset PWA dihasilkan.
- `php artisan test`: gagal sebelum test berjalan karena `phpunit.xml.dist` tidak ditemukan.

