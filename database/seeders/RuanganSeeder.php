<?php

namespace Database\Seeders;

use App\Models\Prodi;
use App\Models\Ruangan;
use Illuminate\Database\Seeder;

class RuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Ruangan bawaan resmi kampus:
     * - 12 Ruang Kelas Teori (Kapasitas: 50 kursi)
     * - Ruang Laboratorium per Prodi (TI, TE, TP, TIND, KIM, TS, PWK, ARS, dan Bersama)
     * - 1 Ruang Audit / Auditorium (Kapasitas: 200 kursi)
     */
    public function run(): void
    {
        // 12 Ruang Kelas Teori
        for ($i = 1; $i <= 12; $i++) {
            Ruangan::updateOrCreate(
                ['kode' => "R$i"],
                [
                    'nama' => "Ruang Kelas $i",
                    'tipe' => 'Ruang Kelas',
                    'kapasitas' => 50,
                    'gedung' => 'Teknik',
                    'lantai' => (string) (intdiv($i - 1, 4) + 1),
                    'is_active' => true,
                    'prodi_id' => null,
                ]
            );
        }

        // Ambil ID Prodi yang ada di database
        $prodiTI = Prodi::where('kode', 'TI')->orWhere('nama', 'like', '%Informatika%')->first();
        $prodiTE = Prodi::where('kode', 'TE')->orWhere('nama', 'like', '%Elektro%')->first();
        $prodiTP = Prodi::where('kode', 'TP')->orWhere('nama', 'like', '%Perkapalan%')->first();
        $prodiTIND = Prodi::where('kode', 'TIND')->orWhere('nama', 'like', '%Industri%')->first();
        $prodiKIM = Prodi::where('kode', 'KIM')->orWhere('nama', 'like', '%Kimia%')->first();
        $prodiTS = Prodi::where('kode', 'TS')->orWhere('nama', 'like', '%Sipil%')->first();
        $prodiPWK = Prodi::where('kode', 'PWK')->orWhere('nama', 'like', '%Wilayah%')->first();
        $prodiARS = Prodi::where('kode', 'ARS')->orWhere('nama', 'like', '%Arsitektur%')->first();

        // Inisialisasi Laboratorium Berdasarkan Program Studi
        $labProdiList = [
            // Teknik Informatika
            [
                'kode' => 'LAB-TIF-01',
                'nama' => 'Lab Rekayasa Perangkat Lunak (Informatika)',
                'tipe' => 'Lab',
                'kapasitas' => 40,
                'gedung' => 'Gedung TI & Elektro',
                'lantai' => '2',
                'is_active' => true,
                'prodi_id' => $prodiTI?->id,
            ],
            [
                'kode' => 'LAB-TIF-02',
                'nama' => 'Lab Jaringan & Sistem Cerdas (Informatika)',
                'tipe' => 'Lab',
                'kapasitas' => 40,
                'gedung' => 'Gedung TI & Elektro',
                'lantai' => '2',
                'is_active' => true,
                'prodi_id' => $prodiTI?->id,
            ],
            // Teknik Elektro
            [
                'kode' => 'LAB-TE-01',
                'nama' => 'Lab Elektronika Dasar & Telekomunikasi (Elektro)',
                'tipe' => 'Lab',
                'kapasitas' => 35,
                'gedung' => 'Gedung TI & Elektro',
                'lantai' => '1',
                'is_active' => true,
                'prodi_id' => $prodiTE?->id,
            ],
            [
                'kode' => 'LAB-TE-02',
                'nama' => 'Lab Sistem Tenaga Listrik & Kendali (Elektro)',
                'tipe' => 'Lab',
                'kapasitas' => 35,
                'gedung' => 'Gedung TI & Elektro',
                'lantai' => '1',
                'is_active' => true,
                'prodi_id' => $prodiTE?->id,
            ],
            // Teknik Perkapalan
            [
                'kode' => 'LAB-TP-01',
                'nama' => 'Lab Desain & Hidrodinamika (Teknik Perkapalan)',
                'tipe' => 'Lab',
                'kapasitas' => 35,
                'gedung' => 'Gedung Maritim',
                'lantai' => '1',
                'is_active' => true,
                'prodi_id' => $prodiTP?->id,
            ],
            // Teknik Industri
            [
                'kode' => 'LAB-TIND-01',
                'nama' => 'Lab Ergonomi & Sistem Produksi (Teknik Industri)',
                'tipe' => 'Lab',
                'kapasitas' => 35,
                'gedung' => 'Gedung Teknik',
                'lantai' => '2',
                'is_active' => true,
                'prodi_id' => $prodiTIND?->id,
            ],
            // S1 Kimia
            [
                'kode' => 'LAB-KIM-01',
                'nama' => 'Lab Kimia Dasar & Analitik (Kimia)',
                'tipe' => 'Lab',
                'kapasitas' => 35,
                'gedung' => 'Gedung Sains',
                'lantai' => '1',
                'is_active' => true,
                'prodi_id' => $prodiKIM?->id,
            ],
            // S1 Teknik Sipil
            [
                'kode' => 'LAB-TS-01',
                'nama' => 'Lab Uji Material & Mekanika Struktur (Sipil)',
                'tipe' => 'Lab',
                'kapasitas' => 35,
                'gedung' => 'Gedung Teknik Sipil',
                'lantai' => '1',
                'is_active' => true,
                'prodi_id' => $prodiTS?->id,
            ],
            // S1 PWK
            [
                'kode' => 'LAB-PWK-01',
                'nama' => 'Lab Studio Perencanaan Spasial & SIG (PWK)',
                'tipe' => 'Lab',
                'kapasitas' => 35,
                'gedung' => 'Gedung PWK',
                'lantai' => '2',
                'is_active' => true,
                'prodi_id' => $prodiPWK?->id,
            ],
            // S1 Arsitektur
            [
                'kode' => 'LAB-ARS-01',
                'nama' => 'Lab Studio Desain & Maket (Arsitektur)',
                'tipe' => 'Lab',
                'kapasitas' => 35,
                'gedung' => 'Gedung Arsitektur',
                'lantai' => '2',
                'is_active' => true,
                'prodi_id' => $prodiARS?->id,
            ],
            // Lab Komputer Bersama FTTK (Multi-prodi)
            [
                'kode' => 'LAB-BERSAMA',
                'nama' => 'Lab Komputer Bersama FTTK',
                'tipe' => 'Lab',
                'kapasitas' => 45,
                'gedung' => 'Gedung Pusat FTTK',
                'lantai' => '1',
                'is_active' => true,
                'prodi_id' => null,
            ],
        ];

        foreach ($labProdiList as $lab) {
            Ruangan::updateOrCreate(
                ['kode' => $lab['kode']],
                $lab
            );
        }

        // Laboratorium Bersama Bawaan Resmi Kampus (LAB1 & LAB2)
        for ($i = 1; $i <= 2; $i++) {
            Ruangan::updateOrCreate(
                ['kode' => "LAB$i"],
                [
                    'nama' => "Laboratorium Bersama $i",
                    'tipe' => 'Lab',
                    'kapasitas' => 35,
                    'gedung' => 'Teknik',
                    'lantai' => '1',
                    'is_active' => true,
                    'prodi_id' => null,
                ]
            );
        }

        // 1 Ruang Audit / Auditorium Kampus
        Ruangan::updateOrCreate(
            ['kode' => 'AUDIT-01-FTTK'],
            [
                'nama' => 'Auditorium Kampus',
                'tipe' => 'Aula',
                'kapasitas' => 200,
                'gedung' => 'Utama',
                'lantai' => '1',
                'is_active' => true,
                'prodi_id' => null,
            ]
        );
    }
}
