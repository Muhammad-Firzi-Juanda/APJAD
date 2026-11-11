<?php

namespace Database\Seeders;

use App\Models\DosenMengajar;
use App\Models\Fakultas;
use App\Models\KelasJadwal;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\Database\Seeder;

class JadwalKuliahSeeder extends Seeder
{
    public function run(): void
    {
        // Fakultas
        $fakultasList = [
            ['nama' => 'Fakultas Teknik', 'kode' => 'FT', 'dekan' => 'Dr. Budi Santoso, M.Kom'],
            ['nama' => 'Fakultas Ekonomi & Bisnis', 'kode' => 'FEB', 'dekan' => 'Dr. Ani Wijayanti, M.Ek'],
            ['nama' => 'Fakultas Hukum', 'kode' => 'FH', 'dekan' => 'Dr. Ahmad Fauzi, M.Hum'],
            ['nama' => 'Fakultas Keguruan & Ilmu Pendidikan', 'kode' => 'FKIP', 'dekan' => 'Dr. Siti Rahayu, M.Pd'],
            ['nama' => 'Fakultas Matematika & Ilmu Pengetahuan Alam', 'kode' => 'FMIPA', 'dekan' => 'Dr. Eko Prasetyo, M.Si'],
        ];

        foreach ($fakultasList as $fak) {
            Fakultas::updateOrCreate(['nama' => $fak['nama']], $fak);
        }

        // Prodi
        $prodiList = [
            ['nama' => 'S1 Teknik Informatika', 'kode' => 'TI', 'fakultas_id' => 1, 'kaprodi' => 'Dr. Indra Kusuma, M.Kom'],
            ['nama' => 'S1 Manajemen', 'kode' => 'MNJ', 'fakultas_id' => 2, 'kaprodi' => 'Dr. Rina Fitriani, M.Ek'],
            ['nama' => 'S1 Hukum', 'kode' => 'HUK', 'fakultas_id' => 3, 'kaprodi' => 'Dr. Doni Hermawan, M.Hum'],
            ['nama' => 'S1 Pendidikan Matematika', 'kode' => 'PMat', 'fakultas_id' => 4, 'kaprodi' => 'Dr. Lily Suryani, M.Pd'],
            ['nama' => 'S1 Matematika', 'kode' => 'MTH', 'fakultas_id' => 5, 'kaprodi' => 'Dr. Bambang Prasetyo, M.Si'],
        ];

        foreach ($prodiList as $pro) {
            Prodi::updateOrCreate(['nama' => $pro['nama']], $pro);
        }

        // MataKuliah Fakultas
        $mkFakultas = [
            ['nama' => 'Pendidikan Agama Islam', 'kode' => 'PAI101', 'sks' => 3, 'semester' => 1, 'tipe' => 'Fakultas', 'level_id' => 1],
            ['nama' => 'Bahasa Indonesia', 'kode' => 'BIN101', 'sks' => 2, 'semester' => 1, 'tipe' => 'Fakultas', 'level_id' => 1],
            ['nama' => 'Bahasa Inggris', 'kode' => 'BING101', 'sks' => 2, 'semester' => 1, 'tipe' => 'Fakultas', 'level_id' => 1],
            ['nama' => 'Pancasila & Kewarganegaraan', 'kode' => 'PPKN101', 'sks' => 3, 'semester' => 1, 'tipe' => 'Fakultas', 'level_id' => 1],
            ['nama' => 'Kewirausahaan', 'kode' => 'KWR201', 'sks' => 2, 'semester' => 4, 'tipe' => 'Fakultas', 'level_id' => 1],
        ];

        foreach ($mkFakultas as $mk) {
            MataKuliah::updateOrCreate(['kode' => $mk['kode']], $mk);
        }

        // MataKuliah Prodi
        $mkProdi = [
            ['nama' => 'Algoritma & Pemrograman', 'kode' => 'TIF101', 'sks' => 3, 'semester' => 1, 'tipe' => 'Prodi', 'level_id' => 1],
            ['nama' => 'Struktur Data', 'kode' => 'TIF202', 'sks' => 3, 'semester' => 2, 'tipe' => 'Prodi', 'level_id' => 1],
            ['nama' => 'Basis Data', 'kode' => 'TIF203', 'sks' => 3, 'semester' => 3, 'tipe' => 'Prodi', 'level_id' => 1],
            ['nama' => 'Jaringan Komputer', 'kode' => 'TIF304', 'sks' => 3, 'semester' => 4, 'tipe' => 'Prodi', 'level_id' => 1],
            ['nama' => 'Keamanan Sistem Informasi', 'kode' => 'TIF405', 'sks' => 3, 'semester' => 5, 'tipe' => 'Prodi', 'level_id' => 1],
        ];

        foreach ($mkProdi as $mk) {
            MataKuliah::updateOrCreate(['kode' => $mk['kode']], $mk);
        }

        // KelasJadwal
        $kelasList = [
            ['mata_kuliah_id' => 6, 'ruang' => 'Lab Komputer 1', 'kapasitas' => 40, 'hari' => 'Senin', 'jam_mulai' => '07:30:00', 'jam_selesai' => '09:30:00', 'kode_kelas' => 'TI-A'],
            ['mata_kuliah_id' => 6, 'ruang' => 'Lab Komputer 1', 'kapasitas' => 40, 'hari' => 'Rabu', 'jam_mulai' => '10:00:00', 'jam_selesai' => '12:00:00', 'kode_kelas' => 'TI-B'],
            ['mata_kuliah_id' => 7, 'ruang' => 'Ruang 301', 'kapasitas' => 35, 'hari' => 'Selasa', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'kode_kelas' => 'TI-A'],
            ['mata_kuliah_id' => 8, 'ruang' => 'Ruang 302', 'kapasitas' => 30, 'hari' => 'Kamis', 'jam_mulai' => '13:00:00', 'jam_selesai' => '15:00:00', 'kode_kelas' => 'TI-A'],
        ];

        foreach ($kelasList as $kelas) {
            KelasJadwal::updateOrCreate(
                ['mata_kuliah_id' => $kelas['mata_kuliah_id'], 'kode_kelas' => $kelas['kode_kelas']],
                $kelas
            );
        }

        // DosenMengajar
        $dosenList = [
            ['nama_dosen' => 'Dr. Ahmad Rizal, M.Kom', 'nip' => '0012345678', 'kelas_jadwal_id' => 1, 'status' => 'Aktif', 'tanggal_mulai' => '2024-09-01'],
            ['nama_dosen' => 'Dr. Siti Nurhaliza, M.T.', 'nip' => '0023456789', 'kelas_jadwal_id' => 2, 'status' => 'Aktif', 'tanggal_mulai' => '2024-09-01'],
            ['nama_dosen' => 'Dr. Budi Santoso, M.Kom', 'nip' => '0034567890', 'kelas_jadwal_id' => 3, 'status' => 'Aktif', 'tanggal_mulai' => '2024-09-01'],
            ['nama_dosen' => 'Dr. Eka Putri, M.Si', 'nip' => '0045678901', 'kelas_jadwal_id' => 4, 'status' => 'Aktif', 'tanggal_mulai' => '2024-09-01'],
        ];

        foreach ($dosenList as $dosen) {
            DosenMengajar::updateOrCreate(
                ['nama_dosen' => $dosen['nama_dosen'], 'nip' => $dosen['nip']],
                $dosen
            );
        }
    }
}
