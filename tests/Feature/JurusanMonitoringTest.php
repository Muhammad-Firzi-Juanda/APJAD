<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CatatanEvaluasiJadwal;
use App\Models\Jurusan;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurusanMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $userJtei;

    protected User $userProdiTi;

    protected User $userProdiTe;

    protected Jurusan $jtei;

    protected Jurusan $jtim;

    protected Prodi $prodiTi;

    protected Prodi $prodiTe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->jtei = Jurusan::where('id', 1)->first() ?? Jurusan::firstOrCreate(
            ['kode' => 'TEI'],
            ['nama' => 'Teknik Elektro dan Teknik Informatika', 'fakultas_id' => 1]
        );

        $this->jtim = Jurusan::where('id', 2)->first() ?? Jurusan::firstOrCreate(
            ['kode' => 'TIM'],
            ['nama' => 'Teknik Industri Maritim', 'fakultas_id' => 1]
        );

        $this->prodiTi = Prodi::where('id', 1)->first() ?? Prodi::firstOrCreate(
            ['kode' => 'TI'],
            ['nama' => 'S1 Teknik Informatika', 'jurusan_id' => $this->jtei->id, 'fakultas_id' => 1]
        );

        $this->prodiTe = Prodi::where('id', 2)->first() ?? Prodi::firstOrCreate(
            ['kode' => 'TE'],
            ['nama' => 'S1 Teknik Elektro', 'jurusan_id' => $this->jtei->id, 'fakultas_id' => 1]
        );

        $this->userJtei = User::where('email', 'rizal@fttk.ac.id')->first()
            ?? User::where('role', UserRole::JURUSAN)->first()
            ?? User::create([
                'nama' => 'Rizal Ferdi',
                'email' => 'rizal@fttk.ac.id',
                'password' => bcrypt('password123'),
                'role' => UserRole::JURUSAN,
                'jabatan' => 'Ketua Jurusan',
                'fakultas_id' => 1,
                'jurusan_id' => $this->jtei->id,
                'prodi_id' => null,
                'status' => 'ACTIVE',
            ]);

        $this->userProdiTi = User::where('email', 'prodi@apjad.com')->first()
            ?? User::create([
                'nama' => 'Admin Prodi TI',
                'email' => 'prodi@apjad.com',
                'password' => bcrypt('password123'),
                'role' => UserRole::PRODI,
                'jabatan' => 'Ketua Program Studi',
                'fakultas_id' => 1,
                'jurusan_id' => $this->jtei->id,
                'prodi_id' => $this->prodiTi->id,
                'status' => 'ACTIVE',
            ]);

        $this->userProdiTe = User::where('email', 'holland@fttk.ac.id')->first()
            ?? User::create([
                'nama' => 'Holland Budi',
                'email' => 'holland@fttk.ac.id',
                'password' => bcrypt('password123'),
                'role' => UserRole::PRODI,
                'jabatan' => 'Ketua Program Studi',
                'fakultas_id' => 1,
                'jurusan_id' => $this->jtei->id,
                'prodi_id' => $this->prodiTe->id,
                'status' => 'ACTIVE',
            ]);
    }

    public function test_admin_jurusan_jtei_can_access_dashboard_and_views_jtei_prodis(): void
    {
        $response = $this->actingAs($this->userJtei)->get(route('jurusan.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Teknik Elektro dan Teknik Informatika');
        $response->assertSee($this->prodiTi->nama);
        $response->assertSee($this->prodiTe->nama);
        $response->assertDontSee($this->jtim->nama);
    }

    public function test_admin_jurusan_jtim_shows_empty_state_safely_without_errors(): void
    {
        $jurusanKosong = Jurusan::create([
            'nama' => 'Jurusan Tanpa Prodi',
            'kode' => 'JTP',
            'fakultas_id' => 1,
            'status' => 'ACTIVE',
        ]);

        $userKosong = User::create([
            'nama' => 'Kajur Kosong',
            'email' => 'kajur_kosong@fttk.ac.id',
            'password' => bcrypt('password123'),
            'role' => UserRole::JURUSAN,
            'jabatan' => 'Ketua Jurusan',
            'fakultas_id' => 1,
            'jurusan_id' => $jurusanKosong->id,
            'prodi_id' => null,
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($userKosong)->get(route('jurusan.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Belum Ada Program Studi Terdaftar');
    }

    public function test_admin_jurusan_can_view_jadwal_monitoring_with_filters(): void
    {
        $response = $this->actingAs($this->userJtei)->get(route('jurusan.jadwal', [
            'prodi_id' => $this->prodiTi->id,
            'hari' => 'Senin',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Monitoring Jadwal Kuliah Jurusan');
    }

    public function test_admin_jurusan_can_create_evaluation_note_for_child_prodi(): void
    {
        $payload = [
            'prodi_id' => $this->prodiTi->id,
            'kategori' => CatatanEvaluasiJadwal::KATEGORI_BENTROK_DOSEN,
            'judul' => 'Dugaan Bentrok Jadwal Dosen Informatika',
            'catatan_rekomendasi' => 'Mohon periksa kembali jadwal mata kuliah Pemrograman Web.',
        ];

        $response = $this->actingAs($this->userJtei)->post(route('jurusan.evaluasi.store'), $payload);

        $response->assertSessionHas('flash_success');
        $this->assertDatabaseHas('catatan_evaluasi_jadwals', [
            'jurusan_id' => $this->jtei->id,
            'prodi_id' => $this->prodiTi->id,
            'judul' => 'Dugaan Bentrok Jadwal Dosen Informatika',
            'status' => CatatanEvaluasiJadwal::STATUS_MENUNGGU,
        ]);
    }

    public function test_admin_jurusan_cannot_create_evaluation_note_for_other_jurusan_prodi(): void
    {
        // Cari atau buat prodi di bawah jurusan lain (JTIM)
        $prodiJtim = Prodi::where('jurusan_id', $this->jtim->id)->first() ?? Prodi::create([
            'nama' => 'S1 Teknik Perkapalan',
            'kode' => 'TP',
            'jurusan_id' => $this->jtim->id,
            'fakultas_id' => 1,
            'status' => 'ACTIVE',
        ]);

        $payload = [
            'prodi_id' => $prodiJtim->id,
            'kategori' => CatatanEvaluasiJadwal::KATEGORI_KETIDAKSESUAIAN_RUANGAN,
            'judul' => 'Uji Coba Lintas Jurusan',
            'catatan_rekomendasi' => 'Catatan ini tidak boleh diterima.',
        ];

        $response = $this->actingAs($this->userJtei)->post(route('jurusan.evaluasi.store'), $payload);

        $response->assertSessionHasErrors(['prodi_id']);
    }

    public function test_admin_prodi_can_respond_to_evaluation_note_addressed_to_their_prodi(): void
    {
        $catatan = CatatanEvaluasiJadwal::create([
            'jurusan_id' => $this->jtei->id,
            'prodi_id' => $this->prodiTi->id,
            'user_id_pengirim' => $this->userJtei->id,
            'kategori' => CatatanEvaluasiJadwal::KATEGORI_KETIDAKSESUAIAN_RUANGAN,
            'judul' => 'Kapasitas Ruangan Kelas A',
            'catatan_rekomendasi' => 'Kapasitas ruang kuliah kurang mencukupi.',
            'status' => CatatanEvaluasiJadwal::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->userProdiTi)->post(route('prodi.evaluasi.tanggapi', ['catatan' => $catatan->id]), [
            'status' => CatatanEvaluasiJadwal::STATUS_SELESAI,
            'tanggapan_prodi' => 'Ruangan telah kami pindahkan ke Lab Rekayasa Perangkat Lunak.',
        ]);

        $response->assertSessionHas('flash_success');

        $catatan->refresh();
        $this->assertEquals(CatatanEvaluasiJadwal::STATUS_SELESAI, $catatan->status);
        $this->assertEquals('Ruangan telah kami pindahkan ke Lab Rekayasa Perangkat Lunak.', $catatan->tanggapan_prodi);
        $this->assertEquals($this->userProdiTi->id, $catatan->user_id_penanggap);
    }

    public function test_admin_prodi_cannot_respond_to_evaluation_note_of_another_prodi(): void
    {
        $catatanTe = CatatanEvaluasiJadwal::create([
            'jurusan_id' => $this->jtei->id,
            'prodi_id' => $this->prodiTe->id,
            'user_id_pengirim' => $this->userJtei->id,
            'kategori' => CatatanEvaluasiJadwal::KATEGORI_PERMINTAAN_PEMERIKSAAN,
            'judul' => 'Pemeriksaan Kelas Elektro',
            'catatan_rekomendasi' => 'Mohon cek jam praktikum rangkaian listrik.',
            'status' => CatatanEvaluasiJadwal::STATUS_MENUNGGU,
        ]);

        // User Prodi TI mencoba menanggapi catatan milik Prodi TE
        $response = $this->actingAs($this->userProdiTi)->post(route('prodi.evaluasi.tanggapi', ['catatan' => $catatanTe->id]), [
            'status' => CatatanEvaluasiJadwal::STATUS_SELESAI,
            'tanggapan_prodi' => 'Tanggapan tidak sah oleh prodi lain.',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_jurusan_cannot_modify_or_create_schedule_directly(): void
    {
        // Admin Jurusan mencoba memanggil route tambah jadwal milik Prodi
        $response = $this->actingAs($this->userJtei)->post(route('prodi.jadwal.store'), [
            'rombel_id' => 1,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_jurusan_can_view_laporan_rekapitulasi(): void
    {
        $response = $this->actingAs($this->userJtei)->get(route('jurusan.laporan'));

        $response->assertStatus(200);
        $response->assertSee('Rekapitulasi Kesiapan Penjadwalan per Program Studi');
        $response->assertSee('KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI');
        $response->assertSee($this->jtei->nama);
    }
}
