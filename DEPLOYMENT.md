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

## Environment variables untuk Vercel

Vercel tidak membaca `.env`. Seluruh variabel diisi di
**Project → Settings → Environment Variables** per environment
(Production/Preview/Development). Daftar diambil dari `.env.example` dan
konfigurasi `config/*`.

### Wajib (aplikasi)

| Variabel | Nilai | Keterangan |
|---|---|---|
| `APP_NAME` | `Kebun Tebu` | |
| `APP_ENV` | `production` | |
| `APP_KEY` | hasil `php artisan key:generate --show` | satu nilai, jangan digenerate ulang sembarangan |
| `APP_DEBUG` | `false` | jangan `true` di production |
| `APP_URL` | `https://<domain>` | domain utama/Vercel; dipakai untuk generate URL |
| `LOG_CHANNEL` | `stack` | |
| `LOG_LEVEL` | `warning` | |
| `SESSION_DRIVER` | `cookie` | `file` tidak persisten dan tidak ada tabel `sessions`; `cookie` aman untuk serverless |
| `SESSION_SECURE_COOKIE` | `true` | wajib di HTTPS |
| `SESSION_SAME_SITE` | `lax` | |
| `SESSION_LIFETIME` | `120` | satuan menit |
| `CACHE_STORE` | `array` | `file` hilang tiap cold start; `array` cukup untuk cache per-request |
| `QUEUE_CONNECTION` | `sync` | Vercel tidak punya queue worker; `sync` mengeksekusi job inline |
| `HASH_DRIVER` | `bcrypt` | |

### Database (wajib — pakai managed PostgreSQL)

Vercel tidak menyediakan database. Gunakan Neon/Supabase/Render/Railway, lalu isi
salah satu cara:

**Cara A — variabel terpisah:**

| Variabel | Nilai |
|---|---|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | host provider |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | nama database |
| `DB_USERNAME` | |
| `DB_PASSWORD` | |

**Cara B — satu URL** (`config/database.php` mendukung `DATABASE_URL`):

```dotenv
DATABASE_URL=postgres://user:password@host:5432/dbname?sslmode=require
```

Migration **tidak dijalankan otomatis** oleh Vercel; jalankan manual
`php artisan migrate --force` dari lokal/CI sebelum atau sesudah deploy.

### Mail (wajib untuk reset password dan alert email)

| Variabel | Nilai |
|---|---|
| `MAIL_MAILER` | `smtp` (bukan `log`) |
| `MAIL_HOST` | host SMTP provider (Resend/Mailgun/Postmark/dll) |
| `MAIL_PORT` | port provider |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | kredensial SMTP |
| `MAIL_ENCRYPTION` | `tls` |
| `MAIL_FROM_ADDRESS` | alamat pengirim terverifikasi |
| `MAIL_FROM_NAME` | `Kebun Tebu` |

### Upload foto (disarankan — storage Vercel bersifat read-only dan sementara)

Tanpa object storage, file di `storage/app` dan `/tmp` hilang setiap cold
start/deployment sehingga foto laporan ikut hilang. Isi variabel berikut dan
set `FILESYSTEM_DISK=s3`:

| Variabel | Nilai |
|---|---|
| `FILESYSTEM_DISK` | `s3` |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | kredensial S3-compatible (S3/R2/MinIO) |
| `AWS_DEFAULT_REGION` | mis. `us-east-1` (atau `auto` untuk R2) |
| `AWS_BUCKET` | nama bucket |
| `AWS_ENDPOINT` | opsional, untuk R2/MinIO |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` untuk R2/MinIO |
| `AWS_URL` | opsional, public URL/CDN bucket |

Jika tidak diisi, upload tetap berjalan tetapi file hanya hidup sementara di
`/tmp`.

### Fitur opsional

**WhatsApp (Fonnte):**

| Variabel | Nilai |
|---|---|
| `FONNTE_TOKEN` | token API Fonnte |
| `FONNTE_URL` | default `https://api.fonnte.com/send` |

**Web Push:**

| Variabel | Nilai |
|---|---|
| `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` | hasil `php artisan webpush:generate-keys` |
| `VAPID_SUBJECT` | `mailto:admin@domain` |

**Monitoring & alerting** (semua opsional; default ada di `config/monitoring.php`):

| Variabel | Default | Keterangan |
|---|---|---|
| `MONITORING_WEBHOOK_URL` | kosong | webhook JSON generik (Slack/Discord/ntfy) |
| `MONITORING_ALERT_MAIL` | kosong | salinan email alert |
| `MONITORING_DISK_MIN_MB` | `512` | ambang ruang disk |
| `MONITORING_STORAGE_MIN_MB` | `512` | ambang storage |
| `MONITORING_SCHEDULER_MAX_AGE` | `5` | menit, umur heartbeat scheduler |
| `MONITORING_MAX_FAILED_JOBS` | `10` | |
| `MONITORING_MAX_QUEUE_BACKLOG` | `500` | |
| `MONITORING_ERROR_THRESHOLD` | `5` | jumlah ERROR/CRITICAL per jam |
| `MONITORING_ALERT_COOLDOWN` | `30` | menit antar alert |

**Sanctum SPA (bila memakai domain custom untuk frontend):**

```dotenv
SANCTUM_STATEFUL_DOMAINS=<domain-tanpa-https>,vercel.app
```

**Vite (dibaca saat build `npm run build` di Vercel):**

```dotenv
VITE_APP_NAME=Kebun Tebu
```

### Batasan Vercel yang perlu diketahui

1. **Storage read-only dan sementara** — `storage/app` serta `/tmp` hilang pada
   cold start; karena itu `SESSION_DRIVER=cookie`, `CACHE_STORE=array`, dan
   `FILESYSTEM_DISK=s3` (foto) dianjurkan.
2. **Tidak ada proses persisten** — queue worker dan `schedule:run` tidak
   berjalan. `QUEUE_CONNECTION=sync` menyelesaikan job inline; untuk scheduler
   terjadwal (SLA, auto-close, digest, `monitor:*`) siapkan Vercel Cron Jobs
   (`crons` di `vercel.json`) atau jalankan dari server eksternal.
3. **Migration manual** — jalankan `php artisan migrate --force` dari luar
   Vercel; jangan menjalankan `migrate:fresh` di production.
4. **Environment Variables wajib tersedia di semua environment** yang aktif
   (Preview kadang dipakai untuk testing) agar deploy preview tidak 500.

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
