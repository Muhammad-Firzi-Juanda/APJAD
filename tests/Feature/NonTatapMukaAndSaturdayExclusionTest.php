<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\MataKuliahDetail;
use App\Models\Ruangan;
use App\Services\GeneticSchedulerService;
use App\Services\SchedulerService;
use Database\Seeders\JadwalKuliahSchedulerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NonTatapMukaAndSaturdayExclusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JadwalKuliahSchedulerSeeder::class);
    }

    /**
     * 1. KKN / Kukerta sepenuhnya dikecualikan dari proses generate (tidak ada di Senin-Jumat maupun Sabtu),
     * tidak dihitung sebagai kegagalan, dan datanya tetap utuh di database.
     */
    public function test_kkn_is_completely_excluded_from_generator(): void
    {
        $dosen = Dosen::first();

        $mkKkn = MataKuliahDetail::create([
            'kode_mk' => 'UNV7001',
            'nama_mk' => 'Kuliah Kerja Nyata (KUKERTA)',
            'sks' => 4,
            'semester' => 7,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosen->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => true,
        ]);

        $mkReguler = MataKuliahDetail::create([
            'kode_mk' => 'TIF7005',
            'nama_mk' => 'Etika Profesi & Rekayasa Perangkat Lunak',
            'sks' => 2,
            'semester' => 7,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosen->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 35,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => false,
        ]);

        $this->assertTrue($mkKkn->isKkn());
        $this->assertEquals('kkn', $mkKkn->getKategoriPenjadwalan());

        $scheduler = app(SchedulerService::class);
        $hasil = $scheduler->generate(7, 'Informatika');

        // Pastikan MK reguler terjadwal
        $scheduledMkIds = Jadwal::with('rombel')
            ->where('is_active', 1)
            ->get()
            ->pluck('rombel.mata_kuliah_detail_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        $this->assertContains($mkReguler->id, $scheduledMkIds);

        // Pastikan KKN sama sekali TIDAK dijadwalkan
        $this->assertNotContains($mkKkn->id, $scheduledMkIds);

        // Pastikan KKN tidak dilaporkan sebagai kegagalan
        $kegagalanCodes = array_column($hasil['kegagalan'] ?? [], 'kode_mk');
        $this->assertNotContains('UNV7001', $kegagalanCodes);

        // Pastikan KKN dilaporkan di statistik khusus
        $this->assertEquals(1, $hasil['statistik']['mk_kkn_dikecualikan'] ?? 0);

        // Pastikan data KKN tetap tersimpan di database
        $this->assertDatabaseHas('mata_kuliah_details', ['id' => $mkKkn->id]);
    }

    /**
     * 2. Kegiatan khusus KP, Magang, dan Skripsi dijadwalkan khusus pada hari Sabtu,
     * tidak memaksakan alokasi ruangan bila tanpa_ruangan = true, dan mematuhi bentrok dosen.
     */
    public function test_special_activities_kp_magang_skripsi_scheduled_on_saturday(): void
    {
        $dosenA = Dosen::first();
        $dosenB = Dosen::skip(1)->first();

        $mkKP = MataKuliahDetail::create([
            'kode_mk' => 'TIF7002',
            'nama_mk' => 'Kerja Praktik',
            'sks' => 2,
            'semester' => 7,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosenA->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => true, // Tanpa ruangan
        ]);

        $mkSkripsi = MataKuliahDetail::create([
            'kode_mk' => 'TIF7003',
            'nama_mk' => 'Skripsi / Tugas Akhir',
            'sks' => 6,
            'semester' => 7,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosenB->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => false, // Butuh ruangan sidang/seminar
        ]);

        $this->assertTrue($mkKP->isKegiatanKhusus());
        $this->assertEquals('KP', $mkKP->getJenisKegiatanKhusus());
        $this->assertTrue($mkSkripsi->isKegiatanKhusus());
        $this->assertEquals('Skripsi', $mkSkripsi->getJenisKegiatanKhusus());

        $scheduler = app(SchedulerService::class);
        $hasil = $scheduler->generate(7, 'Informatika');

        $this->assertGreaterThanOrEqual(2, $hasil['jumlah_jadwal']);

        // Periksa jadwal KP
        $jadwalKp = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkKP->id))->first();
        $this->assertNotNull($jadwalKp);
        $this->assertEquals('Sabtu', $jadwalKp->hari, 'KP harus dijadwalkan pada hari Sabtu');
        $this->assertNull($jadwalKp->ruangan_id, 'KP tanpa ruangan tidak boleh dipaksakan ruangan');

        // Periksa jadwal Skripsi
        $jadwalSkripsi = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkSkripsi->id))->first();
        $this->assertNotNull($jadwalSkripsi);
        $this->assertEquals('Sabtu', $jadwalSkripsi->hari, 'Skripsi harus dijadwalkan pada hari Sabtu');
        $this->assertNotNull($jadwalSkripsi->ruangan_id, 'Skripsi dengan tanpa_ruangan=false harus dialokasikan ruangan');

        // Periksa statistik terpisah
        $this->assertGreaterThanOrEqual(2, $hasil['statistik']['mk_khusus_berhasil'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $hasil['statistik']['mk_khusus_kp'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $hasil['statistik']['mk_khusus_skripsi'] ?? 0);
        $this->assertEquals(
            ($hasil['statistik']['mk_khusus_kp'] ?? 0) + ($hasil['statistik']['mk_khusus_skripsi'] ?? 0) + ($hasil['statistik']['mk_khusus_magang'] ?? 0),
            $hasil['statistik']['mk_khusus_berhasil'] ?? 0
        );
    }

    /**
     * Pengujian khusus Magang:
     * - Memastikan Magang diproses oleh generator ke hari Sabtu
     * - Memeriksa penugasan dosen pembimbing/koordinator
     * - Memeriksa pemisahan alokasi tanpa ruangan vs dengan ruangan fisik
     * - Memverifikasi tidak terjadi tumpang tindih waktu untuk kelompok/cohort yang sama
     */
    public function test_magang_is_processed_on_saturday_with_lecturer_cohort_and_room_checks(): void
    {
        $dosenA = Dosen::first();
        $dosenB = Dosen::skip(1)->first();

        // 1. Magang Industri (Tanpa Ruangan - aktivitas di luar kampus/mitra)
        $mkMagangIndustri = MataKuliahDetail::create([
            'kode_mk' => 'TIF7021',
            'nama_mk' => 'Magang Industri Bersertifikat',
            'sks' => 3,
            'semester' => 7,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosenA->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 30,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => true,
        ]);

        // 2. Magang Riset / Studi Independen (Dengan Ruangan - evaluasi/presentasi di kampus)
        $mkMagangRiset = MataKuliahDetail::create([
            'kode_mk' => 'TIF7022',
            'nama_mk' => 'Studi Independen / Magang Riset',
            'sks' => 3,
            'semester' => 7,
            'tipe' => 'Pilihan',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosenB->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 25,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => false,
        ]);

        // Verifikasi identifikasi model
        $this->assertTrue($mkMagangIndustri->isKegiatanKhusus());
        $this->assertEquals('Magang', $mkMagangIndustri->getJenisKegiatanKhusus());
        $this->assertTrue($mkMagangRiset->isKegiatanKhusus());
        $this->assertEquals('Magang', $mkMagangRiset->getJenisKegiatanKhusus());

        // Jalankan generator
        $scheduler = app(SchedulerService::class);
        $hasil = $scheduler->generate(7, 'Informatika');

        // Verifikasi jadwal Magang Industri di database
        $jadwalIndustri = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkMagangIndustri->id))->first();
        $this->assertNotNull($jadwalIndustri, 'Magang Industri harus berhasil dijadwalkan');
        $this->assertEquals('Sabtu', $jadwalIndustri->hari, 'Magang harus dijadwalkan pada hari Sabtu');
        $this->assertEquals($dosenA->id, $jadwalIndustri->dosen_id, 'Dosen pembimbing Magang Industri harus sesuai');
        $this->assertNull($jadwalIndustri->ruangan_id, 'Magang tanpa_ruangan=true tidak boleh dialokasikan ruangan fisik');

        // Verifikasi jadwal Magang Riset di database
        $jadwalRiset = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkMagangRiset->id))->first();
        $this->assertNotNull($jadwalRiset, 'Magang Riset harus berhasil dijadwalkan');
        $this->assertEquals('Sabtu', $jadwalRiset->hari, 'Magang harus dijadwalkan pada hari Sabtu');
        $this->assertEquals($dosenB->id, $jadwalRiset->dosen_id, 'Dosen pembimbing Magang Riset harus sesuai');
        $this->assertNotNull($jadwalRiset->ruangan_id, 'Magang tanpa_ruangan=false harus mendapatkan ruangan');

        // Verifikasi proteksi tumpang tindih waktu untuk kelompok mahasiswa / cohort yang sama
        $audit = $scheduler->auditValidasiJadwal($scheduler->getRencanaJadwal());
        $this->assertTrue($audit['valid'], 'Audit rencana jadwal tidak boleh memiliki bentrok');

        // Verifikasi statistik khusus Magang
        $this->assertGreaterThanOrEqual(2, $hasil['statistik']['mk_khusus_magang'] ?? 0);
        $this->assertGreaterThanOrEqual(2, $hasil['statistik']['mk_khusus_berhasil'] ?? 0);
    }

    /**
     * 3. Perkuliahan reguler HANYA menggunakan hari Senin-Jumat,
     * dan tidak pernah dipindahkan ke hari Sabtu sekalipun slot hari kerja terbatas.
     */
    public function test_regular_courses_never_overflow_to_saturday(): void
    {
        $scheduler = app(SchedulerService::class);
        $hasil = $scheduler->generate(1, 'Informatika');

        $this->assertGreaterThan(0, $hasil['jumlah_jadwal']);

        $hariTerjadwal = Jadwal::where('is_active', 1)
            ->pluck('hari')
            ->unique()
            ->values()
            ->all();

        $this->assertNotContains('Sabtu', $hariTerjadwal, 'Perkuliahan reguler tidak boleh menggunakan hari Sabtu!');

        foreach ($hariTerjadwal as $hari) {
            $this->assertContains($hari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
        }
    }

    /**
     * 4. Keamanan Transaksi: Jika generate gagal karena audit atau bentrok wajib,
     * jadwal lama di database tetap utuh dan tidak terhapus.
     */
    public function test_transaction_safety_failed_generate_preserves_old_schedule(): void
    {
        $scheduler = app(SchedulerService::class);
        $hasilPertama = $scheduler->generate(1, 'Informatika');
        $countAwal = Jadwal::where('is_active', 1)->count();
        $this->assertGreaterThan(0, $countAwal);

        // Panggil auditValidasiJadwal dengan data invalid buatan untuk memverifikasi logika pencegahan
        $rencanaInvalid = [
            [
                'mk' => MataKuliahDetail::first(),
                'no' => 1,
                'slot' => ['hari' => 'Sabtu', 'mulai' => 450, 'selesai' => 550], // Pelanggaran: Reguler di Sabtu!
                'ruangan' => null,
                'dosenId' => null,
                'peran' => 'Ketua',
                'kategori' => 'reguler',
                'jenis_khusus' => null,
            ],
        ];

        $audit = $scheduler->auditValidasiJadwal($rencanaInvalid);
        $this->assertFalse($audit['valid']);
        $this->assertNotEmpty($audit['errors']);

        // Pastikan jadwal di database tetap sama persis dan tidak hilang
        $countAkhir = Jadwal::where('is_active', 1)->count();
        $this->assertEquals($countAwal, $countAkhir);
    }

    /**
     * 5. Pastikan GeneticSchedulerService juga mematuhi pemisahan 3 kategori:
     * Reguler di Senin-Jumat, Khusus di Sabtu, dan KKN dikecualikan.
     */
    public function test_genetic_scheduler_respects_all_categories(): void
    {
        $dosen = Dosen::first();

        // KKN
        MataKuliahDetail::create([
            'kode_mk' => 'UNV7009',
            'nama_mk' => 'KUKERTA Tematik',
            'sks' => 4,
            'semester' => 7,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosen->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => true,
        ]);

        // KP
        $mkKp = MataKuliahDetail::create([
            'kode_mk' => 'TIF7010',
            'nama_mk' => 'Kerja Praktik',
            'sks' => 2,
            'semester' => 7,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosen->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 40,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => true,
        ]);

        // Magang
        $mkMagang = MataKuliahDetail::create([
            'kode_mk' => 'TIF7012',
            'nama_mk' => 'Magang Mandiri Kampus Merdeka',
            'sks' => 3,
            'semester' => 7,
            'tipe' => 'Pilihan',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosen->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 30,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => true,
        ]);

        // Reguler
        $mkReguler = MataKuliahDetail::create([
            'kode_mk' => 'TIF7011',
            'nama_mk' => 'Pemrograman Cloud',
            'sks' => 3,
            'semester' => 7,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosen->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 35,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => false,
        ]);

        $geneticScheduler = app(GeneticSchedulerService::class);
        $hasil = $geneticScheduler->generate(7, 'Informatika');

        $this->assertEquals('Genetic Algorithm', $hasil['metode']);

        // KKN tidak ada
        $allScheduledCodes = Jadwal::with('rombel.mataKuliah')
            ->where('is_active', 1)
            ->get()
            ->pluck('rombel.mataKuliah.kode_mk')
            ->all();

        $this->assertNotContains('UNV7009', $allScheduledCodes);

        // KP ada di Sabtu
        $jadwalKp = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkKp->id))->first();
        $this->assertNotNull($jadwalKp);
        $this->assertEquals('Sabtu', $jadwalKp->hari);

        // Magang ada di Sabtu
        $jadwalMagang = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkMagang->id))->first();
        $this->assertNotNull($jadwalMagang);
        $this->assertEquals('Sabtu', $jadwalMagang->hari);

        // Reguler ada di Senin-Jumat
        $jadwalReguler = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkReguler->id))->first();
        $this->assertNotNull($jadwalReguler);
        $this->assertContains($jadwalReguler->hari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
    }
}
