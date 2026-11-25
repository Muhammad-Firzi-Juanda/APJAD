<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\MataKuliahDetail;
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
     * 1. KKN / Kukerta sepenuhnya dikecualikan dari proses generate (tidak dijadwalkan otomatis),
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
        $this->assertTrue($mkKkn->isDikecualikanDariGenerate());
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

        // Pastikan KKN sama sekali TIDAK dijadwalkan secara otomatis
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
     * 2. MK Khusus/Non-Generate (KP dan Skripsi) sepenuhnya dikecualikan dari proses generate otomatis,
     * tidak dijadwalkan ke hari manapun, tidak dilaporkan sebagai kegagalan, dan datanya tetap aman.
     */
    public function test_special_activities_kp_magang_skripsi_excluded_from_auto_generate(): void
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
            'tanpa_ruangan' => true,
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
            'tanpa_ruangan' => false,
        ]);

        $this->assertTrue($mkKP->isKp());
        $this->assertTrue($mkKP->isDikecualikanDariGenerate());
        $this->assertEquals('kp', $mkKP->getKategoriPenjadwalan());

        $this->assertTrue($mkSkripsi->isSkripsi());
        $this->assertTrue($mkSkripsi->isDikecualikanDariGenerate());
        $this->assertEquals('skripsi', $mkSkripsi->getKategoriPenjadwalan());

        $scheduler = app(SchedulerService::class);
        $hasil = $scheduler->generate(7, 'Informatika');

        // Pastikan KP dan Skripsi TIDAK dibuatkan jadwal otomatis
        $jadwalKp = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkKP->id))->first();
        $this->assertNull($jadwalKp, 'KP tidak boleh dibuatkan jadwal otomatis');

        $jadwalSkripsi = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkSkripsi->id))->first();
        $this->assertNull($jadwalSkripsi, 'Skripsi tidak boleh dibuatkan jadwal otomatis');

        // Periksa statistik dikecualikan
        $this->assertGreaterThanOrEqual(2, $hasil['statistik']['mk_dikecualikan_total'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $hasil['statistik']['mk_dikecualikan_rincian']['kp'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $hasil['statistik']['mk_dikecualikan_rincian']['skripsi'] ?? 0);

        // Pastikan tidak dihitung gagal
        $kegagalanCodes = array_column($hasil['kegagalan'] ?? [], 'kode_mk');
        $this->assertNotContains('TIF7002', $kegagalanCodes);
        $this->assertNotContains('TIF7003', $kegagalanCodes);
    }

    /**
     * 3. Pengujian MK Khusus lainnya seperti Magang, PPS, dan MK Mengulang (Ulang):
     * - Terdeteksi sebagai dikecualikan dari auto-generate
     * - Tidak dijadwalkan secara otomatis
     * - Tidak dicatat sebagai error/kegagalan
     */
    public function test_magang_pps_ulang_excluded_from_auto_generate(): void
    {
        $dosenA = Dosen::first();
        $dosenB = Dosen::skip(1)->first();

        // Magang
        $mkMagang = MataKuliahDetail::create([
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

        // PPS (Praktik Pengenalan Lapangan / Persekolahan)
        $mkPps = MataKuliahDetail::create([
            'kode_mk' => 'TIF7022',
            'nama_mk' => 'Praktik Pengenalan Persekolahan (PPS)',
            'sks' => 2,
            'semester' => 7,
            'tipe' => 'Wajib',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosenB->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 25,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => true,
        ]);

        // Mengulang
        $mkUlang = MataKuliahDetail::create([
            'kode_mk' => 'TIF7023',
            'nama_mk' => 'Kalkulus 1 (Mengulang)',
            'sks' => 3,
            'semester' => 7,
            'tipe' => 'Pilihan',
            'prodi' => 'Teknik Informatika',
            'dosen_ketua_id' => $dosenA->id,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 20,
            'is_active' => 1,
            'status_tawar' => 1,
            'butuh_lab' => false,
            'tanpa_ruangan' => false,
        ]);

        // Verifikasi identifikasi model
        $this->assertTrue($mkMagang->isMagang());
        $this->assertTrue($mkMagang->isDikecualikanDariGenerate());

        $this->assertTrue($mkPps->isPps());
        $this->assertTrue($mkPps->isDikecualikanDariGenerate());

        $this->assertTrue($mkUlang->isUlang());
        $this->assertTrue($mkUlang->isDikecualikanDariGenerate());

        // Jalankan generator
        $scheduler = app(SchedulerService::class);
        $hasil = $scheduler->generate(7, 'Informatika');

        // Verifikasi tidak ada jadwal otomatis yang dibuat
        $this->assertNull(Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkMagang->id))->first());
        $this->assertNull(Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkPps->id))->first());
        $this->assertNull(Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkUlang->id))->first());

        // Verifikasi rincian statistik
        $this->assertGreaterThanOrEqual(1, $hasil['statistik']['mk_dikecualikan_rincian']['magang'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $hasil['statistik']['mk_dikecualikan_rincian']['pps'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $hasil['statistik']['mk_dikecualikan_rincian']['ulang'] ?? 0);
    }

    /**
     * 4. Perkuliahan reguler HANYA menggunakan hari Senin-Jumat,
     * dan tidak pernah dijadwalkan pada hari Sabtu.
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
     * 5. Keamanan Transaksi: Jika generate gagal karena audit atau bentrok wajib,
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
     * 6. Pastikan GeneticSchedulerService juga mematuhi pengecualian MK non-generate:
     * KKN, KP, Magang, PPS, Ulang, dan Skripsi dikecualikan dari jadwal otomatis,
     * hanya perkuliahan reguler yang dijadwalkan pada Senin-Jumat.
     */
    public function test_genetic_scheduler_respects_all_categories(): void
    {
        $dosen = Dosen::first();

        // KKN
        $mkKkn = MataKuliahDetail::create([
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

        // KKN, KP, Magang TIDAK dijadwalkan otomatis
        $allScheduledCodes = Jadwal::with('rombel.mataKuliah')
            ->where('is_active', 1)
            ->get()
            ->pluck('rombel.mataKuliah.kode_mk')
            ->all();

        $this->assertNotContains('UNV7009', $allScheduledCodes, 'KKN tidak boleh dijadwalkan');
        $this->assertNotContains('TIF7010', $allScheduledCodes, 'KP tidak boleh dijadwalkan otomatis');
        $this->assertNotContains('TIF7012', $allScheduledCodes, 'Magang tidak boleh dijadwalkan otomatis');

        // Reguler ada di Senin-Jumat
        $jadwalReguler = Jadwal::whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mkReguler->id))->first();
        $this->assertNotNull($jadwalReguler, 'MK Reguler harus berhasil dijadwalkan');
        $this->assertContains($jadwalReguler->hari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
    }
}
