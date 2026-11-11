<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Laboratory;
use App\Models\MataKuliahDetail;
use App\Models\Ruangan;
use App\Services\SchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotAndLaboratoryOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $fakultas = Fakultas::create([
            'kode' => 'FTTK',
            'nama' => 'Fakultas Teknik dan Teknologi Kemaritiman',
        ]);

        $dosen1 = Dosen::create([
            'nama' => 'Dosen Utama',
            'inisial' => 'DU',
            'email' => 'utama@kampus.ac.id',
            'jabatan' => 'Ketua & Anggota',
            'is_active' => true,
        ]);

        $dosen2 = Dosen::create([
            'nama' => 'Dosen Pendamping',
            'inisial' => 'DP',
            'email' => 'pendamping@kampus.ac.id',
            'jabatan' => 'Ketua & Anggota',
            'is_active' => true,
        ]);

        Ruangan::create([
            'kode' => 'R01-FTTK',
            'nama' => 'Ruang Teori 01',
            'tipe' => 'Ruang Kelas',
            'kapasitas' => 50,
            'is_active' => true,
        ]);

        Ruangan::create([
            'kode' => 'R02-FTTK',
            'nama' => 'Ruang Teori 02',
            'tipe' => 'Ruang Kelas',
            'kapasitas' => 50,
            'is_active' => true,
        ]);

        Laboratory::create([
            'faculty_id' => $fakultas->id,
            'code' => 'LAB-KOMP-01',
            'name' => 'Laboratorium Komputer Terpadu',
            'capacity' => 45,
            'lab_type' => 'Praktikum Komputer',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_mandatory_course_prioritized_in_morning_slot(): void
    {
        $dosen = Dosen::first();

        // Buat MK Wajib 3 SKS
        MataKuliahDetail::create([
            'kode_mk' => 'IF301',
            'nama_mk' => 'Pemrograman Berorientasi Objek',
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

        $service = app(SchedulerService::class);
        $hasil = $service->generate(3, 'Informatika');

        $this->assertSame(1, $hasil['jumlah_jadwal']);
        $logStr = implode("\n", $hasil['log']);

        // MK Wajib dimulai dari pagi hari (07:30)
        $this->assertStringContainsString('07:30', $logStr);
    }

    public function test_friday_prayer_hour_is_strictly_protected(): void
    {
        $service = app(SchedulerService::class);
        $slotsJumat3Sks = $service->getModularSlotMulaiForSks(3, 'Jumat');

        // Untuk MK 3 SKS (150 menit), jam 10:00 akan selesai jam 12:30 (menabrak sholat Jumat 11:30 - 13:30)
        // Maka jam 10:00 tidak boleh masuk ke slot valid hari Jumat
        $this->assertNotContains('10:00', $slotsJumat3Sks, 'Slot 10:00 Jumat harus diblokir karena menabrak sholat Jumat.');

        // Jam 07:30 selesai 10:00 (sebelum 11:30) dan jam 13:50/15:30 (setelah 13:30) diizinkan
        $this->assertContains('07:30', $slotsJumat3Sks);
    }
}
