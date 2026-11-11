# APJAD - Aplikasi Penjadwalan Mata Kuliah Otomatis

[![Laravel](https://img.shields.io/badge/Laravel-11%2F12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Tests](https://img.shields.io/badge/Tests-73%20Passed%20(100%25)-success?style=for-the-badge&logo=phpunit)](https://phpunit.de)
[![Code Style](https://img.shields.io/badge/Code%20Style-Laravel%20Pint-00D26A?style=for-the-badge)](https://laravel.com/docs/pint)
[![Architecture Docs](https://img.shields.io/badge/Documentation-Backend%20Architecture-blue?style=for-the-badge)](docs/BACKEND_ARCHITECTURE.md)

Sistem Informasi Manajemen dan Penjadwalan Mata Kuliah Otomatis berbasis web (*Monolith Laravel MVC*) yang dirancang untuk mengotomatisasi penyusunan jadwal perkuliahan bebas bentrok (*conflict-free*), mengelola data akademik per program studi/fakultas, dan memproses impor data perkuliahan dari Excel/Spreadsheet secara cerdas.

> 📖 **Dokumentasi Lengkap Logika Backend & Developer Guide:**  
> Untuk penjelasan mendalam mengenai alur logika, constraint satisfaction, algoritma genetika, skema database, dan panduan pengembang selanjutnya, silakan baca [**Dokumentasi Arsitektur Backend (docs/BACKEND_ARCHITECTURE.md)**](docs/BACKEND_ARCHITECTURE.md).

---

## 📌 Ringkasan & Arsitektur Sistem

- **Framework:** Laravel 11/12 (PHP 8.4)
- **Arsitektur:** Model-View-Controller (MVC) Monolith + Service Layer Pattern
- **Frontend:** Blade Templates, Responsive Modern Vanilla CSS & Vanilla JavaScript
- **Database:** MySQL / SQLite
- **Dependency Management:** Composer & NPM
- **Testing Suite:** PHPUnit (73 Feature & Unit Tests, 378 Assertions - 100% Passed)
- **Formatting Standard:** Laravel Pint (PSR-12 / Laravel Standard)

---

## 🚀 Fitur Utama

### 1. Penjadwalan Kuliah Otomatis (Scheduler Engine)
- **Algoritma:** *Greedy Sequential + First-Fit Slot Placement* dengan validasi interval waktu real-time.
- **Pencegahan Bentrok Multi-Dimensi:**
  - **Bentrok Cohort:** Satu angkatan/prodi tidak akan memiliki lebih dari satu kelas pada slot waktu yang sama.
  - **Bentrok Dosen:** Dosen ketua dan dosen anggota dipastikan tidak memiliki jadwal tumpang tindih antar-mata kuliah.
  - **Bentrok Ruangan:** Ruangan fisik maupun laboratorium tidak dapat digunakan lebih dari satu rombel pada jam yang sama.
  - **Manajemen MK Pilihan:** Pengelompokan slot MK pilihan secara terisolasi (maksimum kuota pilihan terdistribusi adil).
  - **Dukungan Slot Berurutan:** Penjadwalan rapi mulai pukul 07:30, 10:00, 13:00, dan 14:50 dengan penyesuaian durasi per SKS.
- **Dukungan MK Tanpa Ruangan:** Mata kuliah seperti Skripsi, Kukerta, KKN, dan Magang dapat dijadwalkan tanpa alokasi ruangan kelas — kolom ruangan ditampilkan kosong (`-`) pada tabel jadwal.
- **Toleransi Dosen Kosong:** MK yang belum memiliki dosen pengampu tetap dapat dijadwalkan; kolom dosen dikosongkan untuk diisi secara manual kemudian.

### 2. Impor Spreadsheet 100% Otomatis (Excel & CSV)
- **Deteksi Delimiter Pintar:** Otomatis mendeteksi pemisah CSV (koma `,`, titik koma `;`, atau tab `\t`) tanpa memecah data teks yang memiliki baris kop/judul instansi.
- **Dukungan Berbagai Format:** Mendukung file `.xlsx`, `.xls`, `.csv`, dan `.ods`.
- **Pemetaan Kolom & Header Fleksibel:** Mengenali berbagai alias nama kolom (`Kode MK`, `KD MKK`, `Nama Mata Kuliah`, `Kredit SKS`, `SMSTR`, `Kuota`, `Dosen PJMK`, `Jenis`, dll.).
- **Deteksi Merged Cells (Carry-Forward Semester):** Sel semester yang kosong akibat penggabungan baris (*merged cells*) di Excel otomatis mewarisi semester dari baris di atasnya.
- **Pemisahan Cerdas Tabel Dosen vs Mata Kuliah:** Otomatis mengidentifikasi tabel inisial/legenda dosen di dalam spreadsheet (misal `Kode Dosen - Dosen`) sehingga nama dosen tidak tertukar menjadi mata kuliah.
- **Perhitungan Kelas Paralel Otomatis:** Menghitung jumlah paralel kelas dari frekuensi baris kelas (A, B, C, dst.) per kode mata kuliah.
- **Deteksi Laboratorium Otomatis:** Mengenali mata kuliah praktikum secara otomatis dan menetapkan kebutuhan laboratorium (`butuh_lab = YA`).

### 3. Fitur Reset & Hapus Data Massal
- **Hapus Semua Data MK & Jadwal:** Menghapus seluruh data mata kuliah beserta relasi rombel dan jadwalnya dalam satu klik tanpa perlu menghapus satu per satu.
- **Hapus Semua Hasil Jadwal:** Mengosongkan jadwal hasil *generate* di tab Jadwal Semester dengan tetap mempertahankan master data mata kuliah.
- **Modal Konfirmasi Aman:** Dialog konfirmasi interaktif untuk mencegah kehilangan data akibat klik tidak disengaja.

### 4. Tampilan Jadwal Semester Interaktif
- **Mode Periode Semester:** Pengalihan instan antara semester **Ganjil** (1, 3, 5, 7) dan **Genap** (2, 4, 6, 8).
- **Filter Semester & Accordion:** Tampilan jadwal per semester yang dapat dibuka/tutup (*accordion*) serta filter pemilihan semester tertentu.
- **Rincian Komprehensif:** Menampilkan jam mulai-selesai, kode rombel/kelas, ruangan, dosen pengampu, SKS, dan tipe perkuliahan.
- **Notifikasi Auto-Dismiss:** Pesan sukses/error menghilang otomatis setelah beberapa detik (4 detik sukses, 6 detik error) dilengkapi progress bar countdown dan tombol tutup manual.

### 5. Manajemen Akun & Hak Akses Berbasis Role (RBAC)
- **Super Admin:** Akses penuh konfigurasi sistem, data fakultas, user, mata kuliah, dan penjadwalan.
- **Fakultas:** Manajemen jadwal dan mata kuliah di lingkup fakultas.
- **Jurusan:** Dashboard pemantauan dan pengelolaan tingkat jurusan.
- **Prodi:** Dashboard dan pemantauan jadwal tingkat program studi.
- **Admin:** Pengelolaan akun pengguna.

---

## 🔍 Hasil Audit Keamanan & Kualitas Kode

| Aspek Audit | Status | Keterangan |
| :--- | :---: | :--- |
| **SQL Injection** | **AMAN** | Seluruh query menggunakan Laravel Eloquent ORM & Query Builder dengan *parameter binding*. |
| **Cross-Site Scripting (XSS)** | **AMAN** | Output HTML di-*escape* secara otomatis oleh Blade Engine `{{ ... }}`. |
| **Cross-Site Request Forgery (CSRF)** | **AMAN** | Semua rute `POST`, `PUT`, dan `DELETE` diproteksi token `@csrf` dan middleware VerifyCsrfToken. |
| **Database Integrity & Cascading** | **AMAN** | Foreign key constraints pada tabel `rombels` dan `jadwals` dengan `onDelete('cascade')`/`onDelete('set null')`. Kolom `dosen_id` dan `ruangan_id` di `jadwals` bersifat nullable untuk mendukung MK tanpa dosen/ruangan. |
| **Autentikasi & Sandi** | **AMAN** | Enkripsi kata sandi menggunakan Bcrypt Hash, pembatasan rate limit login (`throttle:20,1`). |
| **Format & Gaya Kode** | **LULUS** | Seluruh kode PHP telah diformat dan divalidasi menggunakan **Laravel Pint**. |
| **Feature & Regression Tests** | **LULUS** | 63 dari 63 skenario pengujian (`MataKuliahImportTest`, `PenjadwalanDestroyAllTest`, `SchedulerServiceTest`, `UserManagementTest`, `RuanganManagementTest`) lulus 100%. |

---

## 📂 Struktur Direktori Utama

```
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php            # Autentikasi & Profil
│   │   ├── PenjadwalanController.php     # Controller Penjadwalan, Impor & Reset
│   │   ├── UserController.php            # Manajemen Pengguna
│   │   └── ...
│   ├── Models/
│   │   ├── MataKuliahDetail.php          # Master Data Mata Kuliah
│   │   ├── Rombel.php                    # Data Rombel / Kelas Paralel
│   │   ├── Jadwal.php                    # Data Slot Jadwal Terplot
│   │   ├── Dosen.php                     # Master Dosen
│   │   ├── Ruangan.php                   # Master Ruangan & Lab
│   │   └── User.php                      # Pengguna Sistem
│   └── Services/
│       ├── MataKuliahImportService.php   # Service Cerdas Impor Spreadsheet
│       └── SchedulerService.php          # Algoritma Penjadwalan Otomatis
├── database/
│   ├── migrations/                       # Skema Database & Relasi Foreign Key
│   └── seeders/                          # Data Awal Akun, Ruangan, & Pengguna
├── resources/views/
│   └── penjadwalan/
│       ├── index.blade.php               # Halaman Utama Penjadwalan (Tab 1, 2, 3)
│       └── partials/
│           └── jadwal-semester.blade.php # Komponen Accordion Jadwal Semester
├── routes/
│   └── web.php                           # Rute Aplikasi & Middleware RBAC
└── tests/
    └── Feature/
        ├── MataKuliahImportTest.php      # Pengujian Impor Spreadsheet Otomatis
        ├── PenjadwalanDestroyAllTest.php # Pengujian Hapus Semua Data / Jadwal
        └── SchedulerServiceTest.php      # Pengujian Algoritma Scheduler
```

---

## 🛠️ Panduan Instalasi & Menjalankan Aplikasi

### 1. Kebutuhan Sistem
- **PHP** >= 8.4 (Disarankan PHP 8.4.x)
- **Composer** >= 2.0
- **Node.js** >= 18 & **NPM**
- **SQLite** (bawaan tanpa setup) atau **MySQL** >= 8.0 / MariaDB

### 2. Langkah Instalasi Otomatis (Direkomendasikan)
Cukup jalankan satu perintah berikut untuk mengonfigurasi seluruh aplikasi (dependensi, database, migrasi, seeder, dan build aset):

```bash
composer run setup
php artisan serve
```

---

### 3. Langkah Instalasi Manual (Alternatif)
```bash
# Clone repositori
git clone https://github.com/Muhammad-Firzi-Juanda/Aplikasi_Jadwal_Matakuliah.git
cd Aplikasi_Jadwal_Matakuliah

# Instal dependensi PHP
composer install

# Instal dependensi frontend & build asset
npm install
npm run build

# Salin file environment
cp .env.example .env

# Generate application key
php artisan key:generate

# Migrasi tabel dan data awal (seeder akun & ruangan)
php artisan migrate --seed

# Jalankan pengujian unit & feature
php artisan test --compact

# Jalankan server lokal
php artisan serve
```

Aplikasi dapat diakses melalui browser pada `http://127.0.0.1:8000`.

---

## 👤 Akun Pengujian Bawaan

| Role | Email | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin@siwalan.com` | `admin123` |
| **Admin Akun** | `admin@apjad.com` | `admin123` |
| **Admin Fakultas** | `bdarell@gmail.com` | `password123` |
| **Admin Jurusan** | `randtian@gmail.com` | `password123` |
| **Admin Prodi (TI)** | `prodi@apjad.com` | `password123` |

---

## 📄 Lisensi
Aplikasi ini dikembangkan untuk kebutuhan proyek perkuliahan dan pengelolaan jadwal akademik. Hak cipta dilindungi.
