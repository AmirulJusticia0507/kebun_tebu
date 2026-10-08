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
