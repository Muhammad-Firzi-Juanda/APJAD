<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengatur kapasitas bawaan sistem untuk ruangan resmi kampus:
     * - Ruang Kelas Teori: 50 kursi
     * - Ruang Audit / Auditorium: 200 kursi
     */
    public function up(): void
    {
        // 1. Perbarui kapasitas ruang kelas bawaan menjadi 50 kursi
        $kelasKodes = [];
        for ($i = 1; $i <= 12; $i++) {
            $kelasKodes[] = "R$i";
            $kelasKodes[] = sprintf('R%02d-FTTK', $i);
        }

        DB::table('ruangans')
            ->whereIn('kode', $kelasKodes)
            ->where('tipe', 'Ruang Kelas')
            ->update(['kapasitas' => 50]);

        // 2. Pastikan Ruangan Audit / Auditorium terdaftar dengan kapasitas 200 kursi
        $auditExists = DB::table('ruangans')->where('kode', 'AUDIT-01-FTTK')->first();

        if ($auditExists) {
            DB::table('ruangans')->where('kode', 'AUDIT-01-FTTK')->update([
                'nama' => 'Auditorium Kampus',
                'tipe' => 'Aula',
                'kapasitas' => 200,
                'gedung' => 'Utama',
                'lantai' => '1',
                'is_active' => 1,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('ruangans')->insert([
                'kode' => 'AUDIT-01-FTTK',
                'nama' => 'Auditorium Kampus',
                'tipe' => 'Aula',
                'kapasitas' => 200,
                'gedung' => 'Utama',
                'lantai' => '1',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $kelasKodes = [];
        for ($i = 1; $i <= 12; $i++) {
            $kelasKodes[] = "R$i";
            $kelasKodes[] = sprintf('R%02d-FTTK', $i);
        }

        DB::table('ruangans')
            ->whereIn('kode', $kelasKodes)
            ->where('tipe', 'Ruang Kelas')
            ->update(['kapasitas' => 40]);

        DB::table('ruangans')->where('kode', 'AUDIT-01-FTTK')->update([
            'kapasitas' => 100,
        ]);
    }
};
