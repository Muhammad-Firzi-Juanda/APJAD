<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\MataKuliahDetail;
use App\Models\Ruangan;
use App\Services\GeneticSchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneticSchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed dosen
        $dosen = Dosen::create([
            'nama' => 'Dr. Budi Santoso',
            'inisial' => 'BS',
            'email' => 'budi@kampus.ac.id',
            'jabatan' => 'Ketua & Anggota',
            'is_active' => true,
        ]);

        // Seed ruangan teori (non-lab)
        Ruangan::create([
            'kode' => 'R01-FTTK',
            'nama' => 'Ruang 01',
            'tipe' => 'Ruang Kelas',
            'kapasitas' => 50,
            'is_active' => true,
        ]);

        // Seed mata kuliah wajib
        MataKuliahDetail::create([
            'kode_mk' => 'IF2101',
            'nama_mk' => 'Algoritma dan Struktur Data',
            'sks' => 3,
            'semester' => 3,
            'tipe' => 'Wajib',
            'prodi' => 'Informatika',
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'butuh_lab' => false,
            'is_active' => true,
            'status_tawar' => true,
            'dosen_ketua_id' => $dosen->id,
        ]);
    }

    public function test_genetic_scheduler_generates_schedule_with_ga_telemetry(): void
    {
        $service = app(GeneticSchedulerService::class);
        $hasil = $service->generate(3, 'Informatika');

        $this->assertGreaterThan(0, $hasil['jumlah_jadwal']);
        $this->assertNotEmpty($hasil['log']);

        // Pastikan telemetri GA engine tercatat di log awal
        $gaLog = $hasil['log'][0];
        $this->assertStringContainsString('[GA Engine]', $gaLog);
        $this->assertStringContainsString('Fitness Terbaik', $gaLog);
    }
}
