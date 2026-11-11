<?php

namespace Database\Seeders;

use App\Models\Ruangan;
use Illuminate\Database\Seeder;

class RuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Ruangan bawaan resmi kampus:
     * - 12 Ruang Kelas Teori (Kapasitas: 50 kursi)
     * - 2 Laboratorium (Kapasitas: 35 kursi)
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
                ]
            );
        }

        // 2 Laboratorium Praktikum
        for ($i = 1; $i <= 2; $i++) {
            Ruangan::updateOrCreate(
                ['kode' => "LAB$i"],
                [
                    'nama' => "Laboratorium $i",
                    'tipe' => 'Lab',
                    'kapasitas' => 35,
                    'gedung' => 'Teknik',
                    'lantai' => '1',
                    'is_active' => true,
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
            ]
        );
    }
}
