<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Jadwal;
use App\Models\MataKuliahDetail;
use App\Models\Prodi;
use App\Models\Rombel;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SynchronizedRoleScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_initials_matching_excel_mapping(): void
    {
        $dosenPA = Dosen::create([
            'nama' => 'Pipin Armita, M.Pd.I',
            'jabatan' => 'Ketua & Anggota',
            'prodi' => 'Teknik Informatika',
            'is_active' => true,
        ]);
        $this->assertEquals('PA', $dosenPA->inisial);

        $dosenSM = Dosen::create([
            'nama' => 'Dr. Suparman Manjan, Lc., M.Ed',
            'jabatan' => 'Ketua & Anggota',
            'prodi' => 'Teknik Informatika',
            'is_active' => true,
        ]);
        $this->assertEquals('SM', $dosenSM->inisial);

        $dosenMM = Dosen::create([
            'nama' => 'Muhamad Mujahidin, S.T., M.T',
            'jabatan' => 'Ketua & Anggota',
            'prodi' => 'Teknik Elektro',
            'is_active' => true,
        ]);
        $this->assertEquals('MM', $dosenMM->inisial);

        $dosenRBP = Dosen::create([
            'nama' => 'Robby Patria',
            'jabatan' => 'Ketua & Anggota',
            'prodi' => 'Teknik Informatika',
            'is_active' => true,
        ]);
        $this->assertEquals('RBP', $dosenRBP->inisial);

        $dosenDN = Dosen::create([
            'nama' => 'Deny Nusyirwan',
            'jabatan' => 'Ketua & Anggota',
            'prodi' => 'Teknik Informatika',
            'is_active' => true,
        ]);
        $this->assertEquals('DN', $dosenDN->inisial);

        $dosenNF = Dosen::create([
            'nama' => 'Nurfalinda',
            'jabatan' => 'Ketua & Anggota',
            'prodi' => 'Teknik Informatika',
            'is_active' => true,
        ]);
        $this->assertEquals('NF', $dosenNF->inisial);
    }

    public function test_clean_kode_mk_and_kelas_label_accessors(): void
    {
        $mk = MataKuliahDetail::create([
            'kode_mk' => 'INF11104-A',
            'nama_mk' => 'Sistem Basis Data',
            'sks' => 3,
            'semester' => 2,
            'kelas' => 'A',
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'is_active' => true,
        ]);

        $this->assertEquals('INF11104', $mk->clean_kode_mk);
        $this->assertEquals('A', $mk->kelas_label);

        $rombel = Rombel::create([
            'mata_kuliah_detail_id' => $mk->id,
            'kode_rombel' => 'INF11104-A',
            'nomor_rombel' => 1,
            'kelas' => 'A',
            'kapasitas' => 40,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '10:00',
            'is_active' => true,
        ]);

        $this->assertEquals('INF11104', $rombel->clean_kode_mk);
        $this->assertEquals('A', $rombel->kelas_label);

        $dosen = Dosen::create([
            'nama' => 'Pipin Armita, M.Pd.I',
            'jabatan' => 'Ketua & Anggota',
            'prodi' => 'Teknik Informatika',
            'is_active' => true,
        ]);

        $ruangan = Ruangan::create([
            'kode' => 'R-01',
            'nama' => 'Ruang 1',
            'kapasitas' => 50,
            'tipe' => 'Ruang Kelas',
            'is_active' => true,
        ]);

        $jadwal = Jadwal::create([
            'rombel_id' => $rombel->id,
            'ruangan_id' => $ruangan->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '10:00',
            'is_active' => true,
        ]);

        $this->assertEquals('INF11104', $jadwal->clean_kode_mk);
        $this->assertEquals('A', $jadwal->kelas_label);
        $this->assertEquals('PA', $jadwal->dosen_inisial);
    }

    public function test_all_roles_have_synchronized_schedule_view(): void
    {
        $fakultas = Fakultas::create([
            'nama' => 'Fakultas Teknik dan Teknologi Kemaritiman',
            'kode' => 'FTTK',
        ]);

        $prodi = Prodi::create([
            'nama' => 'Teknik Informatika',
            'kode' => 'TI',
            'fakultas_id' => $fakultas->id,
        ]);

        $dosen = Dosen::create([
            'nama' => 'Dr. Suparman Manjan, Lc., M.Ed',
            'jabatan' => 'Ketua & Anggota',
            'prodi' => 'Teknik Informatika',
            'is_active' => true,
        ]);

        $mk = MataKuliahDetail::create([
            'kode_mk' => 'UNV12101-A',
            'nama_mk' => 'Agama',
            'sks' => 2,
            'semester' => 2,
            'kelas' => 'A',
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'is_active' => true,
        ]);

        $rombel = Rombel::create([
            'mata_kuliah_detail_id' => $mk->id,
            'kode_rombel' => 'UNV12101-A',
            'nomor_rombel' => 1,
            'kelas' => 'A',
            'kapasitas' => 40,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:10',
            'is_active' => true,
        ]);

        $ruangan = Ruangan::create([
            'kode' => 'AUDIT-01',
            'nama' => 'Auditorium',
            'kapasitas' => 200,
            'tipe' => 'Aula',
            'is_active' => true,
        ]);

        Jadwal::create([
            'rombel_id' => $rombel->id,
            'ruangan_id' => $ruangan->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:10',
            'is_active' => true,
        ]);

        // 1. Super Admin Role
        $superAdmin = User::create([
            'nama' => 'Super Admin Test',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SUPER_ADMIN,
        ]);
        // Sesuai Prompt1.md: Super Admin dilarang mengakses modul penjadwalan
        $responseAdmin = $this->actingAs($superAdmin)->get(route('penjadwalan.index'));
        $responseAdmin->assertForbidden();

        // 2. Fakultas Role
        $userFakultas = User::create([
            'nama' => 'Admin Fakultas Test',
            'email' => 'fakultas@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::FAKULTAS,
            'fakultas_id' => $fakultas->id,
        ]);
        $responseFakultas = $this->actingAs($userFakultas)->get(route('fakultas.dashboard'));
        $responseFakultas->assertOk();
        $responseFakultas->assertSee('UNV12101');
        $responseFakultas->assertSee('SM');

        // 3. Jurusan Role
        $userJurusan = User::create([
            'nama' => 'Admin Jurusan Test',
            'email' => 'jurusan@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::JURUSAN,
        ]);
        $responseJurusan = $this->actingAs($userJurusan)->get(route('jurusan.dashboard'));
        $responseJurusan->assertOk();
        $responseJurusan->assertSee('UNV12101');
        $responseJurusan->assertSee('SM');

        // 4. Prodi Role
        $userProdi = User::create([
            'nama' => 'Admin Prodi Test',
            'email' => 'prodi@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::PRODI,
            'prodi_id' => $prodi->id,
        ]);
        $responseProdi = $this->actingAs($userProdi)->get(route('prodi.dashboard'));
        $responseProdi->assertOk();
        $responseProdi->assertSee('UNV12101');
        $responseProdi->assertSee('SM');
    }

    public function test_jurusan_dashboard_displays_kode_dosen_in_data_dosen_table(): void
    {
        Dosen::query()->delete();
        $dosen = Dosen::create([
            'nidn' => '0011223344',
            'nama' => 'Prof. Dr. Ir. Budi Santoso, M.Kom.',
            'inisial' => 'BS',
            'email' => 'budi@kampus.ac.id',
            'jabatan' => 'Ketua',
        ]);

        $userJurusan = User::create([
            'nama' => 'Admin Jurusan IT',
            'email' => 'jurusan_it@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::JURUSAN,
        ]);

        $response = $this->actingAs($userJurusan)->get(route('jurusan.dashboard'));
        $response->assertOk();
        $response->assertSee('Kode Dosen');
        $response->assertSee('BS');
        $response->assertSee('Prof. Dr. Ir. Budi Santoso, M.Kom.');
        // Penjadwalan should not exist in Jurusan navigation
        $response->assertDontSee('<span>Penjadwalan</span>', false);
    }

    public function test_jurusan_cannot_access_penjadwalan_or_ruangan(): void
    {
        $userJurusan = User::create([
            'nama' => 'Admin Jurusan Penjadwalan',
            'email' => 'jurusan_sched@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::JURUSAN,
        ]);

        // 1. Jurusan cannot access penjadwalan page (forbidden)
        $response = $this->actingAs($userJurusan)->get(route('penjadwalan.index'));
        $response->assertForbidden();

        // 2. Jurusan cannot access ruangan index (forbidden)
        $responseRuangan = $this->actingAs($userJurusan)->get(route('ruangan.index'));
        $responseRuangan->assertForbidden();
    }
}
