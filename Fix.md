# FIX — Penyempurnaan Manajemen Akun dan Generate Akun Massal APJAD

Lakukan penyesuaian pada fitur Manajemen Akun dan Generate Akun berdasarkan struktur aplikasi, database, controller, model, service, route, middleware, dan tampilan yang sudah tersedia.

Jangan membangun ulang modul dari awal. Audit implementasi yang ada terlebih dahulu dan pertahankan fitur lain yang sudah berjalan.

## 1. Ketentuan Role dan Hak Akses

APJAD memiliki lima role:
1. SUPER ADMIN
2. ADMIN
3. FAKULTAS
4. JURUSAN
5. PRODI

Hanya boleh ada satu akun SUPER ADMIN dan satu akun ADMIN. Kedua akun tersebut dibuat melalui proses bootstrap/instalasi.

SUPER ADMIN dan ADMIN sama-sama dapat membuat akun operasional FAKULTAS, JURUSAN, dan PRODI, baik secara manual maupun massal melalui Excel. Keduanya tidak boleh membuat akun ADMIN atau SUPER ADMIN tambahan.

**SUPER ADMIN:**
- Membuat akun FAKULTAS, JURUSAN, dan PRODI secara manual.
- Membuat akun FAKULTAS, JURUSAN, dan PRODI secara massal melalui Excel.
- Melihat Master Data Organisasi secara read-only.
- Melihat riwayat generate akun.
- Tidak dapat mengedit, menghapus, menonaktifkan, atau mereset password akun operasional yang telah dibuat.
- Tidak memiliki akses ke pengelolaan data akademik.

**ADMIN:**
- CRUD manual akun FAKULTAS, JURUSAN, dan PRODI.
- Generate akun massal melalui Excel.
- Mengedit akun operasional.
- Mengaktifkan dan menonaktifkan akun.
- Melakukan reset password.
- Mengelola Master Data Organisasi secara global.
- Mengakses fitur administrasi global APJAD sesuai tahapan pengembangan.

Kedua role tidak boleh membuat akun ADMIN atau SUPER ADMIN tambahan. Pembatasan harus diterapkan di backend dan database, bukan hanya pada tampilan.

## 2. Master Data Organisasi

Master Data Organisasi dikelola oleh ADMIN sebagai satu sumber data utama.

Struktur organisasi saat ini:

- Fakultas Teknik dan Teknologi Kemaritiman (FTTK)
  - Jurusan Teknik Elektro dan Teknik Informatika
    - S1 Teknik Informatika
    - S1 Teknik Elektro
  - Jurusan Teknik Industri Maritim
    - S1 Teknik Perkapalan
    - S1 Teknik Industri
    - S1 Kimia
  - Jurusan Teknik Sipil dan Perencanaan
    - S1 Teknik Sipil
    - S1 Perencanaan Wilayah dan Kota
    - S1 Arsitektur

Jangan hardcode nama atau jumlah organisasi. Seluruh pilihan harus berasal dari Master Data Organisasi yang aktif di database.

Relasi organisasi:
- Fakultas memiliki banyak Jurusan.
- Jurusan memiliki banyak Program Studi.

Scope akun:
- SUPER ADMIN: seluruh FK organisasi NULL.
- ADMIN: seluruh FK organisasi NULL.
- FAKULTAS: faculty_id wajib; department_id dan study_program_id NULL.
- JURUSAN: faculty_id dan department_id wajib; study_program_id NULL.
- PRODI: faculty_id, department_id, dan study_program_id wajib.

Gunakan foreign key/ID organisasi, bukan hanya menyimpan nama organisasi sebagai teks.

## 3. CRUD Pembuatan Akun Manual

Pertahankan fitur pembuatan akun satu per satu.

Form pembuatan akun manual memiliki:
- Nama Lengkap
- Email
- NUPTK (opsional)
- Nomor HP (opsional)
- Jabatan
- Fakultas
- Jurusan
- Program Studi
- Password dan konfirmasi password

Jabatan menjadi dasar penentuan role. Sediakan pilihan jabatan resmi berikut saja:
- Dekan
- Wakil Dekan
- Ketua Jurusan
- Ketua Program Studi

Pemetaan:
- Dekan dan Wakil Dekan → FAKULTAS.
- Ketua Jurusan → JURUSAN.
- Ketua Program Studi → PRODI.

Jangan menyediakan pemilihan role bebas yang memungkinkan scope organisasi tidak sesuai.

Aturan pengisian organisasi:
- Dekan dan Wakil Dekan: memilih Fakultas. Jurusan dan Program Studi tidak diperlukan.
- Ketua Jurusan: memilih Jurusan. Fakultas otomatis mengikuti induk Jurusan.
- Ketua Program Studi: memilih Jurusan terlebih dahulu, lalu memilih Program Studi yang berada di bawah Jurusan tersebut. Fakultas otomatis mengikuti induk Program Studi.

Form manual menggunakan validasi yang sama dengan proses import Excel.

## 4. Generate Akun Massal Melalui Excel

Fitur ini dapat digunakan oleh SUPER ADMIN dan ADMIN untuk membuat banyak akun operasional sekaligus.

### 4.1 Template Excel

Sediakan tombol Download Template Excel.

Gunakan format kolom berikut:

| Kolom | Ketentuan |
|---|---|
| Nama Lengkap | Wajib |
| Email | Wajib dan harus valid |
| NUPTK | Opsional |
| No. HP | Opsional |
| Fakultas | Diisi atau diturunkan otomatis apabila relasinya jelas |
| Jurusan | Diisi atau diturunkan otomatis dari Program Studi |
| Program Studi | Wajib untuk Ketua Program Studi |
| Jabatan | Wajib |

**Jangan menyediakan kolom Role dan Password dalam template Excel.**

Contoh data dummy:

| Nama Lengkap | Email | NUPTK | No. HP | Fakultas | Jurusan | Program Studi | Jabatan |
|---|---|---|---|---|---|---|---|
| Martaleli Ibet | martaleli@fttk.ac.id | 11223344 | 82177642122 | FTTK | - | - | Dekan |
| Sitimah Laila | sitimah@fttk.ac.id | 73123790 | 82377564312 | FTTK | - | - | Wakil Dekan |
| Rizal Ferdi | rizal@fttk.ac.id | 34562199 | 81289903321 | FTTK | Teknik Elektro dan Teknik Informatika | - | Ketua Jurusan |
| Feri Irawan | feri@fttk.ac.id | 67784354 | 85231890716 | FTTK | Teknik Elektro dan Teknik Informatika | Teknik Informatika | Ketua Program Studi |
| Holland Budi | holland@fttk.ac.id | 54378851 | 82288904366 | FTTK | Teknik Elektro dan Teknik Informatika | Teknik Elektro | Ketua Program Studi |

Tanda "-" hanya menunjukkan kolom yang tidak perlu diisi. Dalam implementasi Excel, gunakan sel kosong.

### 4.2 Upload Excel

Sediakan:
- Download Template.
- Input Upload File.
- Dukungan XLSX, XLS, dan CSV.
- Validasi format, ukuran, dan struktur file.
- Informasi kesalahan apabila kolom wajib tidak ditemukan atau file tidak sesuai.

Upload hanya membaca dan memproses data. Jangan langsung membuat akun ketika file diunggah.

Jangan menjalankan formula atau macro dari file Excel.

### 4.3 Deteksi Role Otomatis Berdasarkan Jabatan

**Role ditentukan otomatis berdasarkan kolom Jabatan. Pengguna tidak perlu memilih role secara manual.**

Gunakan pemetaan jabatan resmi berikut:

| Jabatan | Role Otomatis |
|---|---|
| Dekan | FAKULTAS |
| Wakil Dekan | FAKULTAS |
| Ketua Jurusan | JURUSAN |
| Ketua Program Studi | PRODI |

Gunakan pencocokan jabatan yang konsisten setelah normalisasi teks, misalnya mengabaikan perbedaan huruf besar dan kecil serta spasi yang tidak perlu.

Jangan menggunakan alias jabatan seperti Kepala Jurusan, Kajur, Kepala Prodi, atau Kaprodi. Hanya empat nama jabatan resmi di atas yang diterima.

Apabila jabatan tidak sesuai dengan pilihan resmi tersebut, tampilkan status ERROR dan minta pengguna memperbaikinya.

Role yang dihasilkan sistem bersifat read-only pada Preview. Jika pengguna mengubah Jabatan, sistem harus menghitung ulang role secara otomatis.

### 4.4 Pemetaan Struktur Organisasi Otomatis

**A. Dekan dan Wakil Dekan**
- Role otomatis FAKULTAS.
- Fakultas wajib diketahui.
- Jurusan dan Program Studi tidak diperlukan.
- Jika hanya ada satu Fakultas aktif dan kolom Fakultas kosong, sistem boleh memilihnya otomatis.
- Jika terdapat beberapa Fakultas aktif dan data tidak cukup untuk menentukan Fakultas, tampilkan ERROR.

**B. Ketua Jurusan**
- Role otomatis JURUSAN.
- Sistem mencocokkan Jurusan dari Excel dengan Master Data Organisasi.
- Fakultas otomatis diambil dari induk Jurusan.
- Program Studi dikosongkan.
- Jika Fakultas yang diisi tidak sesuai dengan induk Jurusan, tampilkan ERROR.

**C. Ketua Program Studi**
- Role otomatis PRODI.
- Sistem mencocokkan Program Studi dari Excel dengan Master Data Organisasi.
- Jika Jurusan diisi, validasi kecocokannya dengan induk Program Studi.
- Fakultas dan Jurusan otomatis diperoleh dari relasi induk Program Studi.
- Program Studi wajib dipilih dan harus aktif.
- Jika Program Studi tidak ditemukan atau relasinya tidak sesuai, tampilkan ERROR.

Contoh:
- Teknik Informatika → Jurusan Teknik Elektro dan Teknik Informatika → FTTK.
- Teknik Elektro → Jurusan Teknik Elektro dan Teknik Informatika → FTTK.

Gunakan ID organisasi untuk menyimpan relasi setelah pencocokan. Jangan hanya mengandalkan nama organisasi sebagai teks.

## 5. Preview Data yang Dapat Diedit

Setelah upload berhasil, tampilkan tabel Preview sebelum akun dibuat.

Kolom Preview:
- Nama Lengkap
- Email
- NUPTK
- No. HP
- Fakultas
- Jurusan
- Program Studi
- Jabatan
- Role Otomatis
- Status Validasi
- Aksi Edit

Pengguna dapat memperbaiki:
- Nama Lengkap
- Email
- NUPTK
- No. HP
- Fakultas
- Jurusan
- Program Studi
- Jabatan

Aturan:
- Role Otomatis tidak dapat diedit langsung.
- Perubahan Jabatan akan menghitung ulang role.
- Perubahan Program Studi memperbarui Jurusan dan Fakultas berdasarkan relasi induknya.
- Perubahan Jurusan memperbarui Fakultas secara otomatis.
- Jika Jabatan berubah, sesuaikan atau kosongkan kolom organisasi yang tidak lagi relevan.
- Dropdown Fakultas, Jurusan, dan Program Studi harus mengambil data dinamis dari database.
- Dropdown Program Studi harus terfilter berdasarkan Jurusan.
- Sediakan aksi menghapus atau mengecualikan baris yang tidak akan diproses.

Status validasi:
- VALID: Data lengkap dan sesuai.
- WARNING: Ada informasi opsional atau kondisi yang perlu diperhatikan.
- ERROR: Data wajib kosong, jabatan tidak dikenal, organisasi tidak ditemukan, relasi organisasi tidak sesuai, atau terjadi duplikasi.

Tampilkan ringkasan jumlah VALID, WARNING, dan ERROR.

Jangan menggagalkan seluruh proses hanya karena satu baris bermasalah. Pengguna dapat memperbaiki atau mengecualikan baris bermasalah dan melanjutkan baris lain yang valid.

## 6. Validasi Duplikasi

Periksa:
- Email yang sudah digunakan.
- NUPTK yang sudah digunakan apabila diisi.
- Duplikasi email atau NUPTK antarbaris pada file yang sama.
- Penugasan jabatan aktif yang bertabrakan dalam scope organisasi yang sama.

Cegah lebih dari satu penugasan aktif untuk jabatan yang memang hanya boleh memiliki satu pemegang pada satu scope, misalnya Dekan, Ketua Jurusan pada satu Jurusan, dan Ketua Program Studi pada satu Program Studi.

Jangan menerapkan batas satu orang untuk seluruh role FAKULTAS, karena Wakil Dekan dapat memiliki lebih dari satu pemegang sesuai struktur organisasi.

Lakukan validasi ulang di backend saat pengguna menekan ACC.

## 7. Pengaturan Password Setelah Preview

Password tidak dibaca dari Excel.

Setelah Preview selesai, sediakan dua metode:

**Metode 1 — Password Otomatis**
- Sistem menghasilkan password acak yang kuat dan unik untuk setiap akun.
- Password tidak boleh sama untuk semua pengguna.
- Simpan hanya password dalam bentuk hash di database.
- Wajib mengganti password ketika pertama kali login.
- Sediakan unduhan kredensial sementara satu kali setelah proses berhasil.
- Jangan menyimpan password plaintext di database, log, atau riwayat generate.
- Jangan menyediakan unduhan ulang password plaintext dari riwayat generate.

**Metode 2 — Password Manual**
- Pengguna mengisi password untuk setiap akun.
- Sediakan konfirmasi password.
- Terapkan aturan keamanan yang sama seperti form manual.
- Password tidak boleh kosong.
- Simpan password dalam bentuk hash.
- Jangan menyimpan password plaintext pada riwayat.

Tombol ACC hanya aktif jika:
- Metode password sudah dipilih.
- Semua baris yang dipilih untuk diproses berstatus valid.
- Password memenuhi aturan keamanan.
- Tidak ada duplikasi atau konflik yang belum diselesaikan.

## 8. Konfirmasi dan Proses Pembuatan Akun

Sebelum proses dijalankan, tampilkan modal konfirmasi berisi:
- Total akun yang akan dibuat.
- Jumlah akun FAKULTAS.
- Jumlah akun JURUSAN.
- Jumlah akun PRODI.
- Metode password yang dipilih.

Setelah pengguna menekan ACC:
1. Kirim data Preview ke backend.
2. Validasi ulang seluruh data.
3. Pastikan role dan scope organisasi sesuai.
4. Pastikan akun belum pernah dibuat.
5. Buat akun menggunakan transaksi database.
6. Hash password sebelum disimpan.
7. Simpan hasil proses untuk riwayat generate.
8. Tampilkan ringkasan akun berhasil dan gagal dibuat.

Pastikan aman terhadap klik ganda, pengiriman ulang, atau upload file yang sama secara berulang. Gunakan mekanisme idempotensi atau pemeriksaan duplikasi yang sesuai.

## 9. Riwayat Generate Akun

Sediakan halaman Riwayat Generate Akun.

Informasi:
- Tanggal dan waktu proses.
- Pengguna yang menjalankan proses.
- Nama file yang diunggah.
- Jumlah data yang diproses.
- Jumlah akun berhasil dibuat.
- Jumlah akun gagal dibuat.
- Ringkasan kesalahan.

Jangan menyimpan password plaintext dalam riwayat.

## 10. Tampilan dan Navigasi

Pada sidebar SUPER ADMIN dan ADMIN, sediakan bagian Manajemen Akun:
- Daftar Akun.
- Tambah Akun Manual.
- Generate Akun Excel.
- Riwayat Generate Akun.
- Download Template Excel.

Tampilkan menu dan aksi sesuai hak akses masing-masing role.

Pertahankan desain APJAD yang sudah ada. Preview harus mudah diedit, responsif, dan menampilkan validasi dengan jelas.

## 11. Keamanan dan Kualitas Implementasi

Pastikan:
- Validasi backend menjadi sumber kebenaran utama.
- Otorisasi diterapkan untuk setiap route dan aksi.
- CSRF protection tetap aktif sesuai framework.
- Seluruh input divalidasi dan disanitasi.
- Proses import menggunakan transaksi database.
- Password selalu disimpan menggunakan hashing yang aman.
- Kredensial tidak ditaruh di source code.
- SUPER ADMIN dan ADMIN tidak dapat dibuat melalui form manual maupun Excel.
- Frontend tidak dapat memanipulasi role dan scope.
- Relasi organisasi menggunakan foreign key yang benar.
- Jangan menghapus data lama tanpa memeriksa relasi yang menggunakannya.

## 12. Pengujian

Uji skenario berikut:

1. Import Dekan dengan Fakultas FTTK → FAKULTAS.
2. Import Wakil Dekan dengan Fakultas FTTK → FAKULTAS.
3. Import Ketua Jurusan → JURUSAN dan Fakultas terisi otomatis.
4. Import Ketua Program Studi Teknik Informatika → PRODI dan Jurusan serta Fakultas terisi otomatis.
5. Import Ketua Program Studi Teknik Elektro → PRODI dengan relasi organisasi yang benar.
6. Jabatan tidak sesuai daftar resmi → ERROR.
7. Program Studi dan Jurusan tidak cocok → ERROR.
8. Ubah Jabatan di Preview → role berubah otomatis.
9. Ubah Program Studi di Preview → relasi induk berubah otomatis.
10. Email duplikat → tidak boleh dibuat.
11. NUPTK duplikat jika diisi → tidak boleh dibuat.
12. Baris ERROR dikecualikan → baris VALID tetap dapat diproses.
13. Password otomatis menghasilkan kredensial unik.
14. Password manual wajib divalidasi sebelum ACC.
15. ACC berulang tidak membuat akun duplikat.
16. SUPER ADMIN tidak dapat mengedit atau menghapus akun setelah dibuat.
17. ADMIN dapat mengelola akun operasional sesuai kewenangannya.
18. Tidak ada cara membuat akun ADMIN atau SUPER ADMIN melalui fitur manual maupun Excel.
19. Organisasi baru yang ditambahkan ADMIN otomatis tersedia pada form dan Preview tanpa mengubah kode program.

## 13. Hasil Akhir yang Diharapkan

Sediakan dua cara pembuatan akun yang berjalan berdampingan.

**A. Pembuatan Manual**

Pengguna membuka form, mengisi data, memilih Jabatan resmi, memilih organisasi sesuai jabatan, kemudian menyimpan akun.

**B. Pembuatan Massal**

Upload Excel → sistem membaca data → role ditentukan otomatis berdasarkan Jabatan → organisasi dicocokkan dengan Master Data Organisasi → Preview dapat diedit → pengguna memilih metode password → pengguna menekan ACC → sistem membuat akun.

Jabatan resmi yang diterima hanya:
- Dekan
- Wakil Dekan
- Ketua Jurusan
- Ketua Program Studi

Tidak ada pemilihan role manual pada proses import Excel. Role harus ditentukan otomatis berdasarkan Jabatan dan ditampilkan pada Preview.

Pastikan seluruh proses konsisten antara form manual, import Excel, database, Master Data Organisasi, dan hak akses aplikasi.