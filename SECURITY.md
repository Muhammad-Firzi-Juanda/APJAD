# Panduan Keamanan Aplikasi

## Konfigurasi Wajib untuk Production

### 1. Environment Variables (.env)

Tambahkan konfigurasi berikut di file `.env`:

```env
# Session Security
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=60

# HTTPS Enforcement
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=strong_password_here
```

### 2. Perbaikan yang Sudah Diterapkan

#### ✅ Critical Fixes
- **Plaintext Password Fallback DIHAPUS** - AuthController.php:48
- **Rate Limiting Login** - 5 percobaan per menit (routes/web.php:21)
- **Route /change-password diperbaiki** - memanggil method yang benar
- **Duplicate routes dihapus** - /users/update dan /users/delete POST
- **Password minimum 12 karakter** + konfirmasi wajib
- **Middleware EnsureSuperAdmin** - hanya Super Admin bisa CRUD users
- **Authorization middleware** - proteksi routes di level middleware

#### ⚠️ Harus Dikonfigurasi di Production
1. **Session Encryption**: Set `SESSION_ENCRYPT=true` di .env
2. **HTTPS Cookies**: Set `SESSION_SECURE_COOKIE=true` di .env
3. **Session Lifetime**: Turunkan ke 30-60 menit (`SESSION_LIFETIME=60`)

### 3. Migrasi Database - Tambah Foreign Key

Edit `database/migrations/0001_01_01_000000_create_users_table.php` line 32:

```php
$table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
```

Atau tambahkan index pada kolom `role`:

```php
$table->string('role', 50)->index();
```

### 4. CORS Configuration (Jika Ada API)

Buat file `config/cors.php`:

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### 5. Audit Logging (Rekomendasi)

Install package untuk audit trail:

```bash
composer require owen-it/laravel-auditing
```

### 6. Content Security Policy (Rekomendasi)

Install package CSP:

```bash
composer require spatie/laravel-csp
```

## Checklist Deploy Production

- [ ] Set `APP_ENV=production` di .env
- [ ] Set `APP_DEBUG=false` di .env
- [ ] Generate `APP_KEY` baru: `php artisan key:generate`
- [ ] Set `SESSION_ENCRYPT=true`
- [ ] Set `SESSION_SECURE_COOKIE=true`
- [ ] Set `SESSION_LIFETIME=60` atau kurang
- [ ] Aktifkan HTTPS di web server
- [ ] Jalankan `php artisan config:cache`
- [ ] Jalankan `php artisan route:cache`
- [ ] Jalankan `php artisan view:cache`
- [ ] Setup backup database otomatis
- [ ] Monitor failed login attempts

## Password Policy

Aplikasi sekarang enforce:
- Minimum 12 karakter
- Konfirmasi password wajib (`password_confirmation` field)
- Password lama wajib saat ganti password

## Role-Based Access Control

- **Super Admin**: Full access (CRUD users, edit profile sendiri)
- **Fakultas/Jurusan/Prodi**: Read-only users, edit profile sendiri
- Middleware `super_admin` proteksi semua route /users/*

## Rate Limiting

- Login: 5 percobaan per menit
- Tambahkan throttle ke routes lain jika perlu:
  ```php
  Route::post('/users', [...])->middleware('throttle:10,1');
  ```

## SQL Injection Protection

✅ Semua query menggunakan Eloquent ORM - aman dari SQL injection.

## XSS Protection

✅ Blade `{{ }}` auto-escape output - aman dari XSS.

## Mass Assignment Protection

⚠️ Model User memiliki `role` di `$fillable` - validation di controller mencegah privilege escalation.

## Rekomendasi Tambahan

1. Setup monitoring (Sentry, Bugsnag)
2. Enable 2FA untuk Super Admin
3. Implement email verification
4. Setup automated security updates
5. Regular security audit setiap 3 bulan
6. Backup database harian
7. Log retention policy (30-90 hari)
