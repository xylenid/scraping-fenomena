<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Penjadwalan Crawl Otomatis (GitHub Actions)

Crawl bulanan dijalankan otomatis lewat GitHub Actions (gratis) yang memanggil endpoint internal Laravel:

- **Workflow**: [.github/workflows/monthly-crawl.yml](.github/workflows/monthly-crawl.yml) — berjalan setiap hari pukul **19:00 UTC (= 02:00 WIB)** plus `workflow_dispatch` untuk trigger manual dari tab Actions.
- **Endpoint**: `POST /api/internal/run-schedule` — dilindungi header `Authorization: Bearer $CRON_TOKEN` (dicek dengan `hash_equals`).
- **Logika jadwal**: endpoint menentukan window dari tanggal WIB — **tgl 1–2 → window main** (periode bulan lalu), **tgl 10–11 → window addon** (artikel 1–10 bulan berjalan tentang kejadian bulan lalu), selain itu skip. Grace 1 hari menoleransi keterlambatan trigger GitHub.
- **Idempotency**: skip jika window yang sama untuk periode yang sama sudah berjalan/kurang dari 48 jam lalu; status `partial`/`failed` tetap boleh retry. Set `force=true` untuk memaksa run ulang.
- **Eksekusi**: `crawl:monthly` dijalankan setelah response HTTP terkirim (`app()->terminating`), jadi panggilan curl tidak timeout.

### Setup

1. Push repo ini ke GitHub.
2. Buka **Settings → Secrets and variables → Actions**, tambahkan:
   - `API_BASE_URL` — URL deploy Laravel (mis. `https://scraping-fenomena.onrender.com`, tanpa slash di akhir).
   - `CRON_TOKEN` — nilai yang sama dengan env `CRON_TOKEN` di server Laravel (contoh di [.env.example](.env.example)).
3. Pastikan env `CRON_TOKEN` juga di-set di host Laravel (Render/Railway dashboard).
4. Test manual: tab **Actions → Monthly Crawl Trigger → Run workflow** (opsional isi window/period).

> Catatan: scheduler internal Laravel di `bootstrap/app.php` (tgl 1 & 10, 02:00 WIB) tetap aktif — jalankan `php artisan schedule:run` via cron di host jika host mendukung. GitHub Actions hanya trigger pengganti yang tidak bergantung pada cron host berbayar.

## Deployment (Vercel + Render + Supabase)

Arsitektur hosting gratis:

| Komponen | Layanan | Konfigurasi |
|---|---|---|
| Frontend Vue SPA | **Vercel** | [vercel.json](vercel.json) — build `npm run build:spa`, output `dist`, rewrite SPA |
| API Laravel | **Render** | [render.yaml](render.yaml) — Blueprint (otomatis terbaca saat import repo) |
| Database | **Supabase** | PostgreSQL managed (dipakai lokal & produksi) |
| Cron bulanan | **GitHub Actions** | [.github/workflows/monthly-crawl.yml](.github/workflows/monthly-crawl.yml) |

### 1. Push ke GitHub

```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/<username>/<repo>.git
git push -u origin main
```

### 2. Render — API Laravel

1. Dashboard Render → **New → Blueprint** → connect repo GitHub → `render.yaml` terdeteksi otomatis.
2. Isi env vars bertanda `sync: false`:
   - `APP_KEY` — salin dari `.env` lokal (`APP_KEY=base64:...`), atau generate: `php artisan key:generate --show`
   - `APP_URL` — URL Render, mis. `https://scraping-fenomena.onrender.com`
   - `DB_HOST` — **host pooler IPv4 Supabase** (buka Supabase Dashboard → Connect → pooling mode, salin host). Host direct (`db.<ref>.supabase.co`) hanya AAAA/IPv6 — jika host Render tidak punya IPv6, koneksi gagal.
   - `DB_USERNAME` — direct: `postgres`; **session pooler: `postgres.<ref>`**
   - `DB_PASSWORD` — password database Supabase
   - `CRON_TOKEN` — sama dengan nilai di `.env` (lihat [.env.example](.env.example))
3. Deploy. Start command menjalankan `php artisan migrate --force` lalu serve di `$PORT`; health check di `/up`.

> **Playwright fallback**: browser (Chromium) tidak terpasang di Render free tier, jadi jalur RSS + HTML scraper tetap berjalan, tapi metode browser-based butuh resource lebih besar atau dijalankan lokal (`php artisan crawl:monthly`).

### 3. Vercel — Frontend SPA

1. Vercel dashboard → **Add New → Project** → import repo yang sama.
2. Framework **Vite** terdeteksi otomatis; `vercel.json` sudah menyetel build command `npm run build:spa`, output `dist`, dan rewrite SPA (route fallback).
3. Tambah env variable (build-time): `VITE_API_BASE_URL` = URL Render (mis. `https://scraping-fenomena.onrender.com`). Tanpa ini frontend memanggil `/api/v1` same-origin (Vercel tidak punya API-nya).
4. Deploy. CORS Laravel sudah terbuka default untuk `api/*`.

### 4. GitHub Actions — Secret

Di **Settings → Secrets and variables → Actions**:

- `API_BASE_URL` = URL Render (tanpa trailing slash)
- `CRON_TOKEN` = token yang sama dengan di Render

### 5. Verifikasi end-to-end

1. Buka URL Vercel → dashboard tampil, **Sumber Berita** terisi (dari seed via API).
2. Tab **Actions → Monthly Crawl Trigger → Run workflow** → lihat response JSON di log (`due`, `window`, `period`).
3. Refresh dashboard → artikel dari crawl muncul. Cek tab **Crawl Jobs** untuk status per sumber.
