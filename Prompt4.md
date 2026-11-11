# PROMPT #3 — IMPLEMENTASI MODUL FAKULTAS APJAD

Lanjutkan pengembangan APJAD setelah Prompt #1, Prompt #2, dan FIX Manajemen Akun selesai.

Pada tahap ini, fokus hanya pada **ROLE FAKULTAS**.

Fakultas bertanggung jawab mengelola:

1. Mata Kuliah Wajib Universitas (MKWU).
2. Mata Kuliah Wajib Fakultas (MKWF).
3. Ruangan.
4. Laboratorium.
5. Alokasi ruangan ke Prodi.
6. Alokasi laboratorium ke Prodi.

Modul ini akan menjadi fondasi untuk penjadwalan Fakultas pada tahap berikutnya.

---

## 1. AUDIT PROJECT TERLEBIH DAHULU

Sebelum melakukan perubahan, periksa:

- Database dan migration existing.
- Model dan relasi tabel.
- Implementasi role FAKULTAS.
- Master Data Organisasi.
- Relasi Fakultas, Jurusan, dan Prodi.
- Manajemen Akun hasil Prompt #2 dan FIX.
- Sidebar dan dashboard.
- Middleware dan authorization.
- Struktur mata kuliah jika sudah tersedia.

Ketentuan:

- Jangan membangun ulang fitur yang sudah berjalan.
- Jangan membuat tabel duplikat.
- Pertahankan struktur project existing.
- Jangan merusak fitur SUPER ADMIN dan ADMIN.
- Laporkan temuan audit sebelum melakukan perubahan besar.

## 2. SCOPE ROLE FAKULTAS

User dengan role FAKULTAS hanya boleh mengelola data Fakultas yang menjadi tanggung jawabnya.

Scope:

```text
users.faculty_id
```

Contoh:

```text
User:
Nama: Brian Darell
Role: FAKULTAS
Fakultas: FTTK
```

Ketentuan:

- faculty_id wajib terisi.
- department_id harus NULL.
- study_program_id harus NULL.

Seluruh query dan operasi backend harus menggunakan faculty_id pengguna yang sedang login.

Fakultas tidak boleh mengakses data Fakultas lain.

## 3. MODUL MATA KULIAH FAKULTAS

Fakultas memiliki dua kategori mata kuliah:

### A. MKWU

Mata Kuliah Wajib Universitas.

```text
Code: UNV
Category: MKWU
```

### B. MKWF

Mata Kuliah Wajib Fakultas.

```text
Code: FST
Category: MKWF
```

**PENTING:**

Gunakan istilah MKWU dan MKWF secara konsisten.

Jangan menggunakan istilah MKWK.

Fakultas bertugas mengelola kedua kategori tersebut.

### Menu

```text
Mata Kuliah Fakultas
├── MKWU
└── MKWF
```

Setiap halaman memiliki:

- Tabel mata kuliah.
- Tambah mata kuliah.
- Edit mata kuliah.
- Detail mata kuliah.
- Aktivasi/nonaktivasi.
- Pencarian.
- Filter semester.
- Filter jenis mata kuliah.
- Filter status.

## 4. DATABASE MASTER MATA KULIAH

Gunakan satu tabel `courses` untuk seluruh kategori mata kuliah APJAD.

Jangan membuat tabel terpisah untuk MKWU, MKWF, INF, atau EEL.

Struktur konseptual:

```text
courses
- id
- code
- name
- sks
- semester
- type
- course_category_id
- faculty_id
- department_id nullable
- study_program_id nullable
- status
- created_at
- updated_at
```

Gunakan kategori dari database melalui `course_categories`.

Kategori awal:

```text
UNV = Mata Kuliah Wajib Universitas
FST = Mata Kuliah Wajib Fakultas
```

Ketentuan:

- MKWU menggunakan kategori UNV.
- MKWF menggunakan kategori FST.
- Mata kuliah Fakultas harus memiliki faculty_id yang sesuai.
- Mata kuliah Fakultas tidak memiliki study_program_id tertentu.
- Department_id tidak perlu diisi jika memang tidak menjadi pemilik mata kuliah.
- Mata kuliah Prodi akan dikelola melalui modul Prodi pada Prompt #4.

Jika tabel dan kolom tersebut sudah tersedia, sesuaikan tanpa membuat duplikasi.

### Field input

- Kode Mata Kuliah.
- Nama Mata Kuliah.
- SKS.
- Semester jika berlaku.
- Jenis Mata Kuliah.
- Kategori.
- Status.

Jenis mata kuliah:

```text
THEORY
PRACTICUM
```

Kode mata kuliah dimasukkan secara manual oleh pengguna.

Jangan memaksakan auto-generation kode.

### Validasi

- Kode wajib diisi.
- Nama wajib diisi.
- SKS harus valid.
- Jenis mata kuliah harus sesuai pilihan.
- Kategori harus sesuai kewenangan Fakultas.
- Kode tidak boleh duplikat dalam lingkup yang ditentukan.
- User tidak boleh mengubah faculty_id ke Fakultas lain melalui request body.

## 5. MODUL RUANGAN

Fakultas bertanggung jawab mengelola ruangan yang dapat digunakan oleh Prodi.

Tabel konseptual:

```text
rooms
- id
- faculty_id
- name
- capacity
- type
- status
- created_at
- updated_at
```

Jenis:

```text
CLASSROOM
```

Status:

```text
ACTIVE
INACTIVE
```

Fitur:

- Melihat ruangan.
- Menambahkan ruangan.
- Mengedit ruangan.
- Mengubah kapasitas.
- Mengaktifkan/nonaktifkan ruangan.
- Melihat alokasi ruangan ke Prodi.

### Data awal ruangan FTTK

```text
Room 1  : 35
Room 2  : 42
Room 3  : 42
Room 4  : 60
Room 5  : 42
Room 6  : 60
Room 7  : 50
Room 8  : 42
Room 9  : 42
Room 10 : 42
Room 11 : 42
```

Kapasitas ruangan harus dapat diedit melalui aplikasi.

Jangan hardcode kapasitas di dalam algoritma penjadwalan.

## 6. MODUL LABORATORIUM

Fakultas juga bertanggung jawab mengelola laboratorium.

Tabel konseptual:

```text
laboratories
- id
- faculty_id
- name
- capacity
- lab_type
- status
- created_at
- updated_at
```

Data awal:

```text
Lab TI
Lab Simulasi
Lab Energi
Lab Elektro
```

Pastikan data awal sesuai kebutuhan Prodi:

- Informatika memiliki Lab TI dan Lab Simulasi.
- Elektro memiliki Lab Energi dan Lab Elektro.

Kapasitas setiap laboratorium dapat ditentukan dan diedit melalui aplikasi.

Fitur:

- Melihat laboratorium.
- Menambah laboratorium.
- Mengedit laboratorium.
- Mengubah kapasitas.
- Mengaktifkan/nonaktifkan laboratorium.
- Mengatur alokasi laboratorium ke Prodi.

Jangan mengasumsikan semua laboratorium dapat digunakan oleh seluruh Prodi.

## 7. ALOKASI RUANGAN KE PRODI

Satu ruangan dapat dialokasikan kepada satu atau beberapa Prodi.

Jangan menambahkan study_program_id langsung ke tabel rooms karena satu ruangan dapat digunakan bersama.

Gunakan relasi many-to-many:

```text
rooms
  ↕
study_program_rooms
  ↕
study_programs
```

Struktur konseptual:

```text
study_program_rooms
- id
- room_id
- study_program_id
- created_at
- updated_at
```

Contoh:

```text
Room 4 → TI
Room 4 → TE

Room 5 → TI
Room 6 → TI
```

Fakultas dapat:

- Melihat alokasi.
- Menambahkan alokasi.
- Menghapus alokasi.
- Melihat daftar Prodi pengguna ruangan.

Ketentuan:

- Prodi harus berasal dari Fakultas yang sama.
- Prodi harus berstatus ACTIVE.
- Ruangan harus berstatus ACTIVE.
- Tidak boleh ada data alokasi duplikat.

## 8. ALOKASI LABORATORIUM KE PRODI

Gunakan pola many-to-many:

```text
laboratories
  ↕
study_program_laboratories
  ↕
study_programs
```

Struktur:

```text
study_program_laboratories
- id
- laboratory_id
- study_program_id
- created_at
- updated_at
```

Fakultas dapat mengatur laboratorium mana yang dapat digunakan setiap Prodi.

Contoh:

```text
Lab TI         → TI
Lab Simulasi   → TI
Lab Energi     → TE
Lab Elektro    → TE
```

Fakultas dapat mengubah alokasi sesuai kebutuhan.

Jangan mengizinkan Prodi dari Fakultas lain menerima alokasi resource.

## 9. HUBUNGAN DENGAN PENJADWALAN

Perhatikan bahwa nantinya Fakultas dan Prodi memiliki generator jadwal masing-masing.

- Fakultas akan melakukan generate jadwal untuk MKWU dan MKWF.
- Prodi akan melakukan generate jadwal untuk mata kuliah INF atau EEL.
- Keduanya menggunakan sistem pengecekan bentrok yang terintegrasi.
- Resource yang dipakai Prodi harus berasal dari alokasi Fakultas.

Maka, data ruangan dan laboratorium harus dirancang agar dapat digunakan oleh modul Prodi nantinya.

**Ketentuan kuota:**

Kapasitas resource harus disimpan sebagai data.

Contoh:

```text
Kuota penawaran mata kuliah: 47
Kapasitas Room 4: 60
```

Ruangan tersebut memenuhi kebutuhan kapasitas.

Namun, jangan membuat logika generate jadwal pada Prompt #3. Pengecekan kuota ketika generate akan dikerjakan pada Prompt #5.

## 10. DASHBOARD FAKULTAS

Buat dashboard khusus role FAKULTAS.

Tampilkan ringkasan:

- Total MKWU.
- Total MKWF.
- Total Ruangan.
- Total Laboratorium.
- Total Alokasi Ruangan.
- Total Alokasi Laboratorium.

Seluruh angka harus mengikuti scope Fakultas pengguna.

Jangan menampilkan data Fakultas lain.

## 11. SIDEBAR FAKULTAS

Gunakan struktur:

```text
Dashboard

Mata Kuliah Fakultas
├── MKWU
└── MKWF

Resources
├── Ruangan
├── Laboratorium
├── Alokasi Ruangan
└── Alokasi Laboratorium
```

Jangan menampilkan:

- Manajemen Akun.
- Master Data Organisasi.
- Mata Kuliah Prodi.
- Dosen.
- Kelas.
- Course Offering.
- Jadwal.
- Generate Jadwal.

## 12. SECURITY DAN AUTHORIZATION

Pastikan:

- User Fakultas hanya mengelola data Fakultasnya.
- Tidak dapat mengubah data Fakultas melalui manipulasi request.
- Tidak dapat melihat resource Fakultas lain.
- Tidak dapat mengalokasikan resource ke Prodi Fakultas lain.
- Tidak dapat mengakses modul Manajemen Akun.
- Semua operasi CRUD dilindungi backend.

Jangan hanya menyembunyikan menu frontend.

Gunakan authorization pada route, controller/service, dan query data.

## 13. BATASAN PEKERJAAN

Pada Prompt #3 jangan mengerjakan:

- Modul Manajemen Akun.
- Master Data Organisasi.
- CRUD mata kuliah Prodi.
- Data dosen.
- Data kelas/rombel.
- Course Offering.
- Kelompok peserta mata kuliah gabungan.
- Generate jadwal.
- Conflict detection.
- Publish jadwal.
- Monitoring Jurusan.

Semua tersebut akan dikerjakan pada prompt berikutnya.

## 14. ACCEPTANCE CRITERIA

### Mata Kuliah

- [ ] MKWU dapat CRUD.
- [ ] MKWF dapat CRUD.
- [ ] Kode kategori UNV dan FST benar.
- [ ] Tidak ada istilah MKWK.
- [ ] Mata kuliah memiliki scope Fakultas yang benar.
- [ ] Validasi berjalan di backend.

### Ruangan

- [ ] Ruangan dapat ditambahkan.
- [ ] Ruangan dapat diedit.
- [ ] Kapasitas dapat diubah.
- [ ] Ruangan dapat diaktifkan/nonaktifkan.
- [ ] Data awal ruangan tersedia.

### Laboratorium

- [ ] Laboratorium dapat ditambahkan.
- [ ] Laboratorium dapat diedit.
- [ ] Kapasitas dapat diubah.
- [ ] Laboratorium dapat diaktifkan/nonaktifkan.

### Alokasi

- [ ] Ruangan dapat dialokasikan ke Prodi.
- [ ] Satu ruangan dapat digunakan beberapa Prodi.
- [ ] Laboratorium dapat dialokasikan ke Prodi.
- [ ] Satu laboratorium dapat digunakan beberapa Prodi.
- [ ] Alokasi hanya untuk Prodi dalam Fakultas yang sama.

### Authorization

- [ ] User Fakultas hanya melihat datanya sendiri.
- [ ] Akses Fakultas lain ditolak.
- [ ] Manipulasi scope ditolak.
- [ ] Role lain tidak mendapatkan akses CRUD Fakultas.

### UI

- [ ] Dashboard Fakultas tersedia.
- [ ] Sidebar sesuai ketentuan.
- [ ] MKWU dan MKWF terpisah.
- [ ] Resource management tersedia.
- [ ] Tampilan responsif.

## 15. URUTAN PENGERJAAN

Kerjakan secara bertahap:

1. Audit project existing.
2. Periksa database dan relasi.
3. Periksa role dan scope Fakultas.
4. Implementasikan MKWU dan MKWF.
5. Implementasikan ruangan.
6. Implementasikan laboratorium.
7. Implementasikan alokasi ruangan.
8. Implementasikan alokasi laboratorium.
9. Implementasikan dashboard.
10. Implementasikan sidebar.
11. Terapkan backend authorization.
12. Jalankan testing.
13. Laporkan file yang diubah, migration, fitur yang selesai, serta hasil pengujian.

Jangan mengerjakan fitur di luar scope Prompt #3.

**TARGET AKHIR:**

Role FAKULTAS sudah dapat mengelola MKWU, MKWF, ruangan, laboratorium, alokasi ruangan, dan alokasi laboratorium melalui aplikasi.

Seluruh data siap digunakan sebagai fondasi modul Prodi dan sistem penjadwalan terintegrasi pada tahap berikutnya.
