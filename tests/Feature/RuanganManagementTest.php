<?php

namespace Tests\Feature;

use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuanganManagementTest extends TestCase
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

    public function test_super_admin_cannot_access_ruangan(): void
    {
        $superAdmin = User::where('role', 'Super Admin')->firstOrFail();
        $this->actingAs($superAdmin)->get('/ruangan')->assertForbidden();
    }

    public function test_super_admin_can_add_new_room(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post('/ruangan', [
                'kode' => 'R-TEST-99',
                'nama' => 'Ruang Uji Coba 99',
                'tipe' => 'Ruang Kelas',
                'kapasitas' => 45,
                'gedung' => 'Gedung FTTK',
                'lantai' => '2',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('penjadwalan.index'));
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('ruangans', [
            'kode' => 'R-TEST-99',
            'nama' => 'Ruang Uji Coba 99',
            'kapasitas' => 45,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_update_existing_room(): void
    {
        $ruangan = Ruangan::create([
            'kode' => 'R-CUSTOM-01',
            'nama' => 'Ruang Custom Awal',
            'tipe' => 'Ruang Kelas',
            'kapasitas' => 30,
            'gedung' => 'Gedung B',
            'lantai' => '1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->put("/ruangan/{$ruangan->id}", [
                'kode' => 'R-CUSTOM-01',
                'nama' => 'Ruang Custom Diperbarui',
                'tipe' => 'Lab',
                'kapasitas' => 35,
                'gedung' => 'Gedung B',
                'lantai' => '1',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('penjadwalan.index'));
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('ruangans', [
            'id' => $ruangan->id,
            'nama' => 'Ruang Custom Diperbarui',
            'tipe' => 'Lab',
            'kapasitas' => 35,
        ]);
    }

    public function test_super_admin_can_toggle_room_status(): void
    {
        $ruangan = Ruangan::where('is_active', true)->firstOrFail();

        $response = $this->actingAs($this->superAdmin())
            ->post("/ruangan/{$ruangan->id}/toggle-status");

        $response->assertRedirect(route('penjadwalan.index'));
        $this->assertDatabaseHas('ruangans', [
            'id' => $ruangan->id,
            'is_active' => false,
        ]);

        // Toggle back to active
        $response2 = $this->actingAs($this->superAdmin())
            ->post("/ruangan/{$ruangan->id}/toggle-status");

        $response2->assertRedirect(route('penjadwalan.index'));
        $this->assertDatabaseHas('ruangans', [
            'id' => $ruangan->id,
            'is_active' => true,
        ]);
    }

    public function test_default_campus_rooms_cannot_be_deleted(): void
    {
        $defaultRoom = Ruangan::where('kode', 'R1')->firstOrFail();

        $response = $this->actingAs($this->superAdmin())
            ->delete("/ruangan/{$defaultRoom->id}");

        $response->assertRedirect(route('penjadwalan.index'));
        $response->assertSessionHas('flash_error');

        $this->assertDatabaseHas('ruangans', [
            'id' => $defaultRoom->id,
            'kode' => 'R1',
        ]);
    }

    public function test_custom_room_can_be_deleted(): void
    {
        $customRoom = Ruangan::create([
            'kode' => 'R-HAPUS-99',
            'nama' => 'Ruang Untuk Dihapus',
            'tipe' => 'Ruang Kelas',
            'kapasitas' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->delete("/ruangan/{$customRoom->id}");

        $response->assertRedirect(route('penjadwalan.index'));
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseMissing('ruangans', [
            'id' => $customRoom->id,
        ]);
    }

    public function test_default_campus_rooms_include_classes_labs_and_auditorium_with_correct_capacities(): void
    {
        // 12 Ruang Kelas Teori dengan kapasitas 50
        for ($i = 1; $i <= 12; $i++) {
            $room = Ruangan::where('kode', "R$i")->first();
            $this->assertNotNull($room, "Ruangan R$i harus tersedia sebagai bawaan sistem.");
            $this->assertEquals(50, $room->kapasitas, "Kapasitas R$i harus 50 kursi.");
            $this->assertTrue($room->isDefault(), "Ruangan R$i harus dikenali sebagai ruangan bawaan resmi kampus.");
        }

        // 2 Laboratorium
        for ($i = 1; $i <= 2; $i++) {
            $lab = Ruangan::where('kode', "LAB$i")->first();
            $this->assertNotNull($lab, "Ruangan LAB$i harus tersedia sebagai bawaan sistem.");
            $this->assertTrue($lab->isDefault(), "Ruangan LAB$i harus dikenali sebagai ruangan bawaan resmi kampus.");
        }

        // 1 Ruang Audit / Auditorium dengan kapasitas 200
        $audit = Ruangan::where('kode', 'AUDIT-01-FTTK')->first();
        $this->assertNotNull($audit, 'Ruang Audit/Auditorium harus tersedia sebagai bawaan sistem.');
        $this->assertEquals(200, $audit->kapasitas, 'Kapasitas Ruang Audit harus 200 kursi.');
        $this->assertTrue($audit->isDefault(), 'Ruang Audit harus dikenali sebagai ruangan bawaan resmi kampus.');
    }

    public function test_auditorium_cannot_be_deleted(): void
    {
        $audit = Ruangan::where('kode', 'AUDIT-01-FTTK')->firstOrFail();

        $response = $this->actingAs($this->superAdmin())
            ->delete("/ruangan/{$audit->id}");

        $response->assertRedirect(route('penjadwalan.index'));
        $response->assertSessionHas('flash_error');

        $this->assertDatabaseHas('ruangans', [
            'id' => $audit->id,
            'kode' => 'AUDIT-01-FTTK',
        ]);
    }
}
