<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\MataKuliahDetail;
use App\Models\Ruangan;
use Illuminate\Database\Seeder;

class JadwalKuliahSchedulerSeeder extends Seeder
{
    public function run(): void
    {
        $this->ruangan();
        $this->dosen();
        $this->mataKuliah();
    }

    /** 11 ruang kelas + 2 laboratorium. Semester ini hanya ruang 4, 5, 6 yang aktif. */
    protected function ruangan(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            Ruangan::updateOrCreate(
                ['kode' => "R$i"],
                [
                    'nama' => "Ruang Kelas $i",
                    'tipe' => 'Ruang Kelas',
                    'kapasitas' => 30,
                    'gedung' => 'Teknik',
                    'lantai' => (intdiv($i - 1, 4) + 1),
                    'is_active' => in_array($i, [4, 5, 6], true) ? 1 : 0,
                ]
            );
        }

        for ($i = 1; $i <= 2; $i++) {
            Ruangan::updateOrCreate(
                ['kode' => "LAB$i"],
                [
                    'nama' => "Laboratorium $i",
                    'tipe' => 'Lab',
                    'kapasitas' => 30,
                    'gedung' => 'Teknik',
                    'lantai' => 1,
                    'is_active' => in_array($i, [1, 2], true) ? 1 : 0,
                ]
            );
        }
    }

    protected function dosen(): void
    {
        $daftar = [
            ['NIP001', 'Dr. Budi Santoso, S.T., M.T.', 'Ketua', 'Teknik Informatika'],
            ['NIP002', 'Rina Wijaya, S.T., M.Kom.', 'Ketua', 'Teknik Informatika'],
            ['NIP003', 'Agus Pratama, S.Kom., M.Cs.', 'Anggota', 'Teknik Informatika'],
            ['NIP004', 'Dewi Lestari, S.T., M.T.', 'Anggota', 'Teknik Informatika'],
            ['NIP005', 'Fajar Hidayat, S.Kom., M.M.', 'Anggota', 'Teknik Informatika'],
            ['NIP006', 'Siti Aminah, S.T., M.Eng.', 'Ketua', 'Teknik Informatika'],
            ['NIP007', 'Andi Saputra, S.Kom.', 'Anggota', 'Teknik Informatika'],
            ['NIP008', 'Maya Puspita, S.T., M.T.', 'Anggota', 'Teknik Informatika'],
        ];

        foreach ($daftar as [$nip, $nama, $jabatan, $prodi]) {
            Dosen::updateOrCreate(
                ['nip' => $nip],
                ['nama' => $nama, 'jabatan' => $jabatan, 'prodi' => $prodi, 'is_active' => 1]
            );
        }
    }

    protected function mataKuliah(): void
    {
        $dosen = Dosen::pluck('id', 'nip');

        $daftar = [
            // Semester 1
            ['IF1401', 'Kalkulus 1',            3, 1, 'Wajib',   'NIP001', 'NIP003', 2, 30, 0],
            ['IF1402', 'Pengantar Pemrograman', 3, 1, 'Wajib',   'NIP002', 'NIP004', 2, 30, 1],
            ['IF1403', 'Fisika Dasar',          2, 1, 'Wajib',   'NIP006', 'NIP005', 1, 30, 0],
            ['IF1404', 'Matematika Diskret',    3, 1, 'Wajib',   'NIP003', 'NIP007', 1, 30, 0],
            ['IF1405', 'Olahraga',              1, 1, 'Pilihan', 'NIP007', null,     1, 30, 0],
            ['IF1406', 'Bahasa Inggris Teknis', 2, 1, 'Pilihan', 'NIP004', null,     1, 30, 0],

            // Semester 3
            ['IF2401', 'Struktur Data',         3, 3, 'Wajib',   'NIP002', 'NIP003', 2, 30, 0],
            ['IF2402', 'Sistem Basis Data',     3, 3, 'Wajib',   'NIP001', 'NIP005', 2, 30, 1],
            ['IF2403', 'Algoritma Pemrograman', 3, 3, 'Wajib',   'NIP002', 'NIP007', 1, 30, 1],
            ['IF2404', 'Organisasi Komputer',    3, 3, 'Wajib',   'NIP006', 'NIP008', 1, 30, 0],
            ['IF2405', 'Jaringan Komputer',      3, 3, 'Wajib',   'NIP001', 'NIP004', 1, 30, 1],
            ['IF2406', 'Kewirausahaan',         2, 3, 'Pilihan', 'NIP005', null,     1, 30, 0],
            ['IF2407', 'Etika Profesi',         2, 3, 'Pilihan', 'NIP008', null,     1, 30, 0],

            // Semester 5
            ['IF3401', 'Jaringan Syaraf Tiruan', 3, 5, 'Wajib',   'NIP002', 'NIP003', 1, 30, 1],
            ['IF3402', 'Kecerdasan Artifisial',  3, 5, 'Wajib',   'NIP001', 'NIP005', 1, 30, 1],
            ['IF3403', 'Basis Data Lanjut',      3, 5, 'Wajib',   'NIP006', 'NIP004', 1, 30, 0],
            ['IF3404', 'Keamanan Sistem',        3, 5, 'Wajib',   'NIP003', 'NIP007', 1, 30, 0],
            ['IF3405', 'Pemrograman Berorientasi Objek', 3, 5, 'Wajib', 'NIP002', 'NIP008', 1, 30, 1],
            ['IF3406', 'Manajemen Proyek TI',    3, 5, 'Pilihan', 'NIP004', null,     1, 30, 0],
            ['IF3407', 'Tata Kelola TI',         3, 5, 'Pilihan', 'NIP007', null,     1, 30, 0],

            // Semester 7
            ['IF4401', 'Data Mining',            3, 7, 'Wajib',   'NIP001', 'NIP005', 1, 30, 1],
            ['IF4402', 'Komputasi Awan',         3, 7, 'Wajib',   'NIP006', 'NIP004', 1, 30, 0],
            ['IF4403', 'Kecerdasan Buatan',      3, 7, 'Wajib',   'NIP002', 'NIP003', 1, 30, 0],
            ['IF4404', 'Analisis & Perancangan Sistem', 3, 7, 'Wajib', 'NIP005', 'NIP008', 1, 30, 0],
            ['IF4405', 'Tugas Akhir',            4, 7, 'Wajib',   'NIP003', 'NIP007', 1, 20, 0],
            ['IF4406', 'Ujian Akhir',            2, 7, 'Pilihan', 'NIP008', null,     1, 30, 0],
        ];

        foreach ($daftar as [$kode, $nama, $sks, $semester, $tipe, $ketua, $anggota, $jml, $kap, $lab]) {
            MataKuliahDetail::updateOrCreate(
                ['kode_mk' => $kode],
                [
                    'nama_mk' => $nama,
                    'sks' => $sks,
                    'semester' => $semester,
                    'tipe' => $tipe,
                    'prodi' => 'Teknik Informatika',
                    'dosen_ketua_id' => $dosen[$ketua] ?? null,
                    'dosen_anggota_id' => $dosen[$anggota] ?? null,
                    'jumlah_kelas' => $jml,
                    'kapasitas_per_kelas' => $kap,
                    'butuh_lab' => $lab,
                    'is_active' => 1,
                ]
            );
        }
    }
}
