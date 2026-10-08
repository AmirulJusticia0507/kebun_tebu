# Audit Status dan TODO Kebun Tebu

Tanggal audit: 8 Oktober 2026  
Dasar audit: kode, route, konfigurasi, build produksi, dan test yang tersedia di repository.

## Ringkasan

| Fase | Status audit | Ringkasan |
|---|---|---|
| Phase 2: Frontend Vue/Inertia | Selesai | Halaman autentikasi, dashboard, peta, daftar/form/detail laporan, dan halaman admin tersedia. Build produksi berhasil. |
| Phase 3: Core Controllers | Selesai | Controller auth, dashboard, map, report, status, notification, dan admin tersedia; validasi, otorisasi, sync idempotent, serta perlindungan lifecycle data sudah ditutup. |
| Phase 4: QA Test Plan | Selesai | Bootstrap Pest, factory, serta test auth, authorization, validasi, sync offline, scheduler, dan E2E offline-first (106 tests); Pint, PHPStan, dan CI lulus. |
| Phase 4b: PWA Offline-First | Implementasi selesai, menunggu QA perangkat | Form, IndexedDB (termasuk foto), retry saat online, idempotency, cache form, manifest, dan service worker sudah tersambung. |
| Phase 5: Notifications | Implementasi selesai, menunggu konfigurasi/QA | Database notification, WhatsApp, Web Push, SLA/digest, queue retry, dan notification center sudah tersedia. |
| Phase 6: Security, Observability & Launch Prep | Selesai (menunggu aksi pra-launch) | Hardening akses/upload/header, request tracing, dependency audit, health/error monitoring dengan alert multi-kanal, sinkronisasi role↔Spatie, CI, dan runbook deployment tersedia; migration rehearsal, security review, load test, dan UAT lapangan tinggal dijalankan. |

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

### [x] Phase 3 — Core Controllers

Sudah tersedia:

- Web auth dan API token Sanctum.
- Dashboard aggregation.
- Map query dan filter.
- CRUD laporan dasar, upload foto, status, CSV/GeoJSON export, dan endpoint offline sync.
- CRUD admin untuk user, kategori, dan blok.
- Notification center untuk membaca dan menandai notifikasi.
- Seluruh route berhasil dimuat oleh `php artisan route:list`.
- Detail laporan dibatasi untuk admin/pemilik; status dan export hanya untuk admin.
- Offline batch sync tervalidasi, transactional, idempotent, menghitung duplikat, dan mengirim notifikasi laporan baru.
- Filter map/report/export tervalidasi dan export CSV dilindungi dari formula injection.
- Status laporan menjaga konsistensi `resolved_at` dan tidak mengirim notifikasi palsu untuk perubahan catatan saja.
- Registrasi publik tidak dapat memilih admin.
- Penghapusan user memakai soft delete sehingga laporan tidak ikut terhapus; admin terakhir juga dilindungi.
- Kategori dengan laporan aktif maupun terarsip tidak dapat dihapus.
- Endpoint user form yang merender halaman Vue tidak tersedia sudah dihapus; CRUD memakai modal index.

Validasi lanjutan dilakukan pada Phase 4 melalui feature tests.

### [x] Phase 4 — QA Test Plan Implementation

Sudah tersedia:

- Bootstrap Pest: `phpunit.xml`, `tests/Pest.php`, `TestCase.php`, dan `CreatesApplication.php` dengan SQLite in-memory; helper role `makeAdmin()`/`makeFieldOfficer()`.
- Factory `UserFactory`, `CategoryFactory`, `BlockFactory`, dan `ReportFactory`.
- Test auth: login, logout, throttling, register, reset password (termasuk migration `password_reset_tokens`), dan larangan eskalasi role.
- Test authorization per role untuk dashboard, report, status, export, dan CRUD admin.
- Test validasi laporan, upload foto, SLA deadline, filter, CSV/GeoJSON, serta proteksi formula injection pada export.
- Test sync offline: duplikasi `client_uuid`, payload parsial, kegagalan batch, retry, dan isolasi sesi antar pengguna.
- Test scheduled commands serta pembuatan notifikasi (SLA warning, daily digest, auto-close).
- Test E2E offline-first (HTTP-level): login → buat laporan → offline → kembali online → sync → tampil di peta dengan filter.
- CI GitHub Actions dua job: backend (composer install, composer audit, Pint, PHPStan larastan level 5, Pest) dan frontend (npm ci, ESLint, Prettier check, build PWA).

Hasil:

- `php artisan test`: 106 tests lulus (383 assertions).
- `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` (level 5), ESLint, Prettier, dan `npm run build`: lulus.

Catatan lanjutan:

- [ ] Pengujian browser/perangkat nyata untuk alur kritis dan mode offline PWA.

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
- Registrasi publik selalu membuat field officer; admin hanya dapat dibuat/dikelola admin.
- Detail laporan dibatasi untuk admin/pemilik dan export/status dibatasi untuk admin.
- Rate limit tersedia pada login, registrasi, reset password, token, laporan, sync, dan export.
- Upload gambar dibatasi dimensinya, di-decode ulang, dinormalisasi orientasinya, dan disimpan sebagai WebP tanpa EXIF.
- Security headers dan secure-cookie production guidance tersedia.
- Setiap response/log request memiliki correlation ID.
- Dependency production bebas advisory pada audit terakhir.
- Queue monitoring, failed-job pruning, dan activity-log cleanup terjadwal.
- Runbook deploy, backup/restore, rollback, smoke check, dan incident response tersedia.
- `monitor:health` memeriksa database, storage, disk, heartbeat scheduler, failed jobs, dan backlog queue; `monitor:errors` memindai log ERROR/CRITICAL dengan threshold dan cooldown.
- Endpoint `GET /healthz` (publik, 200/503) dan `GET /dashboard/health` (detail, khusus admin) untuk monitoring eksternal.
- Alert monitoring dikirim bersamaan ke notification center, email, Web Push, dan webhook generik `MONITORING_WEBHOOK_URL` (queue dengan retry).
- Kolom `users.role` dicerminkan ke Spatie roles pada save/assign; command `roles:sync` mendeteksi dan memperbaiki drift.
- CI GitHub Actions: `composer install`, `composer audit`, Pint, PHPStan (larastan), Pest, ESLint, Prettier, dan build produksi PWA.

TODO keamanan prioritas tinggi:

- [x] Tutup self-registration role admin atau batasi registrasi hanya untuk field officer.
- [x] Tambahkan rate limit untuk login, token API, sync, export, dan endpoint sensitif.
- [x] Audit authorization detail/status/export laporan.
- [x] Batasi export berdasarkan role.
- [x] Harden upload dengan validasi, image re-encode, nama acak, dan EXIF stripping.
- [x] Hapus pengecualian CSRF dan cookie placeholder.
- [x] Tambahkan security headers serta panduan HTTPS/cookie/secrets production.
- [x] Pertimbangkan malware scan jika profil risiko deployment membutuhkannya (keputusan dan pemicu aktivasi terdokumentasi di DEPLOYMENT.md).
- [x] Konsistensikan seluruh penggunaan kolom `role` dengan Spatie roles dalam refactor terpisah (hook model, override `assignRole`/`syncRoles`, dan `roles:sync`).

TODO observability dan launch:

- [x] Tambahkan request/user correlation ID pada logging dan response.
- [x] Tambahkan exception/error monitoring dan alerting (`monitor:errors` + alert multi-kanal).
- [x] Tambahkan queue monitoring serta pruning failed jobs/audit log.
- [x] Tambahkan monitoring eksternal untuk scheduler, storage, database, disk, dan error alerting (`/healthz`, `monitor:health`, heartbeat scheduler).
- [x] Buat CI untuk install, lint/static analysis, test, dan build.
- [x] Dokumentasikan konfigurasi production, queue worker, scheduler, storage, dan backup.
- [x] Tambahkan runbook deploy, rollback, restore backup, incident response, dan smoke test.
- [ ] Lakukan migration rehearsal dan uji restore backup sebelum launch (checklist langkah demi langkah tersedia di DEPLOYMENT.md).
- [ ] Jalankan security review, load test, dan UAT lapangan pada perangkat target (checklist tersedia di DEPLOYMENT.md).

## Urutan pengerjaan yang direkomendasikan

1. Perbaiki celah role admin dan authorization Phase 3/6.
2. Hidupkan fondasi Pest lalu kunci perilaku yang sudah ada dengan feature tests.
3. Selesaikan alur offline end-to-end dengan idempotent sync.
4. Bangun notification pipeline database, lalu WhatsApp dan Web Push.
5. Tambahkan CI, monitoring, backup/restore, UAT, dan checklist launch.

## Hasil verifikasi audit

- `php artisan route:list`: berhasil, 50 route terdaftar.
- `npm run build`: berhasil; manifest dan service worker PWA dihasilkan.
- `php artisan test`: lulus — 106 tests, 383 assertions (SQLite in-memory).
- `vendor/bin/pint --test` dan `vendor/bin/phpstan analyse` (level 5): lulus tanpa error.
- ESLint dan Prettier check: lulus.
- CI GitHub Actions (`.github/workflows/ci.yml`) menjalankan audit, lint, static analysis, test, dan build pada dua job backend/frontend.

