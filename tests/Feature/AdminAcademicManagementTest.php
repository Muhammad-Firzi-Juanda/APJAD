<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAcademicManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $superAdminUser;

    protected User $prodiUser;

    protected Fakultas $fakultas;

    protected Jurusan $jurusan;

    protected Prodi $prodiTI;

    protected Prodi $prodiTE;

    protected Dosen $dosenTI;

    protected Dosen $dosenTE;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakultas = Fakultas::create([
            'kode' => 'FTTK',
            'nama' => 'Fakultas Teknik dan Teknologi Kemaritiman',
            'status' => 'ACTIVE',
        ]);

        $this->jurusan = Jurusan::create([
            'fakultas_id' => $this->fakultas->id,
            'kode' => 'JTSI',
            'nama' => 'Teknik Elektro dan Informatika',
            'status' => 'ACTIVE',
        ]);

        $this->prodiTI = Prodi::create([
            'jurusan_id' => $this->jurusan->id,
            'kode' => 'TI',
            'nama' => 'Teknik Informatika',
            'jenjang' => 'S1',
            'status' => 'ACTIVE',
        ]);

        $this->prodiTE = Prodi::create([
            'jurusan_id' => $this->jurusan->id,
            'kode' => 'TE',
            'nama' => 'Teknik Elektro',
            'jenjang' => 'S1',
            'status' => 'ACTIVE',
        ]);

        $this->dosenTI = Dosen::create([
            'nama' => 'Dosen TI Satu, M.Kom',
            'nip' => '198801012015011001',
            'inisial' => 'DT1',
            'prodi' => 'Teknik Informatika',
            'jabatan' => 'Ketua & Anggota',
            'status' => 'ACTIVE',
        ]);

        $this->dosenTE = Dosen::create([
            'nama' => 'Dosen TE Satu, M.T',
            'nip' => '198901012015011002',
            'inisial' => 'DE1',
            'prodi' => 'Teknik Elektro',
            'jabatan' => 'Ketua & Anggota',
            'status' => 'ACTIVE',
        ]);

        $this->adminUser = User::create([
            'nama' => 'Admin Akademik Test',
            'email' => 'admin.akademik@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
        ]);

        $this->superAdminUser = User::create([
            'nama' => 'Super Admin Test',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::SUPER_ADMIN,
        ]);

        $this->prodiUser = User::create([
            'nama' => 'Admin Prodi TI',
            'email' => 'prodi.ti@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::PRODI,
            'prodi_id' => $this->prodiTI->id,
        ]);
    }

    public function test_admin_can_view_prodi_courses_page_with_filtered_lecturers(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.mata-kuliah.prodi', ['prodi' => $this->prodiTI->id]));

        $response->assertOk();
        $response->assertSee('Mata Kuliah Teknik Informatika');
        $response->assertSee('Dosen TI Satu, M.Kom');
        // Dosen prodi TE tidak boleh muncul di dropdown pilihan dosen prodi TI
        $response->assertDontSee('Dosen TE Satu, M.T');
    }

    public function test_admin_can_store_course_for_prodi_with_correct_lecturer(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.mata-kuliah.prodi.store', ['prodi' => $this->prodiTI->id]), [
                'kode_mk' => 'TIF101',
                'nama_mk' => 'Pemrograman Dasar',
                'sks' => 3,
                'semester' => 1,
                'tipe' => 'Wajib',
                'dosen_ketua_id' => $this->dosenTI->id,
                'jumlah_kelas' => 2,
                'kapasitas_per_kelas' => 35,
                'butuh_lab' => 1,
                'tanpa_ruangan' => 0,
            ]);

        $response->assertRedirect(route('admin.mata-kuliah.prodi', ['prodi' => $this->prodiTI->id]));
        $this->assertDatabaseHas('mata_kuliah_details', [
            'kode_mk' => 'TIF101',
            'nama_mk' => 'Pemrograman Dasar',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $this->dosenTI->id,
            'sks' => 3,
            'butuh_lab' => 1,
        ]);
    }

    public function test_admin_cannot_assign_lecturer_from_different_prodi(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.mata-kuliah.prodi.store', ['prodi' => $this->prodiTI->id]), [
                'kode_mk' => 'TIF102',
                'nama_mk' => 'Algoritma & Struktur Data',
                'sks' => 3,
                'semester' => 2,
                'tipe' => 'Wajib',
                'dosen_ketua_id' => $this->dosenTE->id, // Dosen Elektro tidak boleh di prodi TI
                'jumlah_kelas' => 1,
                'kapasitas_per_kelas' => 30,
            ]);

        $response->assertSessionHasErrors('dosen_ketua_id');
        $this->assertDatabaseMissing('mata_kuliah_details', [
            'kode_mk' => 'TIF102',
        ]);
    }

    public function test_admin_can_view_prodi_schedule_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.jadwal.prodi', ['prodi' => $this->prodiTI->id]));

        $response->assertOk();
        $response->assertSee('Jadwal Kuliah Teknik Informatika');
    }
}
