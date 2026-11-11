# Dokumentasi Arsitektur Backend & Panduan Pengembang (Developer Guide)
## Aplikasi Penjadwalan Mata Kuliah Otomatis (APJAD)

Dokumen ini disusun untuk memberikan pemahaman menyeluruh mengenai arsitektur sistem, alur logika backend, aturan bisnis (*domain rules*), skema database, algoritma penjadwalan, serta panduan bagi pengembang (*developers*) selanjutnya yang akan melanjutkan pengembangan proyek ini.

---

## 📑 Daftar Isi
1. [Ringkasan Sistem & Tech Stack](#1-ringkasan-sistem--tech-stack)
2. [Pola Arsitektur (Architectural Pattern)](#2-pola-arsitektur-architectural-pattern)
3. [Skema Database & Relasi Model (ERD Logic)](#3-skema-database--relasi-model-erd-logic)
4. [Autentikasi & Role-Based Access Control (RBAC)](#4-autentikasi--role-based-access-control-rbac)
5. [Logika Mesin Penjadwalan (Scheduler Engine)](#5-logika-mesin-penjadwalan-scheduler-engine)
   - [5.1 SchedulerService (Greedy & Constraint Satisfaction)](#51-schedulerservice-greedy--constraint-satisfaction)
   - [5.2 GeneticSchedulerService (Algoritma Genetika)](#52-geneticschedulerservice-algoritma-genetika)
   - [5.3 Hard vs Soft Constraints](#53-hard-vs-soft-constraints)
   - [5.4 Penanganan Khusus: MK Tanpa Ruangan & Dosen Kosong](#54-penanganan-khusus-mk-tanpa-ruangan--dosen-kosong)
   - [5.5 Logika Mata Kuliah Pilihan](#55-logika-mata-kuliah-pilihan)
6. [Layanan Impor Data Otomatis (MataKuliahImportService)](#6-layanan-impor-data-otomatis-matakuliahimportservice)
   - [6.1 Pemindaian Format & Delimiter Pintar](#61-pemindaian-format--delimiter-pintar)
   - [6.2 Normalisasi Kolom & Aliases](#62-normalisasi-kolom--aliases)
   - [6.3 Penanganan Merged Cells (Carry-Forward)](#63-penanganan-merged-cells-carry-forward)
   - [6.4 Pemisahan Tabel Dosen vs Mata Kuliah](#64-pemisahan-tabel-dosen-vs-mata-kuliah)
7. [Daftar Endpoint & Rute (Routes & Controllers)](#7-daftar-endpoint--rute-routes--controllers)
8. [Testing & Quality Assurance](#8-testing--quality-assurance)
9. [Panduan Onboarding Pengembang Baru](#9-panduan-onboarding-pengembang-baru)
10. [Rekomendasi Pengembangan Selanjutnya (Roadmap)](#10-rekomendasi-pengembangan-selanjutnya-roadmap)

---

## 1. Ringkasan Sistem & Tech Stack

- **Framework:** Laravel 11/12 (PHP 8.4)
- **Desain Pattern:** Monolith MVC + Service Layer Pattern
- **Frontend Template:** Laravel Blade + Vanilla CSS + Vanilla JS (tanpa dependency berat)
- **Asset Bundler:** Vite 8 + TailwindCSS v4
- **Database:** SQLite (default/lokal dev) / MySQL 8.0+ (production)
- **Spreadsheet Parser:** `phpoffice/phpspreadsheet` / `maatwebsite/excel`
- **Testing Engine:** PHPUnit 12 (73 tests passed, 378 assertions, 100% success rate)
- **Code Linter / Formatter:** Laravel Pint (PSR-12 Standard)

---

## 2. Pola Arsitektur (Architectural Pattern)

Aplikasi mengadopsi pola **Service Layer** di atas MVC bawaan Laravel:

```
[HTTP Request]
       │
       ▼
[Middleware (Auth, Role Check, CSRF, Throttle)]
       │
       ▼
[Controllers]  ───▶  PenjadwalanController, UserController, AuthController
       │
       ├─────────────────────────┬─────────────────────────┐
       ▼                         ▼                         ▼
[SchedulerService]   [GeneticSchedulerService]   [MataKuliahImportService]
       │                         │                         │
       └─────────────────────────┴─────────────────────────┘
                                 │
                                 ▼
                     [Eloquent ORM / Models]
     (MataKuliahDetail, Rombel, Jadwal, Ruangan, Dosen, User, Prodi)
                                 │
                                 ▼
                    [Database (SQLite / MySQL)]
```

### Keuntungan Arsitektur Ini:
1. **Thin Controller, Fat Service:** Logika komputasi kompleks (seperti permutasi slot waktu, evaluasi bentrok, dan pemindaian baris spreadsheet) tidak mengotori Controller.
2. **Testabilitas Tinggi:** Service dapat diuji secara terisolasi melalui Feature/Unit test tanpa harus mereproduksi HTTP request lengkap.
3. **Reusabilitas:** Layanan scheduler dapat dipanggil baik dari Controller HTTP, Artisan CLI command, maupun background jobs (Queue).

---

## 3. Skema Database & Relasi Model (ERD Logic)

### Diagram Entitas Utama:
- **`users`**: Menyimpan kredensial pengguna, `role` (`Super Admin`, `Admin`, `Fakultas`, `Jurusan`, `Prodi`), dan `prodi_id` (foreign key ke `prodi`).
- **`fakultas`**: Master data fakultas (cth: FTTK).
- **`prodi`**: Master data program studi (cth: Teknik Informatika, Teknik Elektro) dengan relasi `belongsTo(Fakultas)`.
- **`mata_kuliah`**: Master acuan kode dan nama mata kuliah induk (kode murni tanpa suffix kelas).
- **`mata_kuliah_details`**: Data penawaran mata kuliah spesifik per semester/kurikulum.
  - Relasi: `hasMany(Rombel)`, `belongsTo(Dosen, 'dosen_ketua_id')`, `belongsTo(Dosen, 'dosen_anggota_id')`.
  - Field penting: `sks`, `semester`, `tipe` (Wajib/Pilihan), `butuh_lab`, `tanpa_ruangan`, `is_active`, `status_tawar`.
- **`rombels`**: Rombongan belajar (kelas paralel, cth: `TIF101-A`, `TIF101-B`).
  - Relasi: `belongsTo(MataKuliahDetail)`, `hasOne(Jadwal)`.
- **`jadwals`**: Slot jadwal perkuliahan terplot hasil komputasi scheduler.
  - Relasi: `belongsTo(Rombel)`, `belongsTo(Ruangan)` (nullable), `belongsTo(Dosen)` (nullable).
  - Field penting: `hari`, `jam_mulai`, `jam_selesai`.
- **`ruangans`**: Master ruangan fisik dan laboratorium.
  - Field: `kode`, `nama`, `kapasitas`, `tipe` (`Ruang Kelas`, `Lab`, `Aula`), `is_active`.
- **`dosens`**: Master tenaga pendidik.
  - Field: `nama`, `nidn`, `inisial`, `email`, `is_active`.

### Konvensi Integritas Data & Nullable Constraints:
- `jadwals.ruangan_id` bersifat **NULLABLE** untuk menampung mata kuliah yang tidak memerlukan ruangan fisik (seperti Skripsi, Magang, KKN/Kukerta).
- `jadwals.dosen_id` bersifat **NULLABLE** sehingga mata kuliah yang belum ditentukan dosen pengampunya tetap dapat dialokasikan slot waktunya.
- Penghapusan cascading (`onDelete('cascade')`) diterapkan pada `rombels` dan `jadwals` ketika mata kuliah terkait dihapus.

---

## 4. Autentikasi & Role-Based Access Control (RBAC)

Sistem menerapkan RBAC bertingkat dengan matriks kewenangan:

| Role | Kelola User | Kelola Ruangan | Kelola Dosen | Impor Excel MK | Generate Jadwal | Scope Akses Data |
|---|:---:|:---:|:---:|:---:|:---:|---|
| **Super Admin** | ✅ | ✅ | ✅ | ✅ | ✅ | Global (Semua Prodi/Fakultas) |
| **Admin Akun** | ✅ | ❌ | ❌ | ❌ | ❌ | Pengelolaan Akun Pengguna |
| **Fakultas** | ❌ | ✅ | ✅ | ✅ | ✅ | Lingkup Seluruh Fakultas |
| **Jurusan** | ❌ | ❌ | ❌ | ❌ | 👁️ (Monitor) | Monitoring Penjadwalan |
| **Prodi** | ❌ | ❌ | ❌ | ✅ | ✅ | Terisolasi per Prodi Milik User |

### Isolasi Scope Prodi:
Pada level `PenjadwalanController` dan `SchedulerService`, jika `auth()->user()->role === 'Prodi'`, query database secara otomatis dikunci:
```php
if ($user?->role === 'Prodi' && $user->prodi?->nama) {
    $prodi = $user->prodi->nama;
}
```
Mata kuliah umum (kategori `MK Universitas` dan `MK Fakultas`) tetap dapat dibaca secara sinkron oleh seluruh prodi.

---

## 5. Logika Mesin Penjadwalan (Scheduler Engine)

### 5.1 SchedulerService (Greedy & Constraint Satisfaction)
Mesin utama bekerja dengan strategi **Greedy Sequential Placement dengan Heuristic Complexity Prioritization**:
1. **Pengurutan Prioritas Mata Kuliah:**
   - Mata kuliah diurutkan berdasarkan tingkat kendala tertinggi:
     `sortByDesc([butuh_lab, sks, jumlah_kelas])`
   - Mata kuliah yang butuh Lab dan memiliki SKS besar diprioritaskan terlebih dahulu karena opsi slot dan ruangannya paling terbatas.
2. **Penentuan Durasi Waktu:**
   - Durasi dihitung berdasarkan 1 SKS = 50 menit.
   - Jam selesai dihitung otomatis berdasarkan jam mulai dan durasi SKS.
3. **Pencarian Slot Waktu (First-Fit with Evaluation):**
   - Menelusuri kombinasi Hari (`Senin` s.d. `Jumat`) dan Slot Jam Mulai (`07:30`, `10:00`, `13:00`, `14:50`).

### 5.2 GeneticSchedulerService (Algoritma Genetika)
Merupakan kelas turunan (`extends SchedulerService`) yang menyediakan hook implementasi evolusi genetik:
- **Kromosom:** Representasi susunan penempatan seluruh rombel ke dalam pasangan `(Hari, Slot Jam, Ruangan)`.
- **Fitness Function:** Dievaluasi dari:
  - Pelanggaran hard constraint (penalti sangat tinggi / 0 fitness).
  - Distribusi beban hari yang merata (penalti hari yang terlalu padat).
  - Jeda waktu dosen (minimasi idle time pengajar).
- **Crossover & Mutation:** Melakukan rekombinasi slot waktu dan mutasi acak pada slot ruangan alternatif.

### 5.3 Hard vs Soft Constraints

#### Hard Constraints (Wajib 100% Bebas Konflik):
1. **Cohort Clash:** Dalam satu angkatan/prodi/semester yang sama, mahasiswa tidak boleh memiliki dua mata kuliah pada interval waktu yang beririsan.
2. **Dosen Clash:** Seorang dosen (baik sebagai Dosen Utama maupun Dosen Anggota) tidak boleh mengajar di dua tempat berbeda pada jam yang sama.
3. **Ruangan Clash:** Satu ruangan fisik/lab tidak boleh ditempati lebih dari satu rombel pada jam yang sama.
4. **Lab Requirement:** Mata kuliah berstatus `butuh_lab = 1` hanya boleh dialokasikan pada ruangan bertipe `Lab`.

#### Soft Constraints (Optimasi & Kenyamanan):
1. Menghindari jadwal pada jeda istirahat ibadah siang (12:00 - 13:00).
2. Penyebaran hari yang seimbang (tidak menumpuk di hari Senin-Selasa saja).
3. Penempatan mata kuliah pilihan pada kelompok slot yang terdistribusi adil.

### 5.4 Penanganan Khusus: MK Tanpa Ruangan & Dosen Kosong
- **`tanpa_ruangan = 1`:** Untuk mata kuliah non-kelas fisik (Skripsi, Tugas Akhir, KKN, Magang, Seminar Hasil):
  - Sistem melompati alokasi ruangan (`ruangan_id = null`).
  - Tidak memicu bentrok kapasitas ruangan.
  - Di tampilan tabel UI ditampilkan dengan tanda strip (`-`).
- **Dosen Nullable:** Jika mata kuliah belum memiliki nama dosen saat diimpor, penjadwalan tetap dapat diproses dan tidak menyebabkan error fatal.

### 5.5 Logika Mata Kuliah Pilihan
- Dibatasi oleh konstanta `MAKS_PILIHAN = 2`.
- MK Pilihan dikelompokkan ke dalam bucket slot. MK dalam satu kelompok sengaja berbagi slot (saling bentrok) karena mahasiswa hanya diizinkan memilih salah satu, sementara antar kelompok menggunakan slot berbeda agar mahasiswa dapat mengambil kombinasi MK pilihan yang sah.

---

## 6. Layanan Impor Data Otomatis (MataKuliahImportService)

Layanan ini dirancang dengan toleransi kesalahan tinggi (*fault-tolerant*) untuk memproses file excel yang sering kali memiliki format tidak rapi:

### 6.1 Pemindaian Format & Delimiter Pintar
- Mendukung format: `.xlsx`, `.xls`, `.csv`, `.ods`.
- Pada file `.csv`, sistem membaca beberapa baris sampel dan secara dinamis mendeteksi delimiter terbanyak (koma `,`, titik koma `;`, atau tab `\t`).

### 6.2 Normalisasi Kolom & Aliases
File input pengguna memiliki variasi penamaan header yang sangat beragam. Service memetakan puluhan sinonim ke field baku:
- `kode_mk`: `kode`, `kd_mk`, `kdmk`, `course code`, `kd_mkk`, `kode matakuliah`.
- `nama_mk`: `nama`, `mata kuliah`, `matkul`, `course name`, `judul mk`.
- `sks`: `bobot`, `kredit`, `sks mk`, `credits`, `bobot sks`.
- `semester`: `smt`, `sem`, `tingkat`, `smstr`.
- `dosen_ketua`: `dosen pj`, `pj mk`, `dosen pengampu 1`, `dosen utama`.

### 6.3 Penanganan Merged Cells (Carry-Forward)
Di Excel, kolom `Semester` sering kali di-*merge* untuk satu blok mata kuliah. Saat dibaca program, sel di bawahnya bernilai `null`. Service menerapkan algoritma **Carry-Forward**: jika baris berikutnya kosong, nilai semester otomatis mewarisi baris di atasnya.

### 6.4 Pemisahan Tabel Dosen vs Mata Kuliah
Banyak template kampus menaruh tabel daftar dosen/inisial di bagian bawah sheet yang sama. Service mendeteksi pola baris pemisah (misal: header `Kode Dosen | Nama Dosen`) dan secara cerdas memasukkan data tersebut ke master tabel `dosens` (beserta pemetaan `inisial`), tanpa mengotorinya ke tabel mata kuliah.

---

## 7. Daftar Endpoint & Rute (Routes & Controllers)

Seluruh rute backend didefinisikan di [`routes/web.php`](file:///c:/wamp64/www/Aplikasi_Jadwal_Matakuliah-main33/routes/web.php):

| Method | URL Path | Controller & Action | Fungsi |
|---|---|---|---|
| `GET` | `/login` | `AuthController@showLogin` | Halaman login |
| `POST` | `/login` | `AuthController@login` | Proses autentikasi (throttle: 20/min) |
| `POST` | `/logout` | `AuthController@logout` | Logout sesi |
| `GET` | `/penjadwalan` | `PenjadwalanController@index` | Dashboard utama penjadwalan & jadwal |
| `POST` | `/penjadwalan` | `PenjadwalanController@store` | Tambah mata kuliah manual |
| `PUT` | `/penjadwalan/{mk}` | `PenjadwalanController@update` | Update data mata kuliah |
| `DELETE` | `/penjadwalan/{mk}` | `PenjadwalanController@destroy` | Hapus satu mata kuliah |
| `POST` | `/penjadwalan/generate` | `PenjadwalanController@generate` | Eksekusi engine penjadwalan otomatis |
| `DELETE` | `/penjadwalan/jadwal/destroy-all` | `PenjadwalanController@destroyAllJadwal` | Reset seluruh jadwal terplot |
| `DELETE` | `/penjadwalan/destroy-all` | `PenjadwalanController@destroyAll` | Hapus seluruh data MK & jadwal |
| `POST` | `/penjadwalan/import-excel` | `PenjadwalanController@importExcel` | Upload & proses file Excel/CSV |
| `GET` | `/penjadwalan/export-excel` | `PenjadwalanController@exportExcel` | Unduh jadwal ke file spreadsheet |
| `RESOURCE` | `/users` | `UserController` | CRUD akun pengguna (Role Admin/Super Admin) |
| `RESOURCE` | `/ruangan` | `RuanganController` | CRUD master ruangan & laboratorium |

---

## 8. Testing & Quality Assurance

Proyek ini telah dilengkapi dengan rangkaian automated test menyeluruh menggunakan **PHPUnit**:

### Ringkasan Hasil Pengujian:
```text
PHPUnit 12.5.12 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.3.28 with Xdebug
Configuration: phpunit.xml

Tests: 73 passed (378 assertions)
Duration: ~21 detik
Status: 100% SUCCESS
```

### Lingkup Test Suite:
1. [`MataKuliahImportTest.php`](file:///c:/wamp64/www/Aplikasi_Jadwal_Matakuliah-main33/tests/Feature/MataKuliahImportTest.php): Validasi parsing Excel/CSV, carry-forward merged cells, pemisahan dosen, dan deteksi lab.
2. [`SchedulerServiceTest.php`](file:///c:/wamp64/www/Aplikasi_Jadwal_Matakuliah-main33/tests/Feature/SchedulerServiceTest.php): Pengujian bebas bentrok (dosen, ruangan, cohort), kuota pilihan, dan durasi SKS.
3. [`PenjadwalanDestroyAllTest.php`](file:///c:/wamp64/www/Aplikasi_Jadwal_Matakuliah-main33/tests/Feature/PenjadwalanDestroyAllTest.php): Pengujian reset jadwal parsial dan reset total data.
4. [`PenjadwalanLogPersistenceTest.php`](file:///c:/wamp64/www/Aplikasi_Jadwal_Matakuliah-main33/tests/Feature/PenjadwalanLogPersistenceTest.php): Memastikan log generate tersimpan di session/cache saat refresh.
5. [`RuanganManagementTest.php`](file:///c:/wamp64/www/Aplikasi_Jadwal_Matakuliah-main33/tests/Feature/RuanganManagementTest.php): CRUD kapasitas ruangan dan filter tipe lab/kelas.
6. [`SynchronizedRoleScheduleTest.php`](file:///c:/wamp64/www/Aplikasi_Jadwal_Matakuliah-main33/tests/Feature/SynchronizedRoleScheduleTest.php): Sinkronisasi data antar role (Super Admin, Fakultas, Prodi).
7. [`UserManagementTest.php`](file:///c:/wamp64/www/Aplikasi_Jadwal_Matakuliah-main33/tests/Feature/UserManagementTest.php): Validasi otorisasi dan manajemen akun pengguna.

---

## 9. Panduan Onboarding Pengembang Baru

Bagi rekan pengembang yang baru melakukan `git clone`:

### 1. Prasyarat:
- PHP >= 8.3/8.4 dengan ekstensi `pdo_sqlite`, `pdo_mysql`, `mbstring`, `zip`, `gd`.
- Composer 2.x
- Node.js >= 18.x & NPM
- Git

### 2. Langkah Setup Sekali Klik:
```bash
composer run setup
```
Perintah ini akan secara otomatis:
1. Menginstal dependensi PHP (`composer install`).
2. Menyalin file `.env.example` ke `.env`.
3. Membuat database `database.sqlite` (jika belum ada).
4. Menghasilkan Application Key (`php artisan key:generate`).
5. Menjalankan migrasi database dan seeding data awal (`php artisan migrate --force --seed`).
6. Menginstal paket frontend (`npm install`).
7. Mem-build aset antarmuka (`npm run build`).

### 3. Menjalankan Server Pengembangan:
```bash
# Jalankan server aplikasi
php artisan serve

# (Opsional jika ingin hot-reload styling)
npm run dev
```

### 4. Memverifikasi Kode Sebelum Commit:
```bash
# Jalankan test suite
php artisan test --compact

# Jalankan linter & formatting kode
vendor/bin/pint --dirty --format agent
```

---

## 10. Rekomendasi Pengembangan Selanjutnya (Roadmap)

Bagi pengembang yang akan melanjutkan proyek ini, berikut beberapa rekomendasi peningkatan fitur:

1. **Peningkatan Algoritma Genetika Lanjutan (`GeneticSchedulerService`):**
   - Mengimplementasikan *Multi-Objective Fitness Function* (misalnya: meminimalkan hari kosong bagi mahasiswa dan memusatkan jadwal dosen pada hari tertentu).
   - Menambahkan pengaturan kustom parameter GA di antarmuka web (jumlah generasi, mutation rate, population size).
2. **Ekspor Laporan Resmi (PDF Cetak Siap Pakai):**
   - Menambahkan fitur ekspor format PDF menggunakan package seperti `barryvdh/laravel-dompdf` dengan format kop surat resmi kampus.
3. **Preferensi Waktu Mengajar Dosen (Dosen Availability):**
   - Menambahkan tabel ketersediaan hari/jam bagi dosen yang memiliki komitmen di luar kampus (misal: dosen hanya bersedia mengajar hari Selasa & Kamis).
4. **REST API / Webhook Integrasi:**
   - Menyediakan API berbasis Sanctum untuk integrasi langsung dengan Sistem Informasi Akademik (SIAKAD) universitas.
5. **Notifikasi Otomatis:**
   - Pengiriman email rekap jadwal ke masing-masing dosen pengampu setelah jadwal difinalisasi.
