# PROMPT #3 — IMPLEMENTASI ROLE FAKULTAS

Lanjutkan pengembangan APJAD setelah modul **SUPER ADMIN** dan **ADMIN** selesai.

Untuk tahap ini, fokus **HANYA pada ROLE FAKULTAS**.

Jangan mengerjakan modul JURUSAN, PRODI, scheduler, generate jadwal, atau fitur akademik lainnya yang belum diminta.

---

## 1. AUDIT TERLEBIH DAHULU

Sebelum melakukan perubahan:

1. Inspect struktur project saat ini.
2. Inspect database schema/migrations.
3. Inspect model/entity yang sudah ada.
4. Inspect authentication dan RBAC.
5. Inspect implementasi SUPER ADMIN dan ADMIN dari Prompt #1 dan #2.
6. Pastikan tidak merusak fitur yang sudah berjalan.
7. Gunakan struktur existing project jika sudah tersedia, jangan membuat arsitektur baru tanpa alasan.

Setelah audit, implementasikan modul Fakultas secara konsisten dengan arsitektur yang sudah ada.

---

# 2. SCOPE ROLE FAKULTAS

Role:

```text
FAKULTAS
```

User Fakultas harus terikat ke satu Fakultas melalui:

```text
users.faculty_id
```

Contoh:

```text
User:
- name: Admin Fakultas Teknik dan Teknologi Kemaritiman
- role: FAKULTAS
- faculty_id: FTTK
```

User Fakultas hanya boleh mengakses data yang memiliki:

```text
faculty_id = currentUser.faculty_id
```

Backend wajib melakukan pengecekan scope.

Jangan hanya mengandalkan hidden menu atau route guard di frontend.

---

# 3. FITUR YANG DIMILIKI FAKULTAS

Fakultas bertanggung jawab terhadap:

### Mata Kuliah Fakultas

- MKWU
- MKWF

### Resources

- Ruangan
- Laboratorium
- Alokasi Ruangan
- Alokasi Laboratorium

Fakultas **TIDAK** bertanggung jawab terhadap:

- Manajemen akun
- Mata kuliah Prodi
- Dosen Prodi
- Kelas/Rombel Prodi
- Course Offering
- Jadwal Prodi
- Generate jadwal
- Edit jadwal
- Publish jadwal

Jangan implementasikan fitur-fitur tersebut pada tahap ini.

---

# 4. KATEGORI MATA KULIAH FAKULTAS

Gunakan dua kategori berikut:

```text
MKWU
Mata Kuliah Wajib Universitas
Code: UNV
```

dan

```text
MKWF
Mata Kuliah Wajib Fakultas
Code: FST
```

**PENTING:**

Jangan gunakan istilah MKWK.

Istilah yang benar untuk project APJAD adalah:

```text
MKWU
MKWF
```

---

# 5. DATABASE — COURSE CATEGORY

Jika belum tersedia, buat struktur category yang dapat membedakan scope mata kuliah.

Contoh:

```text
course_categories
- id
- code
- name
- scope
- faculty_id
- created_at
- updated_at
```

Data awal:

```text
UNV
name: Mata Kuliah Wajib Universitas
scope: UNIVERSITY
```

```text
FST
name: Mata Kuliah Wajib Fakultas
scope: FACULTY
faculty_id: FTTK
```

Jangan hardcode category hanya di frontend.

Category harus berasal dari database.

---

# 6. MASTER COURSE FAKULTAS

Gunakan satu tabel master:

```text
courses
```

Minimal memiliki:

```text
id
code
name
sks
semester
type
faculty_id
course_category_id
status
created_at
updated_at
```

`type`:

```text
THEORY
PRACTICUM
```

Untuk tahap ini Fakultas hanya boleh membuat/mengelola course dengan kategori:

```text
MKWU
MKWF
```

dan sesuai scope Fakultasnya.

---

# 7. CRUD MATA KULIAH

Role Fakultas dapat:

- melihat MKWU
- membuat MKWU
- mengedit MKWU
- mengaktifkan/nonaktifkan MKWU

dan:

- melihat MKWF
- membuat MKWF
- mengedit MKWF
- mengaktifkan/nonaktifkan MKWF

Gunakan soft delete/deactivate jika sistem sudah memiliki pola tersebut.

Jangan menghapus data secara hard delete jika sudah berpotensi digunakan oleh data akademik lain.

---

# 8. VALIDASI MATA KULIAH

Saat membuat/edit course:

### Required

- Kode Mata Kuliah
- Nama Mata Kuliah
- SKS
- Semester
- Jenis Mata Kuliah
- Kategori
- Fakultas

### Validasi

- kode tidak boleh duplicate dalam scope yang sama
- nama wajib diisi
- SKS harus valid
- semester harus valid
- type harus `THEORY` atau `PRACTICUM`
- course harus memiliki faculty scope
- user Fakultas tidak boleh memasukkan `faculty_id` milik Fakultas lain

Jangan memaksakan auto-generation kode mata kuliah.

Kode dimasukkan secara manual oleh user.

---

# 9. PEMBEDAAN MKWU DAN MKWF

UI harus jelas membedakan:

```text
Mata Kuliah Fakultas
├── MKWU
│   └── Mata Kuliah Wajib Universitas
│
└── MKWF
    └── Mata Kuliah Wajib Fakultas
```

Contoh sidebar:

```text
Mata Kuliah Fakultas
├── MKWU
└── MKWF
```

User dapat filter berdasarkan:

- kategori
- semester
- status
- jenis mata kuliah

---

# 10. RUANGAN

Fakultas adalah owner resource ruangan.

Buat/pertahankan:

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

Minimal type:

```text
CLASSROOM
```

Status:

```text
ACTIVE
INACTIVE
```

Fakultas dapat:

- melihat ruangan
- menambah ruangan
- edit ruangan
- mengaktifkan/nonaktifkan ruangan

---

# 11. DATA AWAL RUANGAN

Untuk Fakultas:

```text
FTTK
```

seed data:

```text
Room 1 — 35
Room 2 — 42
Room 3 — 42
Room 4 — 60
Room 5 — 42
Room 6 — 60
Room 7 — 50
Room 8 — 42
Room 9 — 42
Room 10 — 42
Room 11 — 42
```

Capacity harus berupa data yang dapat diedit.

Jangan hardcode capacity di scheduler karena scheduler belum dikerjakan.

---

# 12. LABORATORIUM

Fakultas juga memiliki resource Laboratorium.

Gunakan struktur:

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
```

Capacity harus dapat diedit.

Status:

```text
ACTIVE
INACTIVE
```

Fakultas dapat:

- melihat laboratorium
- menambah laboratorium
- mengedit laboratorium
- mengaktifkan/nonaktifkan laboratorium

---

# 13. ALOKASI RUANGAN KE PRODI

Ruangan dimiliki Fakultas tetapi dapat digunakan oleh beberapa Prodi.

**Jangan menambahkan `study_program_id` langsung ke tabel `rooms`.**

Gunakan relasi many-to-many.

Contoh:

```text
rooms
   ↕
study_program_rooms
   ↕
study_programs
```

Struktur junction:

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

Artinya satu ruangan dapat dialokasikan ke beberapa Prodi.

---

# 14. ALOKASI LABORATORIUM KE PRODI

Gunakan konsep yang sama:

```text
laboratories
   ↕
study_program_laboratories
   ↕
study_programs
```

Contoh:

```text
study_program_laboratories
- id
- laboratory_id
- study_program_id
- created_at
- updated_at
```

Fakultas dapat:

- melihat alokasi
- menambahkan Prodi ke resource
- menghapus alokasi

Prodi hanya akan memiliki akses VIEW terhadap resource yang dialokasikan kepadanya pada tahap berikutnya.

---

# 15. SCOPE SECURITY

Semua operasi Fakultas harus menggunakan:

```text
currentUser.faculty_id
```

Contoh:

```text
GET /courses
```

tidak boleh mengembalikan course Fakultas lain.

Contoh:

```text
GET /rooms
```

hanya boleh mengembalikan room milik Fakultas user.

Contoh:

```text
POST /rooms
```

backend harus menggunakan faculty scope dari authenticated user.

Jangan mempercayai `faculty_id` dari request body jika dapat menyebabkan user memasukkan data ke Fakultas lain.

---

# 16. DASHBOARD FAKULTAS

Buat dashboard khusus role Fakultas.

Contoh statistik:

```text
Total MKWU
Total MKWF
Total Ruangan
Total Laboratorium
Total Alokasi Ruangan
Total Alokasi Laboratorium
```

Dashboard hanya menampilkan data dalam scope Fakultas user.

---

# 17. SIDEBAR FAKULTAS

Gunakan:

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

Jangan tampilkan:

```text
Manajemen Akun
Dosen
Kelas
Course Offering
Jadwal
Generate
Publish
```

---

# 18. AUTHORIZATION MATRIX

Pastikan backend memiliki aturan seperti:

```text
FAKULTAS
├── course MKWU/MKWF      → CRUD
├── rooms                  → CRUD
├── laboratories           → CRUD
├── room allocation        → CRUD
├── lab allocation         → CRUD
│
├── accounts               → DENY
├── Prodi courses          → DENY
├── lecturers              → DENY
├── classes                → DENY
├── schedules              → DENY
├── generate schedule      → DENY
└── publish schedule       → DENY
```

Jangan hanya menyembunyikan menu.

API/backend juga harus menolak akses.

---

# 19. SEED DATA ORGANISASI

Jika belum ada, pastikan minimal terdapat:

```text
Fakultas:
FTTK
Fakultas Teknik dan Teknologi Kemaritiman

Jurusan:
Teknik Elektro dan Informatika

Prodi:
S1 Teknik Informatika
Kode: TI

S1 Teknik Elektro
Kode: TE
```

Relasi:

```text
FTTK
└── Teknik Elektro dan Informatika
    ├── S1 Teknik Informatika
    └── S1 Teknik Elektro
```

Jangan membuat struktur organisasi lain di luar kebutuhan ini.

---

# 20. YANG JANGAN DIKERJAKAN

Pada Prompt #3 ini, **JANGAN** implementasikan:

- scheduler
- automatic schedule generation
- conflict detection
- course offering
- kelas/rombel
- dosen
- assignment dosen
- publish schedule
- schedule validation
- import Excel
- role JURUSAN
- role PRODI
- perubahan SUPER ADMIN
- perubahan ADMIN

Fokus hanya:

```text
FAKULTAS
├── MKWU
├── MKWF
├── Ruangan
├── Laboratorium
├── Alokasi Ruangan
└── Alokasi Laboratorium
```

---

# 21. ACCEPTANCE CRITERIA

Prompt #3 dianggap selesai jika:

### Role

- [ ] User dengan role FAKULTAS dapat login.
- [ ] User Fakultas hanya melihat data Fakultasnya.
- [ ] User Fakultas tidak dapat mengakses API Fakultas lain.
- [ ] User Fakultas tidak dapat mengakses modul account management.

### Mata Kuliah

- [ ] MKWU dapat CRUD.
- [ ] MKWF dapat CRUD.
- [ ] MKWU menggunakan kode kategori `UNV`.
- [ ] MKWF menggunakan kode kategori `FST`.
- [ ] Tidak ada istilah `MKWK` di implementasi.
- [ ] Course memiliki faculty scope.
- [ ] Validasi course berjalan.

### Resource

- [ ] Ruangan dapat CRUD.
- [ ] Laboratorium dapat CRUD.
- [ ] Capacity dapat diubah.
- [ ] Resource dapat diaktifkan/nonaktifkan.

### Allocation

- [ ] Ruangan dapat dialokasikan ke Prodi.
- [ ] Satu ruangan dapat dialokasikan ke beberapa Prodi.
- [ ] Laboratorium dapat dialokasikan ke Prodi.
- [ ] Satu laboratorium dapat dialokasikan ke beberapa Prodi.

### UI

- [ ] Dashboard Fakultas tersedia.
- [ ] Sidebar Fakultas sesuai scope.
- [ ] MKWU dan MKWF tampil terpisah.
- [ ] Resource management tersedia.
- [ ] Tidak ada menu scheduler pada role Fakultas.

### Security

- [ ] Authorization dilakukan di backend.
- [ ] Scope `faculty_id` diterapkan pada query.
- [ ] Direct URL/API access di luar scope ditolak.
- [ ] Tidak ada privilege escalation melalui request body.

---

## HASIL AKHIR YANG DIHARAPKAN

Setelah Prompt #3 selesai, struktur APJAD harus sudah memiliki:

```text
SUPER ADMIN
    ↓
ADMIN
    ↓
FAKULTAS
    ├── MKWU
    ├── MKWF
    ├── Ruangan
    ├── Laboratorium
    ├── Alokasi Ruangan
    └── Alokasi Laboratorium
```

Belum masuk ke:

```text
JURUSAN
PRODI
DOSEN
KELAS
COURSE OFFERING
SCHEDULER
```

Kerjakan secara bertahap.

**Audit → implement → migration → seed → backend authorization → UI → testing.**

Jangan mengerjakan fitur di luar scope Prompt #3.