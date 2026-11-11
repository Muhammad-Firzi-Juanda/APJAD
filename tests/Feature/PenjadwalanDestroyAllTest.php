<?php

namespace Tests\Feature;

use App\Models\MataKuliahDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PenjadwalanDestroyAllTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function superAdmin(): User
    {
        return User::where('role', 'Fakultas')->firstOrFail();
    }

    public function test_can_destroy_all_mata_kuliah_and_schedules(): void
    {
        $mk = MataKuliahDetail::create([
            'kode_mk' => 'TEST999',
            'nama_mk' => 'Test Course',
            'sks' => 3,
            'semester' => 2,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('mata_kuliah_details', ['kode_mk' => 'TEST999']);

        $response = $this->actingAs($this->superAdmin())
            ->delete(route('penjadwalan.destroy-all'), [
                'scope' => 'all',
            ]);

        $response->assertRedirect(route('penjadwalan.index'));
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseMissing('mata_kuliah_details', ['kode_mk' => 'TEST999']);
        $this->assertEquals(0, MataKuliahDetail::count());
    }

    public function test_can_destroy_only_generated_schedules(): void
    {
        $mk = MataKuliahDetail::create([
            'kode_mk' => 'TEST888',
            'nama_mk' => 'Test Course 2',
            'sks' => 3,
            'semester' => 2,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'is_active' => true,
        ]);

        $rombelId = DB::table('rombels')->insertGetId([
            'mata_kuliah_detail_id' => $mk->id,
            'kode_rombel' => 'TEST888-A',
            'nomor_rombel' => 1,
            'kapasitas' => 40,
            'hari' => 'Senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:30:00',
            'peran_dosen' => 'Ketua',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ruanganId = DB::table('ruangans')->insertGetId([
            'kode' => 'R101',
            'nama' => 'Ruang 101',
            'kapasitas' => 45,
            'tipe' => 'Ruang Kelas',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dosenId = DB::table('dosens')->insertGetId([
            'nama' => 'Dosen Test',
            'jabatan' => 'Ketua',
            'prodi' => 'Teknik Informatika',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('jadwals')->insert([
            'rombel_id' => $rombelId,
            'ruangan_id' => $ruanganId,
            'dosen_id' => $dosenId,
            'hari' => 'Senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:30:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals(1, DB::table('jadwals')->count());

        $response = $this->actingAs($this->superAdmin())
            ->delete(route('penjadwalan.destroy-all'), [
                'scope' => 'jadwal_only',
            ]);

        $response->assertRedirect(route('penjadwalan.index'));
        $response->assertSessionHas('flash_success');

        // Jadwal & rombel terhapus, tetapi mata kuliah tetap ada
        $this->assertEquals(0, DB::table('jadwals')->count());
        $this->assertDatabaseHas('mata_kuliah_details', ['kode_mk' => 'TEST888']);
    }
}
