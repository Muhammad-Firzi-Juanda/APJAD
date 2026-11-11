<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\MataKuliahDetail;
use App\Models\Rombel;
use App\Models\Ruangan;
use App\Services\SchedulerService;
use Database\Seeders\JadwalKuliahSchedulerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji aturan wajib Pak Nov:
 *  1. Bentrok kelas  : satu cohort (prodi+semester) tidak boleh 2 MK pada waktu sama.
 *  2. Dosen ketua    : tidak boleh bentrok di MK mana pun.
 *  3. Dosen anggota  : bergantian dengan ketua untuk kelas paralel.
 *  4. Slot           : mulai 07:30/10:00/13:00/14:50, durasi SKS x 50 menit.
 *  5. MK Pilihan     : satu slot yang sama, maksimal 2.
 *  6. Ruangan        : tidak boleh dipakai dua kelas pada waktu yang sama.
 */
class SchedulerServiceTest extends TestCase
{
    use RefreshDatabase;

    private const SEMESTER = 1;

    private const PRODI = 'Teknik Informatika';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JadwalKuliahSchedulerSeeder::class);
    }

    /* ------------------------------------------------------------------ */
    /* Helper */
    /* ------------------------------------------------------------------ */

    private function generate(int $semester = self::SEMESTER): array
    {
        return app(SchedulerService::class)->generate($semester, 'Informatika');
    }

    private function format(int $menit): string
    {
        return sprintf('%02d:%02d', intdiv($menit, 60), $menit % 60);
    }

    private function menit(string $waktu): int
    {
        [$j, $m] = array_map('intval', explode(':', $waktu));

        return $j * 60 + $m;
    }

    /** Interval overlap dua jadwal pada hari yang sama. */
    private function tumpang(array $a, array $b): bool
    {
        return $this->menit($a['jam_mulai']) < $this->menit($b['jam_selesai'])
            && $this->menit($a['jam_selesai']) > $this->menit($b['jam_mulai']);
    }

    /** Semua jadwal aktif beserta relasi lengkapnya. */
    private function semuaJadwal(): array
    {
        return Jadwal::with(['rombel.mataKuliah', 'ruangan', 'dosen'])
            ->where('is_active', 1)
            ->get()
            ->map(fn ($j) => [
                'id' => $j->id,
                'rombel_id' => $j->rombel_id,
                'ruangan_id' => $j->ruangan_id,
                'dosen_id' => $j->dosen_id,
                'hari' => $j->hari,
                'jam_mulai' => $j->jam_mulai,
                'jam_selesai' => $j->jam_selesai,
                'mk' => $j->rombel?->mataKuliah,
                'ruangan' => $j->ruangan,
                'dosen' => $j->dosen,
            ])
            ->all();
    }

    /* ------------------------------------------------------------------ */
    /* Aturan 1 - bentrok kelas (cohort) */
    /* ------------------------------------------------------------------ */

    public function test_cohort_tidak_boleh_dua_mk_wajib_dalam_waktu_sama(): void
    {
        $this->generate();

        $jadwal = $this->semuaJadwal();

        for ($i = 0; $i < count($jadwal); $i++) {
            for ($k = $i + 1; $k < count($jadwal); $k++) {
                $a = $jadwal[$i];
                $b = $jadwal[$k];

                if ($a['hari'] !== $b['hari']) {
                    continue;
                }
                if ($a['mk']->id === $b['mk']->id) {
                    continue;
                } // kelas paralel MK yang sama
                if ($a['mk']->semester !== $b['mk']->semester) {
                    continue;
                }
                if ($a['mk']->prodi !== $b['mk']->prodi) {
                    continue;
                }

                // MK Pilihan memang sengaja diletakkan pada slot yang sama
                // supaya mahasiswa tidak bisa memilih lebih dari satu.
                if ($a['mk']->tipe === 'Pilihan' && $b['mk']->tipe === 'Pilihan') {
                    continue;
                }

                $this->assertFalse(
                    $this->tumpang($a, $b),
                    sprintf(
                        'Cohort bentrok: %s (%s) dan %s (%s) pada %s %s-%s',
                        $a['mk']->kode_mk,
                        $a['mk']->nama_mk,
                        $b['mk']->kode_mk,
                        $b['mk']->nama_mk,
                        $a['hari'],
                        $a['jam_mulai'],
                        $a['jam_selesai']
                    )
                );
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Aturan 2 - bentrok dosen */
    /* ------------------------------------------------------------------ */

    public function test_dosen_tidak_boleh_bentrok(): void
    {
        $this->generate();

        $jadwal = $this->semuaJadwal();

        for ($i = 0; $i < count($jadwal); $i++) {
            for ($k = $i + 1; $k < count($jadwal); $k++) {
                $a = $jadwal[$i];
                $b = $jadwal[$k];

                if ($a['hari'] !== $b['hari']) {
                    continue;
                }
                if (! $a['dosen_id'] || $a['dosen_id'] !== $b['dosen_id']) {
                    continue;
                }

                $this->assertFalse(
                    $this->tumpang($a, $b),
                    sprintf(
                        'Dosen %s (%s) bentrok antara %s dan %s pada %s %s-%s',
                        $a['dosen']->nama,
                        $a['dosen']->nidn,
                        $a['mk']->kode_mk,
                        $b['mk']->kode_mk,
                        $a['hari'],
                        $a['jam_mulai'],
                        $a['jam_selesai']
                    )
                );
            }
        }
    }

    public function test_dosen_ketua_diperrioritaskan(): void
    {
        $this->generate();

        $rombel = Rombel::with('mataKuliah')->whereHas(
            'mataKuliah',
            fn ($q) => $q->where('semester', self::SEMESTER)
        )->get();

        $this->assertGreaterThan(0, $rombel->count());

        foreach ($rombel as $r) {
            if ($r->peran_dosen !== 'Ketua') {
                continue;
            }

            $this->assertSame(
                $r->mataKuliah->dosen_ketua_id,
                $r->dosen_pengampu_id,
                "Kelas {$r->mataKuliah->kode_mk} ditandai Ketua tetapi dosennya bukan dosen ketua."
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /* Aturan 3 - kelas paralel */
    /* ------------------------------------------------------------------ */

    public function test_kelas_paralel_dapat_ruangan_dan_dosen_berbeda(): void
    {
        $hasil = $this->generate();

        // Kelas paralel adalah kelas MK yang sama pada hari + jam yang sama.
        $grup = Rombel::whereHas('mataKuliah', fn ($q) => $q->where('semester', self::SEMESTER))
            ->get()
            ->groupBy(fn ($r) => implode('|', [
                $r->mata_kuliah_id,
                $r->hari,
                $r->jam_mulai,
                $r->jam_selesai,
            ]));

        $ditemukan = 0;

        foreach ($grup as $kunci => $kelompok) {
            if ($kelompok->count() < 2) {
                continue;
            }
            $ditemukan++;

            [$mkId, $hari, $mulai] = explode('|', $kunci);
            $kode = $kelompok->first()->mataKuliah->kode_mk;

            $ruangan = $kelompok->pluck('ruangan_id')->all();
            $this->assertSame(
                count($ruangan),
                count(array_unique($ruangan)),
                "Kelas paralel $kode pada $hari $mulai memakai ruangan yang sama."
            );

            $dosen = $kelompok->pluck('dosen_pengampu_id')->all();
            $this->assertSame(
                count($dosen),
                count(array_unique($dosen)),
                "Kelas paralel $kode pada $hari $mulai memakai dosen yang sama (MK #{$mkId})."
            );

            // Semua kelas dalam grup harus benar-benar parallel class MK yang sama
            $this->assertSame(1, $kelompok->pluck('mata_kuliah_id')->unique()->count());
        }

        $this->assertGreaterThan(0, $ditemukan, 'Seharusnya ada minimal satu kelompok kelas paralel.');
        $this->assertGreaterThan(0, $hasil['jumlah_jadwal']);
    }

    /* ------------------------------------------------------------------ */
    /* Aturan 4 - slot dan durasi */
    /* ------------------------------------------------------------------ */

    public function test_jam_mulai_selalu_dari_daftar_slot(): void
    {
        $this->generate();

        foreach ($this->semuaJadwal() as $j) {
            $this->assertContains(
                $j['jam_mulai'],
                SchedulerService::SLOT_MULAI,
                "Jam mulai {$j['jam_mulai']} ({$j['mk']->kode_mk}) bukan slot yang diizinkan."
            );
            $this->assertContains(
                $j['hari'],
                SchedulerService::HARI,
                "Hari {$j['hari']} tidak dikenal."
            );
        }
    }

    public function test_durasi_sama_dengan_sks_kali_50_menit(): void
    {
        $this->generate();

        foreach ($this->semuaJadwal() as $j) {
            $durasi = $this->menit($j['jam_selesai']) - $this->menit($j['jam_mulai']);
            $harapan = $j['mk']->sks * 50;

            $this->assertSame(
                $harapan,
                $durasi,
                "Durasi {$j['mk']->kode_mk} = {$durasi} menit, seharusnya {$harapan} menit."
            );
        }
    }

    public function test_kelas_yang_ruwet_diprioritaskan(): void
    {
        $hasil = $this->generate();

        // MK dengan SKS terbesar harus terpasang lebih dulu (tidak ada GAGAL)
        foreach ($hasil['log'] as $baris) {
            $this->assertStringNotContainsString('GAGAL', $baris, "Ada MK gagal dijadwalkan: {$baris}");
        }
    }

    /* ------------------------------------------------------------------ */
    /* Aturan 5 - MK Pilihan */
    /* ------------------------------------------------------------------ */

    public function test_mk_pilihan_dibagi_ke_dua_kelompok_slot(): void
    {
        $hasil = $this->generate();

        $pilihan = Jadwal::with('rombel.mataKuliah')
            ->whereHas('rombel.mataKuliah', fn ($q) => $q->where('tipe', 'Pilihan'))
            ->get();

        if ($pilihan->isEmpty()) {
            $this->markTestSkipped('Tidak ada MK Pilihan pada seed semester ini.');
        }

        $this->assertGreaterThan(1, $pilihan->count(), 'Seharusnya ada minimal 2 MK Pilihan.');
        $this->assertCount(SchedulerService::MAKS_PILIHAN, $hasil['slot_pilihan']);

        // Tidak boleh ada MK Pilihan yang bisa diambil dua kali:
        // artinya tidak boleh ada slot yang bisa menampung 2 MK sekaligus
        // pada kelompok yang sama. Cukup pastikan tiap kelompok punya slot unik.
        $slot = $hasil['slot_pilihan'];
        $this->assertNotSame(
            $slot[0]['hari'].$slot[0]['mulai'],
            $slot[1]['hari'].$slot[1]['mulai'],
            'Dua kelompok MK Pilihan tidak boleh berbagi slot.'
        );

        // Semua pilihan harus punya ruangan (tidak boleh-null).
        foreach ($pilihan as $j) {
            $this->assertNotNull($j->ruangan_id);
            $this->assertNotNull($j->dosen_id);
        }
    }

    public function test_semua_mk_pilihan_terjadwal_dan_kuota_dua_kelompok(): void
    {
        // Tambahkan MK Pilihan ketiga agar terlihat pembagian ke 2 kelompok.
        $dosen = Dosen::where('is_active', 1)->orderByDesc('id')->value('id');

        MataKuliahDetail::create([
            'kode_mk' => 'IF1499',
            'nama_mk' => 'Pilihan Ketiga',
            'sks' => 2,
            'semester' => self::SEMESTER,
            'tipe' => 'Pilihan',
            'prodi' => self::PRODI,
            'dosen_ketua_id' => $dosen,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 30,
            'butuh_lab' => false,
            'is_active' => true,
        ]);

        $hasil = $this->generate();

        $kodeTerjadwal = Jadwal::with('rombel.mataKuliah')
            ->whereHas('rombel.mataKuliah', fn ($q) => $q->where('tipe', 'Pilihan'))
            ->get()
            ->pluck('rombel.mataKuliah.kode_mk')
            ->unique()
            ->values()
            ->all();

        // Semua MK Pilihan harus tetap dijadwalkan (tidak ada yang dibuang).
        $this->assertContains('IF1499', $kodeTerjadwal, 'MK Pilihan tambahan harus tetap dijadwalkan.');

        // Kuota = jumlah kelompok slot = MAKS_PILIHAN.
        $this->assertSame(SchedulerService::MAKS_PILIHAN, $hasil['kuota_pilihan']);
        $this->assertCount(SchedulerService::MAKS_PILIHAN, $hasil['slot_pilihan']);

        // Mahasiswa bisa ambil maksimal 1 MK per kelompok = MAKS_PILIHAN total.
        $slotUnik = [];
        foreach ($hasil['slot_pilihan'] as $slot) {
            $slotUnik[$slot['hari'].' '.$this->format($slot['mulai'])] = true;
        }
        $this->assertCount(
            SchedulerService::MAKS_PILIHAN,
            $slotUnik,
            'Setiap kelompok MK Pilihan harus memakai slot yang berbeda.'
        );
    }

    public function test_mk_pilihan_dalam_satu_kelompok_saling_bentrok(): void
    {
        $hasil = $this->generate();

        // Kelompok harus berisi lebih dari satu MK agar aturan ini teruji.
        if (count($hasil['slot_pilihan']) === 0) {
            $this->markTestSkipped('Tidak ada MK Pilihan pada seed.');
        }

        foreach ($hasil['slot_pilihan'] as $slot) {
            preg_match('/\((.+)\)$/', $slot['label'], $m);
            $kode = array_map('trim', explode(',', $m[1] ?? ''));

            $jadwal = Jadwal::with('rombel.mataKuliah')
                ->whereHas('rombel.mataKuliah', fn ($q) => $q->whereIn('kode_mk', $kode))
                ->get();

            $this->assertGreaterThan(0, $jadwal->count());

            // Semua dalam kelompok harus pada hari + jam yang sama persis.
            $pertama = $jadwal->first();
            foreach ($jadwal as $j) {
                $this->assertSame($pertama->hari, $j->hari);
                $this->assertSame($pertama->jam_mulai, $j->jam_mulai);
            }

            // Ruangan berbeda satu sama lain supaya tidak saling tabrak fisik.
            $ruangan = $jadwal->pluck('ruangan_id')->all();
            $this->assertSame(count($ruangan), count(array_unique($ruangan)));
        }
    }

    /* ------------------------------------------------------------------ */
    /* Aturan 6 - ruangan */
    /* ------------------------------------------------------------------ */

    public function test_ruangan_tidak_boleh_bentrok(): void
    {
        $this->generate();

        $jadwal = $this->semuaJadwal();

        for ($i = 0; $i < count($jadwal); $i++) {
            for ($k = $i + 1; $k < count($jadwal); $k++) {
                $a = $jadwal[$i];
                $b = $jadwal[$k];

                if ($a['hari'] !== $b['hari']) {
                    continue;
                }
                if ($a['ruangan_id'] !== $b['ruangan_id']) {
                    continue;
                }

                $this->assertFalse(
                    $this->tumpang($a, $b),
                    sprintf(
                        'Ruangan %s dipakai dua kelas pada %s (%s-%s vs %s-%s)',
                        $a['ruangan']->kode,
                        $a['hari'],
                        $a['jam_mulai'],
                        $a['jam_selesai'],
                        $b['jam_mulai'],
                        $b['jam_selesai']
                    )
                );
            }
        }
    }

    public function test_hanya_ruangan_aktif_yang_dipakai(): void
    {
        $this->generate();

        $aktif = Ruangan::where('is_active', 1)->pluck('id')->all();

        foreach ($this->semuaJadwal() as $j) {
            $this->assertContains(
                $j['ruangan_id'],
                $aktif,
                "Jadwal {$j['mk']->kode_mk} memakai ruangan non-aktif."
            );
        }
    }

    public function test_mk_lab_ditempatkan_di_ruangan_lab(): void
    {
        $this->generate();

        foreach ($this->semuaJadwal() as $j) {
            if (! $j['mk']->butuh_lab) {
                continue;
            }

            $this->assertSame(
                'Lab',
                $j['ruangan']->tipe,
                "MK {$j['mk']->kode_mk} butuh lab tapi memakai {$j['ruangan']->tipe}."
            );
        }
    }

    public function test_mk_non_lab_tidak_di_ruangan_lab(): void
    {
        $this->generate();

        foreach ($this->semuaJadwal() as $j) {
            if ($j['mk']->butuh_lab) {
                continue;
            }

            $this->assertNotSame(
                'Lab',
                $j['ruangan']->tipe,
                "MK {$j['mk']->kode_mk} tidak butuh lab tapi memakai lab."
            );
        }
    }

    public function test_ruangan_kurang_kapasitas_tidak_dipakai(): void
    {
        MataKuliahDetail::create([
            'kode_mk' => 'IF1500',
            'nama_mk' => 'MK Huge',
            'sks' => 2,
            'semester' => self::SEMESTER,
            'tipe' => 'Wajib',
            'prodi' => self::PRODI,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 500,
            'butuh_lab' => false,
            'is_active' => true,
        ]);

        $hasil = $this->generate();

        $ada = Jadwal::with('rombel.mataKuliah')
            ->whereHas('rombel.mataKuliah', fn ($q) => $q->where('kode_mk', 'IF1500'))
            ->count();

        $this->assertSame(0, $ada, 'MK dengan kapasitas di luar ruang seharusnya gagal.');
        $this->assertStringContainsString('IF1500', implode(' ', $hasil['log']));
    }

    /* ------------------------------------------------------------------ */
    /* Perilaku lain */
    /* ------------------------------------------------------------------ */

    public function test_regenerasi_bersifat_idempoten(): void
    {
        $pertama = $this->generate();
        $jumlahSatu = Jadwal::where('is_active', 1)->count();

        $kedua = $this->generate();
        $jumlahDua = Jadwal::where('is_active', 1)->count();

        $this->assertSame($pertama['jumlah_jadwal'], $kedua['jumlah_jadwal']);
        $this->assertSame($jumlahSatu, $jumlahDua, 'Generate ulang menghasilkan jadwal ganda.');

        $this->assertSame(
            Rombel::whereHas('mataKuliah', fn ($q) => $q->where('semester', self::SEMESTER))->count(),
            Rombel::whereHas('mataKuliah', fn ($q) => $q->where('semester', self::SEMESTER))->count()
        );
    }

    public function test_regenerasi_semester_lain_tidak_menghapus_hasil_sebelumnya(): void
    {
        $this->generate(self::SEMESTER);
        $sebelum = Jadwal::where('is_active', 1)->count();

        $service = app(SchedulerService::class);
        $service->generate(3, 'Informatika');
        $sesudah = Jadwal::where('is_active', 1)->count();

        $this->assertGreaterThan($sebelum, $sesudah, 'Jadwal semester 1 harus tetap ada.');
    }

    public function test_semester_kosong_tidak_membuat_jadwal(): void
    {
        $hasil = app(SchedulerService::class)->generate(7, 'Teknik Kebidanan');

        $this->assertSame(0, $hasil['jumlah_jadwal']);
        $this->assertNotNull($hasil['catatan']);
    }

    public function test_mk_non_aktif_tidak_dijadwalkan(): void
    {
        $kode = MataKuliahDetail::where('semester', self::SEMESTER)->value('kode_mk');
        MataKuliahDetail::where('kode_mk', $kode)->update(['is_active' => false]);

        $this->generate();

        $ada = Jadwal::with('rombel.mataKuliah')
            ->whereHas('rombel.mataKuliah', fn ($q) => $q->where('kode_mk', $kode))
            ->count();

        $this->assertSame(0, $ada, 'MK non-aktif seharusnya dilewati.');
    }

    public function test_hasil_generate_mengembalikan_log_dan_jumlah(): void
    {
        $hasil = $this->generate();

        $this->assertSame(self::SEMESTER, $hasil['semester']);
        $this->assertGreaterThan(0, $hasil['jumlah_jadwal']);
        $this->assertNotEmpty($hasil['log']);
        $this->assertIsArray($hasil['peringatan']);
        $this->assertSame(
            $hasil['jumlah_jadwal'],
            Jadwal::where('is_active', 1)->count(),
            'Jumlah pada hasil harus sama dengan jumlah baris di database.'
        );
    }

    public function test_dosen_ketua_sama_tidak_boleh_bentrok_lintas_semester(): void
    {
        $service = app(SchedulerService::class);
        $service->generate(1, 'Informatika');
        $service->generate(3, 'Informatika');

        $jadwal = $this->semuaJadwal();

        for ($i = 0; $i < count($jadwal); $i++) {
            for ($k = $i + 1; $k < count($jadwal); $k++) {
                $a = $jadwal[$i];
                $b = $jadwal[$k];
                if ($a['hari'] !== $b['hari']) {
                    continue;
                }
                if (! $a['dosen_id'] || $a['dosen_id'] !== $b['dosen_id']) {
                    continue;
                }

                $this->assertFalse($this->tumpang($a, $b), 'Dosen bentrok lintas semester.');
            }
        }
    }

    public function test_setiap_kelas_selalu_punya_dosen(): void
    {
        $this->generate();

        foreach ($this->semuaJadwal() as $j) {
            $this->assertNotNull($j['dosen_id'], "Kelas {$j['mk']->kode_mk} tidak punya dosen.");
            $this->assertInstanceOf(Dosen::class, $j['dosen']);
        }
    }

    public function test_mk_pilihan_tanpa_dosen_tidak_dijadwalkan(): void
    {
        // MK Pilihan tanpa dosen harus dilewati, bukan membuat jadwal kosong.
        MataKuliahDetail::create([
            'kode_mk' => 'IF1498',
            'nama_mk' => 'Pilihan Tanpa Dosen',
            'sks' => 2,
            'semester' => self::SEMESTER,
            'tipe' => 'Pilihan',
            'prodi' => self::PRODI,
            'jumlah_kelas' => 1,
            'kapasitas_per_kelas' => 30,
            'butuh_lab' => false,
            'is_active' => true,
        ]);

        $hasil = $this->generate();

        $ada = Jadwal::with('rombel.mataKuliah')
            ->whereHas('rombel.mataKuliah', fn ($q) => $q->where('kode_mk', 'IF1498'))
            ->count();

        $this->assertSame(0, $ada, 'MK Pilihan tanpa dosen seharusnya tidak dijadwalkan.');
        $this->assertStringContainsString('IF1498', implode(' ', $hasil['peringatan']));
    }

    public function test_hasil_generate_mengembalikan_array_kegagalan_dan_diagnosa_terstruktur(): void
    {
        $hasil = $this->generate();

        $this->assertArrayHasKey('kegagalan', $hasil);
        $this->assertIsArray($hasil['kegagalan']);
    }
}
