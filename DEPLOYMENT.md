# Deployment dan Operasional

## Prasyarat

- PHP 8.3+ dengan `pdo_pgsql`, `pgsql`, `openssl`, `mbstring`, dan `fileinfo`.
- PostgreSQL, Node.js 20+, Composer, dan HTTPS.
- Process manager untuk queue worker serta cron untuk Laravel scheduler.

## Konfigurasi production

Salin `.env.example`, lalu pastikan nilai berikut tidak memakai default development:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-aplikasi
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
```

Isi database, mail, `FONNTE_TOKEN`, dan VAPID keys. Buat VAPID keys sekali:

```bash
php artisan webpush:generate-keys
```

Konfigurasikan juga kanal alert monitoring:

```dotenv
MONITORING_WEBHOOK_URL=https://hooks.slack.com/...   # opsional, webhook generik JSON
MONITORING_ALERT_MAIL=ops@domain                     # opsional, salinan email alert
```

Simpan secrets di secret manager/konfigurasi server, bukan di repository.

## Deploy

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan queue:restart
```

Jalankan queue worker menggunakan process manager:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Jalankan scheduler setiap menit melalui cron:

```cron
* * * * * cd /path/kebun_tebu && php artisan schedule:run >> /dev/null 2>&1
```

## Monitoring dan alerting

Tiga komponen tersedia:

1. **Health check sistem** — `php artisan monitor:health` memeriksa koneksi database,
   keterbacaan/storage, ruang kosong disk, heartbeat scheduler, jumlah failed jobs, dan
   backlog queue. Tambahkan `--alert` untuk mengirim peringatan, `--json` untuk output mesin.
   Exit code `1` ketika ada check berstatus gagal, sehingga bisa dipakai monitoring eksternal.
2. **Error log monitoring** — `php artisan monitor:errors --hours=1 --alert` memindai
   `storage/logs/*.log` untuk entri `ERROR`/`CRITICAL`/`EMERGENCY`, menghitungnya terhadap
   ambang `MONITORING_ERROR_THRESHOLD`, lalu mengirim alert beserta sampel pesan.
3. **Heartbeat scheduler** — setiap `schedule:run` menulis
   `storage/framework/schedule-heartbeat.json`. Jika heartbeat basi, health check gagal
   sehingga cron yang mati langsung terdeteksi.

Scheduler aplikasi sudah menjalankan keduanya setiap jam (`monitor:health --alert` dan
`monitor:errors --alert --hours=1`) berikut penulisan heartbeat tiap menit.

Endpoint:

- `GET /healthz` — publik, hanya status: `200` sehat, `503` bila ada check gagal.
  Arahkan Uptime Kuma/Pingdom/status page ke endpoint ini.
- `GET /dashboard/health` — detail seluruh check, khusus admin (role `admin`).

Kanal alert (dikirim bersamaan, dengan cooldown agar tidak spam):

- Notification center (database) untuk seluruh admin.
- Email Laravel (`MAIL_*`) untuk seluruh admin dan `MONITORING_ALERT_MAIL`.
- Web Push ke perangkat yang berlangganan.
- POST JSON ke `MONITORING_WEBHOOK_URL` (Slack/Telegram/Discord/ntfy/generic) melalui
  queue dengan retry/backoff.

Struktur payload webhook:

```json
{
  "app": "Kebun Tebu",
  "environment": "production",
  "host": "app-server-01",
  "type": "system.health",
  "severity": "critical",
  "title": "Health check sistem gagal",
  "message": "[fail] scheduler: Scheduler terakhir berjalan 42 menit lalu",
  "context": {},
  "time": "2026-10-08T10:00:00+07:00"
}
```

Cadangan di sisi server (bila tidak memakai webhook): gunakan exit code command.

```cron
*/5 * * * * cd /path/kebun_tebu && php artisan monitor:health >> /dev/null 2>&1
```

Verifikasi konsistensi role sistematis: `php artisan roles:sync --dry-run` menampilkan
drift antara kolom `users.role` dan Spatie roles (exit code `1` bila ada), tanpa
`--dry-run` untuk memperbaikinya.

Untuk pengembangan lokal, jalankan `php artisan schedule:work` agar heartbeat scheduler
tersedia dan `monitor:health` tidak gagal pada check scheduler.

## Backup dan restore

Sebelum migration, buat backup PostgreSQL dan salinan storage upload:

```bash
pg_dump --format=custom --file=kebun_tebu.dump kebun_tebu
```

Uji restore secara berkala pada database non-production:

```bash
createdb kebun_tebu_restore_test
pg_restore --dbname=kebun_tebu_restore_test kebun_tebu.dump
```

### Rehearsal migration dan uji restore (wajib sebelum launch)

Jalankan sekali penuh di staging/lingkungan sejenis production, lalu catat hasilnya:

1. Ambil backup terbaru: `pg_dump --format=custom --file=pre-launch.dump kebun_tebu`.
2. Buat database bayangan: `createdb kebun_tebu_rehearsal`.
3. Restore dump: `pg_restore --dbname=kebun_tebu_rehearsal pre-launch.dump`.
4. Salin `.env` production ke `.env.rehearsal` dengan `DB_DATABASE=kebun_tebu_rehearsal`,
   lalu jalankan `php artisan migrate --force` dan `php artisan migrate:status`.
5. Bandingkan jumlah baris entitas utama (users, reports, categories, blocks,
   notifications, push_subscriptions) antara asli dan hasil restore.
6. Salin isi `storage/app/public` dan verifikasi beberapa foto laporan dapat diakses
   melalui `GET /storage/...`.
7. Jalankan smoke check di bawah pada lingkungan rehearsal.
8. Uji rollback: `php artisan migrate:rollback --step=1` pada rehearsal, lalu
   `php artisan migrate --force` kembali.
9. Hapus database bayangan setelah hasil dicatat.

Simpan tanggal rehearsal, versi schema (`migrate:status`), dan waktu tempuh restore di
checklist launch. Ulangi minimal setiap kali ada migration baru atau perubahan versi PostgreSQL.

## Smoke check

- `GET /up` mengembalikan HTTP 200.
- Login admin dan field officer berhasil.
- Field officer tidak dapat membuka dashboard/export/admin route.
- Buat laporan online dan offline, lalu pastikan tampil di peta.
- Ubah status laporan dan pastikan notification center menerima pesan.
- Pastikan `php artisan queue:monitor default:100` tidak memberi peringatan.
- Periksa `php artisan queue:failed` dan log aplikasi.

## Rollback

1. Aktifkan maintenance mode: `php artisan down --retry=60`.
2. Hentikan worker dan kembalikan release aplikasi sebelumnya.
3. Restore database hanya jika migration tidak backward-compatible dan backup sudah diverifikasi.
4. Jalankan `php artisan optimize:clear`, lalu `php artisan optimize`.
5. Mulai kembali worker dan jalankan `php artisan up`.
6. Ulangi smoke check.

Jangan menjalankan `migrate:fresh` pada production.

## Respons insiden

1. Catat waktu, request ID, user ID, dan report ID yang terdampak.
2. Periksa application log, failed jobs, status scheduler, database, disk, dan provider eksternal.
3. Nonaktifkan channel eksternal bermasalah dengan mengosongkan kredensialnya jika diperlukan; notifikasi database tetap berjalan.
4. Retry job aman melalui `php artisan queue:retry <id>` setelah penyebab diperbaiki.
5. Dokumentasikan akar masalah dan tindakan pencegahan setelah layanan pulih.

## Checklist sebelum launch

### CI dan mutu kode

- [ ] GitHub Actions (`.github/workflows/ci.yml`) hijau untuk kedua job:
      **backend** (`composer install`, `composer audit --locked`, `pint --test`,
      `phpstan analyse`, `php artisan test`) dan **frontend** (`npm ci`, ESLint,
      Prettier check, `npm run build`).
- [ ] Jalankan `npm audit --audit-level=high` dan tinjau hasilnya sebelum rilis.
- [ ] Quality checks lokal sebelum push:

      ```bash
      vendor/bin/pint --test
      vendor/bin/phpstan analyse --no-progress
      php artisan test
      npx eslint . --ext vue,js,jsx,cjs,mjs
      npx prettier --check "resources/**/*.{vue,js,css,html}"
      npm run build
      ```

### Security review

- [ ] Uji ulang: field officer tidak dapat membuka route admin, export, dan status.
- [ ] Uji registrasi publik selalu menjadi field officer; akun admin hanya dibuat admin.
- [ ] Uji rate limit login/registrasi/reset/sync/export (permintaan berlebih ditolak).
- [ ] Verifikasi upload: tipe/ukuran/dimensi ditolak di luar batas, foto keluar sebagai
      WebP tanpa EXIF, nama file acak, dan path tidak dapat ditebak.
- [ ] Verifikasi security headers, HTTPS, `SESSION_SECURE_COOKIE=true`, serta
      `APP_DEBUG=false` di production.
- [ ] Pastikan tidak ada secrets di repository (`git log -p` lalu cari password/secret).
- [ ] `composer audit --locked` dan `npm audit` tanpa advisory kritis.

### Keputusan malware scan

**Keputusan: tidak wajib untuk launch, wajib diaktifkan kembali jika salah satu kondisi terpenuhi.**

Profil risiko saat ini: aplikasi internal lapangan, seluruh upload hanya dari pengguna
terautentikasi, gambar di-decode ulang dan disimpan ulang sebagai WebP (EXIF dan konten
eksternal tidak dibawa), dependency diaudit lewat `composer audit`/`npm audit`, dan tidak
menerima konten dari pihak ketiga. Karena itu on-access malware scan (ClamAV/Defender)
belum ditambahkan agar tidak menambah latensi upload.

Aktifkan scan jika salah satu berikut terjadi:

- [ ] Hosting/berbagi storage dengan tenant lain atau filesystem dipindai provider.
- [ ] Ada integrasi pihak ketiga yang mengunggah file ke storage yang sama.
- [ ] Kebijakan keamanan organisasi mensyaratkan scanning artefak rilis.

Jika diaktifkan: scan direktori `public/build`, `vendor`, dan `storage/app/public`
sebelum deploy (`clamscan -r --infected`), dan/atau pasang on-access scanning di server.

### Load test dan UAT

- [ ] Load test skenario kritis (login, buat laporan + upload foto, peta, export CSV)
      dengan target minimal 100 pengguna bersamaan; catat p95 latency dan error rate.
- [ ] Uji beban sync offline batch (50 draft per perangkat) secara bersamaan.
- [ ] UAT lapangan pada perangkat Android target: instalasi PWA, mode offline
      (reload, form, GPS, foto), reconnect/sync, notifikasi, dan tampilan peta.
- [ ] Uji dengan sinyal lemah/terputus (airplane mode) dan setelah kembali online.
- [ ] Rehearsal migration dan restore backup sesuai bagian sebelumnya sudah dicatat.

### Konfigurasi production yang diverifikasi

- [ ] Queue worker persisten (process manager) dan cron `schedule:run` aktif;
      heartbeat `storage/framework/schedule-heartbeat.json` selalu baru.
- [ ] `GET /healthz` dipantau uptime monitor eksternal.
- [ ] `MONITORING_WEBHOOK_URL`/email teruji mengirim alert nyata.
- [ ] Backup otomatis database dan `storage/app/public` aktif serta pernah di-restore.
