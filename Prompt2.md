# PROMPT #2 — IMPLEMENTASI MANAJEMEN AKUN SUPER ADMIN DAN ADMIN

Lanjutkan pengembangan APJAD setelah Prompt #1 selesai.

Pada tahap ini, fokus hanya pada modul **Manajemen Akun** untuk role SUPER ADMIN dan ADMIN.

Pastikan implementasi menggunakan Master Data Organisasi yang sudah dibuat pada Prompt #1.

Jangan mengerjakan modul akademik Fakultas, Jurusan, dan Prodi pada tahap ini.

---

## 1. AUDIT IMPLEMENTASI SEBELUMNYA

Sebelum melakukan perubahan, periksa:

1. Struktur tabel users dan roles.
2. Implementasi authentication.
3. Implementasi lima role APJAD.
4. Master Data Fakultas.
5. Master Data Jurusan.
6. Master Data Program Studi.
7. Relasi antara Fakultas, Jurusan, dan Prodi.
8. Middleware, policy, dan authorization.
9. Dashboard dan sidebar masing-masing role.

Pastikan tidak membuat ulang tabel atau fitur yang sudah tersedia.

Gunakan struktur existing project. Jika perlu mengubah migration atau relasi, pastikan perubahan tidak merusak data sebelumnya.

---

## 2. ATURAN HAK AKSES MANAJEMEN AKUN

APJAD memiliki dua level pengelola akun.

### SUPER ADMIN

SUPER ADMIN memiliki kewenangan:

- Membuat akun ADMIN.
- Membuat akun FAKULTAS.
- Membuat akun JURUSAN.
- Membuat akun PRODI.
- Melihat seluruh akun.
- Melihat detail akun.
- Mengedit informasi akun.
- Mengaktifkan akun.
- Menonaktifkan akun.
- Melakukan reset password.

SUPER ADMIN tidak boleh membuat akun SUPER ADMIN melalui form biasa.

Pembuatan atau perubahan akun SUPER ADMIN harus melalui mekanisme khusus yang lebih aman, bukan melalui manajemen akun reguler.

### ADMIN

ADMIN memiliki kewenangan:

- Membuat akun FAKULTAS.
- Membuat akun JURUSAN.
- Membuat akun PRODI.
- Melihat akun yang termasuk dalam kewenangannya.
- Mengedit informasi akun yang boleh dikelolanya.
- Mengaktifkan akun.
- Menonaktifkan akun.
- Melakukan reset password sesuai kewenangannya.

ADMIN tidak boleh:

- Membuat akun SUPER ADMIN.
- Membuat akun ADMIN.
- Mengedit akun SUPER ADMIN.
- Mengedit akun ADMIN.
- Menonaktifkan akun SUPER ADMIN.
- Menonaktifkan akun ADMIN.
- Mengubah role akunnya sendiri menjadi SUPER ADMIN.

Perbedaan utama:

```text
SUPER ADMIN
├── Membuat ADMIN
├── Membuat FAKULTAS
├── Membuat JURUSAN
└── Membuat PRODI

ADMIN
├── Membuat FAKULTAS
├── Membuat JURUSAN
└── Membuat PRODI
```

Aturan ini merupakan hierarki kewenangan pengelolaan akun, bukan hierarki organisasi universitas.

---

## 3. FORM TAMBAH AKUN

Buat satu mekanisme form tambah akun yang menyesuaikan field berdasarkan role yang dipilih.

Field dasar:

- Nama lengkap
- Email
- Password
- Role
- Status akun

Ketentuan:

### Nama lengkap
Wajib diisi.

### Email
Wajib diisi dan harus unik.

### Password
Sediakan dua pilihan:

1. Admin memasukkan password awal secara manual.
2. Sistem menghasilkan password sementara secara otomatis.

Jika menggunakan password sementara:
- Jangan menyimpan password dalam bentuk plaintext.
- Berikan informasi password awal secara aman kepada pembuat akun.
- Wajibkan pengguna mengganti password pada login pertama jika mekanisme tersebut tersedia.

### Role
Wajib dipilih.

Pilihan role harus mengikuti kewenangan pembuat akun.

Jika pembuat akun SUPER ADMIN:

```text
ADMIN
FAKULTAS
JURUSAN
PRODI
```

Jika pembuat akun ADMIN:

```text
FAKULTAS
JURUSAN
PRODI
```

Jangan menampilkan role yang tidak boleh dibuat oleh pengguna tersebut.

---

## 4. FORM BERDASARKAN ROLE

Field organisasi harus muncul secara dinamis berdasarkan role yang dipilih.

### A. Jika role ADMIN

Field organisasi tidak diperlukan.

```text
Nama
Email
Password
Role: ADMIN
Status
```

### B. Jika role FAKULTAS

Tampilkan pilihan Fakultas dari Master Data Organisasi.

```text
Nama
Email
Password
Role: FAKULTAS

Fakultas:
[ Pilih Fakultas ]

Status
```

Ketentuan:
- Hanya Fakultas berstatus ACTIVE yang dapat dipilih.
- Akun hanya boleh memiliki satu faculty_id.
- department_id harus NULL.
- study_program_id harus NULL.

### C. Jika role JURUSAN

Tampilkan dropdown bertingkat:

```text
Nama
Email
Password
Role: JURUSAN

Fakultas:
[ Pilih Fakultas ]

Jurusan:
[ Pilih Jurusan ]

Status
```

Ketentuan:
- Pilihan Fakultas berasal dari Master Data.
- Setelah Fakultas dipilih, dropdown Jurusan hanya menampilkan Jurusan milik Fakultas tersebut.
- Hanya organisasi berstatus ACTIVE yang dapat dipilih.
- Akun harus memiliki faculty_id dan department_id.
- study_program_id harus NULL.

### D. Jika role PRODI

Tampilkan dropdown bertingkat:

```text
Nama
Email
Password
Role: PRODI

Fakultas:
[ Pilih Fakultas ]

Jurusan:
[ Pilih Jurusan ]

Program Studi:
[ Pilih Program Studi ]

Status
```

Ketentuan:
- Pilihan Jurusan mengikuti Fakultas yang dipilih.
- Pilihan Prodi mengikuti Jurusan yang dipilih.
- Hanya organisasi berstatus ACTIVE yang dapat dipilih.
- Akun harus memiliki faculty_id, department_id, dan study_program_id.
- Seluruh relasi harus sesuai dengan Master Data Organisasi.

---

## 5. INTEGRASI MASTER DATA ORGANISASI

Jangan membuat ulang data Fakultas, Jurusan, atau Prodi dalam modul Manajemen Akun.

Gunakan tabel Master Data dari Prompt #1.

Contoh:

```text
Fakultas
FTTK - Fakultas Teknik dan Teknologi Kemaritiman

Jurusan
Teknik Elektro dan Informatika

Program Studi
TI - S1 Teknik Informatika
TE - S1 Teknik Elektro
```

Jika Super Admin menambahkan Fakultas baru melalui Master Data, Fakultas tersebut otomatis tersedia pada dropdown pembuatan akun.

Jika sebuah Jurusan nonaktif, Jurusan tersebut tidak boleh dipilih untuk akun baru.

Jika sebuah Prodi nonaktif, Prodi tersebut tidak boleh dipilih untuk akun baru.

Jangan menghapus relasi organisasi secara otomatis dari akun yang sudah terdaftar ketika data organisasi dinonaktifkan.

---

## 6. STRUKTUR DATA AKUN

Sesuaikan dengan struktur database existing.

Secara konseptual, data akun memiliki:

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
- must_change_password nullable
- created_at
- updated_at
```

Pastikan:

| Role | faculty_id | department_id | study_program_id |
|---|---|---|---|
| SUPER ADMIN | NULL | NULL | NULL |
| ADMIN | NULL | NULL | NULL |
| FAKULTAS | Wajib | NULL | NULL |
| JURUSAN | Wajib | Wajib | NULL |
| PRODI | Wajib | Wajib | Wajib |

Jika struktur tersebut sudah tersedia, jangan membuat kolom duplikat.

Gunakan foreign key dan validasi relasi sesuai kebutuhan.

---

## 7. HALAMAN SEMUA AKUN

Sediakan halaman daftar akun dengan tabel yang informatif.

Kolom yang disarankan:

- No.
- Nama
- Email
- Role
- Fakultas
- Jurusan
- Program Studi
- Status
- Aksi

Aksi:
- Detail
- Edit
- Aktifkan/Nonaktifkan
- Reset Password

Terapkan pencarian dan filter:

- Nama atau email.
- Role.
- Fakultas.
- Jurusan.
- Program Studi.
- Status akun.

Jangan menampilkan informasi password dalam tabel atau halaman detail.

Gunakan pagination jika data akun banyak.

---

## 8. PEMBAGIAN HALAMAN AKUN

### Sidebar SUPER ADMIN

```text
Dashboard

Master Data
├── Fakultas
├── Jurusan
└── Program Studi

Manajemen Akun
├── Semua Akun
├── Akun Admin
├── Akun Fakultas
├── Akun Jurusan
└── Akun Prodi
```

### Sidebar ADMIN

```text
Dashboard

Manajemen Akun
├── Semua Akun
├── Akun Fakultas
├── Akun Jurusan
└── Akun Prodi
```

ADMIN tidak boleh melihat menu:
- Master Data.
- Akun Admin.
- Akun Super Admin.

Pastikan sidebar mengikuti role dan hak akses.

---

## 9. PERBEDAAN DATA YANG DAPAT DILIHAT

### SUPER ADMIN

SUPER ADMIN dapat melihat seluruh akun di sistem.

Filter:
- Semua role.
- Semua Fakultas.
- Semua Jurusan.
- Semua Prodi.
- Status aktif/nonaktif.

### ADMIN

ADMIN hanya dapat mengelola kategori akun berikut:

- FAKULTAS
- JURUSAN
- PRODI

ADMIN tidak dapat mengakses informasi akun SUPER ADMIN atau ADMIN melalui endpoint yang tidak diizinkan.

Untuk tahap ini, ADMIN memiliki kewenangan mengelola akun organisasi sesuai batasan role yang telah ditentukan.

Jangan memberikan kewenangan pengelolaan Master Data Organisasi kepada ADMIN.

---

## 10. EDIT AKUN

Ketika mengedit akun, tampilkan informasi yang sesuai dengan role akun tersebut.

Hal yang dapat diedit:
- Nama.
- Email.
- Role, jika diizinkan oleh kewenangan pembuat akun.
- Fakultas.
- Jurusan.
- Program Studi.
- Status.

Aturan penting:

- ADMIN tidak dapat mengubah role menjadi ADMIN atau SUPER ADMIN.
- ADMIN tidak dapat mengedit akun ADMIN atau SUPER ADMIN.
- Perubahan organisasi harus memvalidasi kembali relasi Fakultas, Jurusan, dan Prodi.
- Akun yang sedang digunakan tidak boleh dinonaktifkan oleh pemilik akun itu sendiri.
- SUPER ADMIN tidak boleh membuat dirinya kehilangan satu-satunya akses administratif.
- Lindungi akun SUPER ADMIN terakhir agar tidak dapat dinonaktifkan atau kehilangan akses secara tidak sengaja.

Jika perubahan role atau scope berdampak pada akses lama, pastikan session atau permission diperbarui secara aman.

---

## 11. RESET PASSWORD

Sediakan fitur reset password.

Ketentuan:
- Password lama tidak boleh ditampilkan.
- Password baru harus di-hash.
- SUPER ADMIN dapat reset password akun sesuai kewenangannya.
- ADMIN hanya dapat reset password akun FAKULTAS, JURUSAN, dan PRODI.
- ADMIN tidak boleh reset password akun SUPER ADMIN atau ADMIN.

Jika menggunakan password sementara, minta pengguna mengganti password ketika login berikutnya.

---

## 12. SECURITY DAN BACKEND AUTHORIZATION

Seluruh aturan harus diterapkan di backend.

Jangan hanya mengandalkan:
- Hidden menu.
- Kondisi frontend.
- Dropdown role.
- URL yang tidak ditampilkan.

Lakukan pemeriksaan authorization pada:
- Route.
- Controller.
- Service.
- API.
- Query data.

Lindungi dari:
- Privilege escalation.
- Mass assignment.
- Manipulasi role melalui request body.
- Manipulasi faculty_id.
- Manipulasi department_id.
- Manipulasi study_program_id.
- Akses langsung ke akun yang tidak boleh dikelola.

Role dan scope harus divalidasi ulang di server.

Gunakan prinsip default deny.

---

## 13. DASHBOARD

### Dashboard SUPER ADMIN

Tampilkan ringkasan:

- Total seluruh akun.
- Total akun ADMIN.
- Total akun FAKULTAS.
- Total akun JURUSAN.
- Total akun PRODI.
- Total akun aktif.
- Total akun nonaktif.
- Total Fakultas.
- Total Jurusan.
- Total Program Studi.

### Dashboard ADMIN

Tampilkan ringkasan:

- Total akun Fakultas.
- Total akun Jurusan.
- Total akun Prodi.
- Total akun aktif.
- Total akun nonaktif.

Dashboard ADMIN hanya menghitung akun yang termasuk dalam kewenangannya.

---

## 14. BATASAN PEKERJAAN

Prompt #2 hanya mengerjakan:

```text
MANAJEMEN AKUN
├── Pembuatan Akun
├── Daftar Akun
├── Detail Akun
├── Edit Akun
├── Aktivasi/Nonaktivasi
└── Reset Password
```

Jangan mengerjakan:

- Perubahan Master Data Organisasi kecuali penyesuaian integrasi yang diperlukan.
- CRUD MKWU.
- CRUD MKWF.
- CRUD mata kuliah Prodi.
- Dosen.
- Kelas/Rombel.
- Course Offering.
- Ruangan.
- Laboratorium.
- Alokasi resource.
- Penjadwalan.
- Generate jadwal.
- Monitoring jadwal.
- Role DOSEN.
- Role MAHASISWA.
- Fitur multi-role aktif.

Jangan mengubah aturan bisnis Prompt #1 tanpa alasan teknis yang jelas.

---

## 15. ACCEPTANCE CRITERIA

### SUPER ADMIN

- [ ] Dapat membuat akun ADMIN.
- [ ] Dapat membuat akun FAKULTAS.
- [ ] Dapat membuat akun JURUSAN.
- [ ] Dapat membuat akun PRODI.
- [ ] Dapat melihat seluruh akun.
- [ ] Dapat mengedit akun sesuai ketentuan.
- [ ] Dapat mengaktifkan dan menonaktifkan akun.
- [ ] Dapat melakukan reset password.
- [ ] Tidak dapat membuat Super Admin melalui form biasa.

### ADMIN

- [ ] Dapat membuat akun FAKULTAS.
- [ ] Dapat membuat akun JURUSAN.
- [ ] Dapat membuat akun PRODI.
- [ ] Tidak dapat membuat akun ADMIN.
- [ ] Tidak dapat membuat akun SUPER ADMIN.
- [ ] Tidak dapat mengakses Master Data Organisasi.
- [ ] Tidak dapat mengedit akun ADMIN atau SUPER ADMIN.
- [ ] Tidak dapat mengakses akun yang berada di luar kewenangannya.

### Form

- [ ] Field menyesuaikan role.
- [ ] Dropdown Fakultas mengambil data Master Data.
- [ ] Dropdown Jurusan mengikuti Fakultas.
- [ ] Dropdown Prodi mengikuti Jurusan.
- [ ] Organisasi nonaktif tidak dapat dipilih.
- [ ] Email unik.
- [ ] Validasi berjalan di backend.
- [ ] Scope tersimpan dengan benar.

### Security

- [ ] Backend memeriksa role pembuat akun.
- [ ] Backend memeriksa scope akun yang dikelola.
- [ ] Manipulasi role ditolak.
- [ ] Manipulasi scope organisasi ditolak.
- [ ] Direct URL/API access terlindungi.
- [ ] Password tersimpan dalam bentuk hash.
- [ ] Akun Super Admin terakhir terlindungi.

### UI

- [ ] Dashboard Super Admin tersedia.
- [ ] Dashboard Admin tersedia.
- [ ] Sidebar sesuai role.
- [ ] Daftar akun memiliki pencarian dan filter.
- [ ] Tampilan responsif.
- [ ] Tidak ada menu akademik yang belum menjadi scope Prompt #2.

---

## 16. URUTAN PENGERJAAN

Kerjakan secara bertahap:

1. Audit implementasi Prompt #1.
2. Periksa struktur users dan roles.
3. Sesuaikan relasi pengguna dengan Master Data Organisasi.
4. Implementasikan form tambah akun.
5. Implementasikan daftar akun.
6. Implementasikan detail dan edit akun.
7. Implementasikan aktivasi/nonaktivasi.
8. Implementasikan reset password.
9. Implementasikan backend authorization.
10. Implementasikan dashboard dan sidebar.
11. Jalankan testing seluruh role.
12. Laporkan file yang diubah, migration yang dibuat, fitur yang selesai, dan hasil pengujian.

Jangan melewati audit dan langsung membuat ulang modul.

**TARGET AKHIR:**

SUPER ADMIN dan ADMIN sudah dapat mengelola akun sesuai kewenangan masing-masing.

Seluruh akun FAKULTAS, JURUSAN, dan PRODI harus terhubung dengan Master Data Organisasi dari Prompt #1.

Modul akademik belum dikerjakan pada tahap ini.