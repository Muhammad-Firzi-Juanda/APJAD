# PROMPT #1 — AUDIT SISTEM, FONDASI ROLE, DAN MASTER DATA ORGANISASI

Saya sedang mengembangkan APJAD (Aplikasi Penjadwalan Mata Kuliah Otomatis).

Pada tahap ini, fokus pekerjaan adalah:
1. Audit project yang sudah ada.
2. Menetapkan fondasi lima role pengguna.
3. Membuat Master Data Organisasi.
4. Mengatur relasi Fakultas, Jurusan, dan Program Studi.
5. Menyiapkan fondasi authorization berdasarkan role dan scope organisasi.

Jangan langsung mengerjakan seluruh fitur APJAD. Ikuti batasan setiap bagian di bawah ini.

---

## 1. AUDIT PROJECT TERLEBIH DAHULU

Sebelum melakukan perubahan, periksa:

- Struktur folder project.
- Framework dan arsitektur yang digunakan.
- Database dan seluruh migration.
- Model dan relasi antartabel.
- Authentication dan authorization.
- Sistem role yang sudah tersedia.
- Dashboard dan sidebar setiap role.
- Implementasi manajemen akun yang sudah ada.

Identifikasi bagian yang sudah berjalan dan bagian yang perlu diperbaiki.

**Penting:**
- Jangan membuat ulang fitur yang sebenarnya sudah tersedia.
- Jangan menghapus fitur yang sudah berjalan.
- Pertahankan konvensi penamaan dan arsitektur existing project.
- Hindari perubahan database yang tidak diperlukan.
- Jika terdapat struktur yang bertentangan dengan rancangan ini, jelaskan terlebih dahulu sebelum melakukan perubahan besar.

---

## 2. LIMA ROLE UTAMA APJAD

APJAD saat ini menggunakan lima role utama:

1. SUPER ADMIN
2. ADMIN
3. FAKULTAS
4. JURUSAN
5. PRODI

Penjelasan:

| Role | Tanggung Jawab |
|---|---|
| SUPER ADMIN | Mengelola akun dan Master Data Organisasi |
| ADMIN | Mengelola akun Fakultas, Jurusan, dan Prodi |
| FAKULTAS | Mengelola mata kuliah wajib Fakultas dan resource |
| JURUSAN | Memantau aktivitas akademik Prodi |
| PRODI | Mengelola kebutuhan akademik dan penjadwalan Prodi |

Pada tahap ini, implementasikan fondasi role dan scope. Detail fitur akademik setiap role akan dikerjakan pada prompt berikutnya.

**Ketentuan saat ini:**
- Satu akun memiliki satu role utama.
- Belum perlu fitur berpindah-pindah role.
- Belum perlu role DOSEN dan MAHASISWA.
- Sistem harus memungkinkan pengembangan multi-role pada masa mendatang tanpa harus merombak seluruh arsitektur.

---

## 3. HIRARKI ORGANISASI APJAD

Struktur organisasi harus mendukung hubungan berikut:

```text
Universitas
└── Fakultas Teknik dan Teknologi Kemaritiman
    Kode: FTTK
    │
    └── Jurusan Teknik Elektro dan Informatika
        │
        ├── S1 Teknik Informatika
        │   Kode Prodi: TI
        │   Kode MK: INF
        │
        └── S1 Teknik Elektro
            Kode Prodi: TE
            Kode MK: EEL
```

Struktur tersebut merupakan data awal, bukan struktur permanen yang dikunci di dalam kode.

Nantinya Super Admin dapat menambahkan Fakultas, Jurusan, dan Prodi lain melalui sistem.

---

## 4. MASTER DATA ORGANISASI

Buat modul Master Data Organisasi yang hanya dapat diakses oleh SUPER ADMIN.

Master Data memiliki tiga bagian:

### A. Master Fakultas

Field minimal:
- id
- code
- name
- status
- created_at
- updated_at

Fitur:
- Melihat daftar Fakultas.
- Menambahkan Fakultas.
- Mengedit Fakultas.
- Mengaktifkan dan menonaktifkan Fakultas.
- Melihat jumlah Jurusan di dalam Fakultas.

### B. Master Jurusan

Field minimal:
- id
- faculty_id
- code
- name
- status
- created_at
- updated_at

Fitur:
- Melihat daftar Jurusan.
- Menambahkan Jurusan.
- Memilih Fakultas induk.
- Mengedit Jurusan.
- Mengaktifkan dan menonaktifkan Jurusan.
- Melihat jumlah Prodi di dalam Jurusan.

Satu Fakultas dapat memiliki banyak Jurusan.

### C. Master Program Studi

Field minimal:
- id
- department_id
- code
- name
- status
- created_at
- updated_at

Fitur:
- Melihat daftar Prodi.
- Menambahkan Prodi.
- Memilih Fakultas dan Jurusan induk.
- Mengedit Prodi.
- Mengaktifkan dan menonaktifkan Prodi.

Satu Jurusan dapat memiliki banyak Prodi.

Pastikan relasi organisasi konsisten. Jika sistem menyimpan `faculty_id` pada tabel Prodi untuk kebutuhan scope, pastikan nilainya selalu sesuai dengan Fakultas milik Jurusan induknya.

---

## 5. PENGELOLAAN MASTER DATA DARI UI

Master Data tidak boleh hanya bisa diubah melalui database atau phpMyAdmin.

Super Admin harus dapat mengelolanya melalui antarmuka aplikasi.

Contoh menu:

```text
Dashboard

Master Data
├── Fakultas
├── Jurusan
└── Program Studi

Manajemen Akun
└── (Dikerjakan pada Prompt #2)
```

Setiap halaman memiliki:
- Tabel daftar data.
- Tombol tambah.
- Form tambah dan edit.
- Pencarian.
- Filter status.
- Informasi relasi organisasi.
- Aksi aktif/nonaktif.

Untuk pemilihan organisasi, gunakan dropdown yang mengambil data dari database.

Contoh form Jurusan:
- Pilih Fakultas.
- Isi kode Jurusan.
- Isi nama Jurusan.

Contoh form Prodi:
- Pilih Fakultas.
- Pilih Jurusan berdasarkan Fakultas yang dipilih.
- Isi kode Prodi.
- Isi nama Prodi.

Gunakan dropdown bertingkat agar pengguna tidak salah memilih relasi organisasi.

---

## 6. VALIDASI MASTER DATA

Terapkan validasi berikut:

### Fakultas
- Kode Fakultas wajib diisi.
- Nama Fakultas wajib diisi.
- Kode tidak boleh duplikat.

### Jurusan
- Fakultas induk wajib dipilih.
- Kode Jurusan wajib diisi.
- Nama Jurusan wajib diisi.
- Kode tidak boleh duplikat dalam scope yang ditentukan.

### Program Studi
- Fakultas wajib dipilih.
- Jurusan wajib dipilih.
- Jurusan harus benar-benar berada di Fakultas yang dipilih.
- Kode Prodi wajib diisi.
- Nama Prodi wajib diisi.
- Kode tidak boleh duplikat dalam scope yang ditentukan.

Gunakan validasi backend, bukan hanya frontend.

Jangan mengizinkan data organisasi yang tidak memiliki induk sesuai hierarkinya.

---

## 7. STATUS DAN PENGHAPUSAN DATA

Gunakan status:

```text
ACTIVE
INACTIVE
```

Jangan melakukan hard delete terhadap Fakultas, Jurusan, atau Prodi yang sudah digunakan oleh data lain.

Jika data sudah memiliki relasi dengan akun atau data akademik:
- Tolak penghapusan permanen.
- Sediakan opsi menonaktifkan data.
- Data nonaktif tidak boleh dipilih ketika membuat relasi baru.
- Data historis tetap dapat ditampilkan sesuai kebutuhan.

---

## 8. SCOPE PENGGUNA

Siapkan struktur scope berdasarkan role.

Contoh field pengguna:

```text
users
- id
- name
- email
- password
- role_id
- faculty_id nullable
- department_id nullable
- study_program_id nullable
- status
- created_at
- updated_at
```

Ketentuan scope:

| Role | faculty_id | department_id | study_program_id |
|---|---|---|---|
| SUPER ADMIN | NULL | NULL | NULL |
| ADMIN | NULL | NULL | NULL |
| FAKULTAS | Wajib | NULL | NULL |
| JURUSAN | Wajib | Wajib | NULL |
| PRODI | Wajib | Wajib | Wajib |

Pastikan kombinasi scope benar-benar sesuai dengan relasi Master Data Organisasi.

Contoh:
- Akun Fakultas hanya terhubung ke satu Fakultas.
- Akun Jurusan hanya terhubung ke Jurusan dalam Fakultas tersebut.
- Akun Prodi hanya terhubung ke Prodi dalam Jurusan dan Fakultas tersebut.

Jangan mempercayai scope yang dikirim langsung dari frontend tanpa validasi backend.

---

## 9. FONDASI AUTHORIZATION

Siapkan middleware, policy, atau mekanisme authorization sesuai arsitektur existing project.

Ketentuan:

- SUPER ADMIN dapat mengakses Master Data Organisasi.
- ADMIN tidak dapat mengakses Master Data Organisasi.
- FAKULTAS tidak dapat mengakses Master Data Organisasi.
- JURUSAN tidak dapat mengakses Master Data Organisasi.
- PRODI tidak dapat mengakses Master Data Organisasi.

Backend wajib memeriksa role dan scope.

Jangan hanya menyembunyikan menu di frontend.

Akses langsung ke URL dan API juga harus dilindungi.

Gunakan prinsip default deny: jika akses tidak diberikan secara eksplisit, maka akses ditolak.

---

## 10. SEED DATA AWAL

Siapkan seed data organisasi berikut:

**Fakultas**

```text
Code: FTTK
Name: Fakultas Teknik dan Teknologi Kemaritiman
Status: ACTIVE
```

**Jurusan**

```text
Name: Teknik Elektro dan Informatika
Fakultas: FTTK
Status: ACTIVE
```

**Program Studi**

```text
Name: S1 Teknik Informatika
Code: TI
Jurusan: Teknik Elektro dan Informatika
Status: ACTIVE
```

```text
Name: S1 Teknik Elektro
Code: TE
Jurusan: Teknik Elektro dan Informatika
Status: ACTIVE
```

Pastikan seeder tidak membuat data duplikat ketika dijalankan berulang kali.

---

## 11. DASHBOARD DAN SIDEBAR SUPER ADMIN

Dashboard Super Admin pada tahap ini dapat menampilkan ringkasan:

- Total Fakultas.
- Total Jurusan.
- Total Program Studi.
- Total Akun.

Menu:

```text
Dashboard

Master Data
├── Fakultas
├── Jurusan
└── Program Studi

Manajemen Akun
└── (Detail implementasi pada Prompt #2)
```

Jangan mencampur pengelolaan Master Data dengan pengelolaan akun.

Keduanya merupakan modul yang berbeda.

---

## 12. BATASAN PEKERJAAN

Pada Prompt #1 ini jangan mengerjakan:

- CRUD akun secara lengkap.
- Form pembuatan akun berdasarkan role.
- Pengaturan akun oleh Admin.
- Modul MKWU.
- Modul MKWF.
- Mata Kuliah Prodi.
- Dosen.
- Kelas/Rombel.
- Course Offering.
- Ruangan.
- Laboratorium.
- Alokasi resource.
- Penjadwalan.
- Generate jadwal.
- Monitoring jadwal.

Manajemen akun akan dikerjakan secara khusus pada Prompt #2.

---

## 13. TESTING DAN ACCEPTANCE CRITERIA

Prompt #1 dianggap selesai apabila:

### Master Data
- [ ] Super Admin dapat menambah Fakultas.
- [ ] Super Admin dapat mengedit Fakultas.
- [ ] Super Admin dapat mengaktifkan/nonaktifkan Fakultas.
- [ ] Super Admin dapat menambah Jurusan.
- [ ] Jurusan terhubung ke Fakultas yang benar.
- [ ] Super Admin dapat menambah Prodi.
- [ ] Prodi terhubung ke Jurusan yang benar.
- [ ] Dropdown organisasi bertingkat berfungsi.
- [ ] Data organisasi tersimpan di database.
- [ ] Seed data tidak menghasilkan duplikasi.

### Role dan Scope
- [ ] Lima role APJAD dikenali sistem.
- [ ] Scope setiap role memiliki ketentuan yang benar.
- [ ] Relasi scope tervalidasi backend.
- [ ] Satu akun hanya memiliki satu role utama pada tahap ini.

### Security
- [ ] Hanya Super Admin yang dapat mengakses Master Data Organisasi.
- [ ] Admin tidak dapat mengakses CRUD Master Data.
- [ ] Role lain tidak dapat mengakses CRUD Master Data.
- [ ] Direct URL dan API terlindungi.
- [ ] Data organisasi yang sudah digunakan tidak dapat dihapus sembarangan.

### Tidak Merusak Fitur
- [ ] Authentication existing tetap berjalan.
- [ ] Fitur existing tidak rusak.
- [ ] Relasi database tidak mengalami konflik.
- [ ] Dashboard dan sidebar sesuai role.

---

## 14. URUTAN PENGERJAAN

Kerjakan dengan urutan:

1. Audit project.
2. Laporkan struktur dan temuan audit.
3. Rancang penyesuaian database.
4. Implementasikan migration dan relasi.
5. Implementasikan model dan validasi.
6. Implementasikan Master Data Organisasi.
7. Implementasikan authorization.
8. Implementasikan dashboard dan sidebar.
9. Jalankan seeder.
10. Jalankan testing.

Jika menemukan masalah pada arsitektur existing, jelaskan alasan dan dampak perubahannya.

Jangan mengerjakan fitur di luar Prompt #1.

**TARGET AKHIR:**

Super Admin sudah dapat mengelola struktur organisasi universitas melalui aplikasi, yaitu Fakultas, Jurusan, dan Program Studi. Struktur ini nantinya menjadi sumber data untuk pembuatan akun dan seluruh modul akademik APJAD.