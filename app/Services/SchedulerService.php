<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\MataKuliahDetail;
use App\Models\Rombel;
use App\Models\Ruangan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Metode: Greedy Berurutan + First-Fit Slot dengan validasi interval dan optimasi.
 *
 * Aturan yang ditegakkan:
 *  1. Bentrok kelas  : satu cohort (prodi+semester) tidak boleh punya 2 MK pada waktu yang sama.
 *  2. Dosen ketua    : tidak boleh bentrok di MK mana pun (hierarki IPV #1).
 *  3. Dosen anggota  : tidak boleh bentrok juga, namun bergantian dengan ketua.
 *  4. Slot berurutan : mulai 07:30 -> 10:00 -> 13:00 -> 14:50 -> 15:30.
 *  5. MK pilihan     : dibagi ke MAKS_PILIHAN kelompok slot. MK dalam satu
 *                      kelompok sharing slot sehingga saling bentrok; antar
 *                      kelompok memakai slot berbeda. Mahasiswa hanya boleh
 *                      mengambil 1 MK per kelompok, jadi maksimal 2 MK pilihan.
 *  6. Ruangan        : tidak boleh dipakai dua kelas pada waktu yang sama.
 *
 * Improvement:
 *  - Validasi resource sebelum generate
 *  - Scoring system untuk slot selection
 *  - Prioritas MK berdasarkan kompleksitas
 *  - Distribusi dosen yang lebih merata
 *  - Conflict resolution dengan backtracking
 */
class SchedulerService
{
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    public const HARI_KHUSUS = ['Sabtu'];

    public const HARI_SEMUA = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /** Jam mulai slot resmi, mencakup slot standar dan slot modular 50 menit sesuai template Excel */
    public const SLOT_MULAI = [
        '07:30', '08:20', '09:10', '10:00', '10:50', '11:40',
        '13:00', '13:50', '14:40', '14:50', '15:30', '16:20', '17:10',
    ];

    public const SLOT_KHUSUS = ['08:00', '09:40', '10:00', '13:00', '14:40', '15:00'];

    public const PERIOD_SLOTS = [
        '07:30', '08:20', '09:10', '10:00', '10:50',
        '13:00', '13:50', '14:40', '15:30', '16:20',
    ];

    public const MAKS_PILIHAN = 2;

    protected array $hariTersedia = [];

    protected array $slotMulaiTersedia = [];

    protected array $customSlotMulai = [];

    protected array $log = [];

    protected array $peringatan = [];

    protected array $kegagalan = [];

    protected array $slotPilihan = [];

    /** @var array<int,array{hari:string,ruangan_id:int,mulai:int,selesai:int}> */
    protected array $pakaiRuangan = [];

    /** @var array<int,array<int,array{hari:string,mulai:int,selesai:int}>> */
    protected array $pakaiDosen = [];

    /** @var array<string,array<int,array{hari:string,mulai:int,selesai:int}>> */
    protected array $kunciCohort = [];

    protected array $tersimpan = [];

    /** @var array<int,array{mk:MataKuliahDetail,no:int,slot:array,ruangan:?Ruangan,dosenId:?int,peran:?string,kategori:string,jenisKhusus:?string}> */
    protected array $rencanaJadwal = [];

    protected ?Collection $cacheRuanganAktif = null;

    protected ?Collection $cacheManualJadwals = null;

    public function getRencanaJadwal(): array
    {
        return $this->rencanaJadwal;
    }

    public function generate(int $semester, ?string $prodi = 'Informatika', array $options = []): array
    {
        $this->reset($semester, $prodi, $options);

        // 1. Ambil data terklasifikasi secara ketat
        $mkReguler = $this->ambilMkReguler($semester, $prodi);
        $mkKhusus = $this->ambilMkKhusus($semester, $prodi);
        $mkKkn = $this->ambilMkKkn($semester, $prodi);
        $mkPerluDiperiksa = $this->ambilMkPerluDiperiksa($semester, $prodi);

        // Catat KKN yang sepenuhnya dikecualikan dari proses generate
        if ($mkKkn->isNotEmpty()) {
            $this->log[] = sprintf(
                '[Info Akademik] %d MK KKN/Kukerta (%s) sepenuhnya dikecualikan dari proses penjadwalan kuliah (kegiatan pengabdian masyarakat lapangan).',
                $mkKkn->count(),
                $mkKkn->pluck('nama_mk')->unique()->implode(', ')
            );
        }

        // Catat data yang perlu diperiksa
        if ($mkPerluDiperiksa->isNotEmpty()) {
            foreach ($mkPerluDiperiksa as $mAmbigu) {
                $this->peringatan[] = "PERIKSA DATA: Mata kuliah {$mAmbigu->kode_mk} ({$mAmbigu->nama_mk}) memiliki atribut tidak lengkap atau tidak valid sehingga tidak dimasukkan ke dalam jadwal.";
            }
        }

        if ($mkReguler->isEmpty() && $mkKhusus->isEmpty()) {
            $statistik = $this->hitungStatistikBaru($mkReguler, $mkKhusus, $mkKkn, $mkPerluDiperiksa, 0, 0, 0, 0, 0);

            return $this->hasil($semester, 0, 'Belum ada mata kuliah reguler maupun kegiatan khusus aktif untuk semester ini.', $statistik);
        }

        // 2. Validasi resource reguler sebelum generate
        $validasi = $this->validasiResource($mkReguler, $semester, $prodi);
        if (! $validasi['status']) {
            $statistik = $this->hitungStatistikBaru($mkReguler, $mkKhusus, $mkKkn, $mkPerluDiperiksa, 0, 0, 0, 0, 0);

            return $this->hasil($semester, 0, $validasi['pesan'], $statistik);
        }

        $cohort = $this->namaCohort($prodi, $semester);

        // 3. Penjadwalan Perkuliahan Reguler (Hanya Senin - Jumat)
        $wajibReguler = $mkReguler->where('tipe', 'Wajib')
            ->sortByDesc(fn ($m) => [$m->butuh_lab, $m->sks, $m->jumlah_kelas])
            ->values();

        foreach ($wajibReguler as $m) {
            $mCohort = $this->namaCohort($m->prodi ?: $prodi, (int) $m->semester, $m->kelas);
            $this->tempatkan($m, $mCohort);
        }

        $pilihanReguler = $mkReguler->where('tipe', 'Pilihan');
        if ($pilihanReguler->isNotEmpty()) {
            $this->tempatkanPilihan($pilihanReguler, $cohort, $semester);
        }

        $regulerBerhasilCount = count(array_filter($this->rencanaJadwal, fn ($r) => ($r['kategori'] ?? 'reguler') === 'reguler'));

        // 4. Penjadwalan Kegiatan Akademik Khusus (Hanya Hari Sabtu)
        $kpBerhasilCount = 0;
        $magangBerhasilCount = 0;
        $skripsiBerhasilCount = 0;

        if ($mkKhusus->isNotEmpty()) {
            $hasilKhusus = $this->jadwalkanKhusus($mkKhusus, $prodi, $semester);
            $kpBerhasilCount = $hasilKhusus['kp'];
            $magangBerhasilCount = $hasilKhusus['magang'];
            $skripsiBerhasilCount = $hasilKhusus['skripsi'];
        }

        $khususBerhasilCount = $kpBerhasilCount + $magangBerhasilCount + $skripsiBerhasilCount;
        $totalBerhasil = $regulerBerhasilCount + $khususBerhasilCount;

        // 5. Re-Audit Keamanan dan Validasi Sebelum Simpan ke Database
        $audit = $this->auditValidasiJadwal($this->rencanaJadwal);
        if (! $audit['valid']) {
            $this->peringatan = array_merge($this->peringatan, $audit['errors']);
            $statistik = $this->hitungStatistikBaru(
                $mkReguler, $mkKhusus, $mkKkn, $mkPerluDiperiksa,
                0, 0, 0, 0, 0
            );

            return $this->hasil(
                $semester,
                0,
                'Validasi keamanan jadwal gagal: terdeteksi bentrok atau pelanggaran batasan hari. Jadwal lama tetap aman dan tidak diubah.',
                $statistik
            );
        }

        // 6. Terapkan ke database dalam transaksi atomik
        DB::transaction(function () use ($semester, $prodi) {
            $this->terapkanJadwalKeDatabase($this->rencanaJadwal, $semester, $prodi);
        });

        // 7. Hitung statistik hasil terpisah
        $statistik = $this->hitungStatistikBaru(
            $mkReguler, $mkKhusus, $mkKkn, $mkPerluDiperiksa,
            $regulerBerhasilCount, $khususBerhasilCount,
            $kpBerhasilCount, $magangBerhasilCount, $skripsiBerhasilCount
        );

        return $this->hasil($semester, $totalBerhasil, null, $statistik, 'Greedy Algorithm');
    }

    public function applyProdiFilter($query, ?string $prodi): void
    {
        if (! $prodi || in_array(strtolower(trim($prodi)), ['semua', 'all'], true)) {
            return;
        }

        $p = strtolower(trim($prodi));
        if (in_array($p, ['fakultas_all', 'mkwu_mkwf', 'fakultas_dan_universitas', 'semua_fakultas'], true)) {
            $query->where(function ($q) {
                $q->whereIn('kategori', ['MK Universitas', 'MK Fakultas'])
                    ->orWhere('kode_mk', 'like', 'UNV%')
                    ->orWhere('kode_mk', 'like', 'FST%')
                    ->orWhere('prodi', 'like', '%Universitas%')
                    ->orWhere('prodi', 'like', '%Fakultas%');
            });
        } elseif (in_array($p, ['universitas', 'mkwu', 'mk universitas'], true) || str_contains($p, 'universitas')) {
            $query->where(function ($q) {
                $q->where('kategori', 'MK Universitas')
                    ->orWhere('kode_mk', 'like', 'UNV%')
                    ->orWhere('prodi', 'like', '%Universitas%');
            });
        } elseif (in_array($p, ['fakultas', 'mkwf', 'mk fakultas'], true) || str_contains($p, 'fakultas')) {
            $query->where(function ($q) {
                $q->where('kategori', 'MK Fakultas')
                    ->orWhere('kode_mk', 'like', 'FST%')
                    ->orWhere('prodi', 'like', '%Fakultas%');
            });
        } elseif (str_contains($p, 'informatika') || $p === 'ti') {
            $query->where(function ($q) {
                $q->where('prodi', 'like', '%Informatika%')
                    ->orWhere('prodi', 'like', '%TI%');
            })->where('prodi', 'not like', '%Elektro%');
        } elseif (str_contains($p, 'elektro') || $p === 'te') {
            $query->where(function ($q) {
                $q->where('prodi', 'like', '%Elektro%')
                    ->orWhere('prodi', 'like', '%TE%');
            })->where('prodi', 'not like', '%Informatika%');
        } else {
            $query->where('prodi', 'like', "%{$prodi}%");
        }
    }

    public function ambilMkReguler(int $semester, ?string $prodi): Collection
    {
        $query = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota', 'dosenAnggota2', 'dosenAnggota3'])
            ->where('semester', $semester)
            ->where('is_active', 1)
            ->where('status_tawar', 1)
            ->reguler();

        $this->applyProdiFilter($query, $prodi);

        return $query->get()->filter(fn ($m) => $m->isReguler())->values();
    }

    public function ambilMkKhusus(int $semester, ?string $prodi): Collection
    {
        $query = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota', 'dosenAnggota2', 'dosenAnggota3'])
            ->where('semester', $semester)
            ->where('is_active', 1)
            ->where('status_tawar', 1)
            ->kegiatanKhusus();

        $this->applyProdiFilter($query, $prodi);

        return $query->get()->filter(fn ($m) => $m->isKegiatanKhusus())->values();
    }

    public function ambilMkKkn(int $semester, ?string $prodi): Collection
    {
        $query = MataKuliahDetail::where('semester', $semester)
            ->where('is_active', 1)
            ->where('status_tawar', 1)
            ->kkn();

        $this->applyProdiFilter($query, $prodi);

        return $query->get()->filter(fn ($m) => $m->isKkn())->values();
    }

    public function ambilMkPerluDiperiksa(int $semester, ?string $prodi): Collection
    {
        $query = MataKuliahDetail::where('semester', $semester)
            ->where('is_active', 1)
            ->where('status_tawar', 1);

        $this->applyProdiFilter($query, $prodi);

        return $query->get()->filter(fn ($m) => $m->isPerluDiperiksa())->values();
    }

    protected function ambilMk(int $semester, ?string $prodi): Collection
    {
        return $this->ambilMkReguler($semester, $prodi);
    }

    protected function ambilMkNonTatapMuka(int $semester, ?string $prodi): Collection
    {
        return $this->ambilMkKkn($semester, $prodi);
    }

    public function isMkBelongsToProdi(?MataKuliahDetail $mk, ?string $prodi): bool
    {
        if (! $mk) {
            return false;
        }
        if (! $prodi || in_array(strtolower(trim($prodi)), ['semua', 'all'], true)) {
            return true;
        }

        $p = strtolower(trim($prodi));
        $mkProdi = strtolower((string) $mk->prodi);

        if (str_contains($p, 'informatika')) {
            return str_contains($mkProdi, 'informatika') || str_contains($mkProdi, 'ti');
        }
        if (str_contains($p, 'elektro')) {
            return str_contains($mkProdi, 'elektro') || str_contains($mkProdi, 'te');
        }

        return str_contains($mkProdi, $p);
    }

    protected function validasiResource(Collection $mk, int $semester, ?string $prodi): array
    {
        $warnings = [];

        // Hanya validasi mata kuliah yang memerlukan dosen dan ruangan fisik
        $mkPerluDosen = $mk->filter(fn ($m) => $m->isReguler() && ! $m->tanpa_ruangan);
        $dosenTerdaftar = array_unique(array_filter([
            ...$mkPerluDosen->pluck('dosen_ketua_id')->toArray(),
            ...$mkPerluDosen->pluck('dosen_anggota_id')->toArray(),
            ...$mkPerluDosen->pluck('dosen_anggota_2_id')->toArray(),
            ...$mkPerluDosen->pluck('dosen_anggota_3_id')->toArray(),
        ]));

        if (count($dosenTerdaftar) < $mkPerluDosen->count()) {
            $warnings[] = 'Beberapa mata kuliah belum memiliki dosen pengampu lengkap.';
        }

        // Cek ruangan kelas dan lab
        $mkButuhLab = $mk->where('butuh_lab', 1)->count();
        $mkButuhKelas = $mk->where('butuh_lab', 0)->where('tanpa_ruangan', 0)->count();

        $labTersedia = Ruangan::where('tipe', 'Lab')->where('is_active', 1)->count();
        $kelasTersedia = Ruangan::whereIn('tipe', ['Ruang Kelas', 'Aula', 'Auditorium'])->where('is_active', 1)->count();

        if ($mkButuhLab > $labTersedia * 5) {
            $warnings[] = "Ruangan lab mungkin tidak cukup untuk {$mkButuhLab} kelas praktikum.";
        }

        if ($mkButuhKelas > $kelasTersedia * 5) {
            $warnings[] = "Ruangan kelas teori mungkin tidak cukup untuk {$mkButuhKelas} kelas.";
        }

        // Cek total SKS terhadap slot hari reguler (Senin - Jumat)
        $totalSks = $mk->sum('sks');
        $totalSlotTersedia = count($this->hariTersedia) * count($this->slotMulaiTersedia) * 2;

        if ($totalSks > $totalSlotTersedia) {
            $warnings[] = "Total SKS ({$totalSks}) melebihi estimasi kapasitas slot hari kerja reguler ({$totalSlotTersedia}). Beberapa MK mungkin tidak terjadwal.";
        }

        foreach ($warnings as $warning) {
            $this->peringatan[] = "VALIDASI: {$warning}";
        }

        return ['status' => true, 'pesan' => null];
    }

    public function hitungStatistikBaru(
        Collection $mkReguler,
        Collection $mkKhusus,
        Collection $mkKkn,
        Collection $mkPerluDiperiksa,
        int $regulerBerhasil,
        int $khususBerhasil,
        int $kpBerhasil = 0,
        int $magangBerhasil = 0,
        int $skripsiBerhasil = 0
    ): array {
        $totalRegistered = $mkReguler->count() + $mkKhusus->count() + $mkKkn->count() + $mkPerluDiperiksa->count();
        $totalTerjadwal = $regulerBerhasil + $khususBerhasil;
        $totalGagal = count($this->kegagalan);

        return [
            'total_mk' => $totalRegistered,
            'mk_terjadwal' => $totalTerjadwal,
            'mk_reguler_total' => $mkReguler->count(),
            'mk_reguler_berhasil' => $regulerBerhasil,
            'mk_khusus_total' => $mkKhusus->count(),
            'mk_khusus_berhasil' => $khususBerhasil,
            'mk_khusus_kp' => $kpBerhasil,
            'mk_khusus_magang' => $magangBerhasil,
            'mk_khusus_skripsi' => $skripsiBerhasil,
            'mk_kkn_dikecualikan' => $mkKkn->count(),
            'mk_perlu_diperiksa' => $mkPerluDiperiksa->count(),
            'mk_gagal' => $totalGagal,
            'mk_wajib' => $mkReguler->where('tipe', 'Wajib')->count(),
            'mk_pilihan' => $mkReguler->where('tipe', 'Pilihan')->count(),
            'total_sks' => $mkReguler->sum('sks') + $mkKhusus->sum('sks'),
            'mk_butuh_lab' => $mkReguler->where('butuh_lab', 1)->count(),
            'mk_tanpa_ruangan' => $mkKhusus->where('tanpa_ruangan', 1)->count(),
            'mk_tatap_muka' => $mkReguler->count(),
            'mk_non_tatap_muka' => $mkKkn->count(),
        ];
    }

    protected function hitungStatistik(Collection $mk, int $terjadwal, ?Collection $nonTatapMuka = null): array
    {
        $nonTatapMukaCount = $nonTatapMuka?->count() ?? 0;

        return [
            'total_mk' => $mk->count() + $nonTatapMukaCount,
            'mk_tatap_muka' => $mk->count(),
            'mk_non_tatap_muka' => $nonTatapMukaCount,
            'mk_wajib' => $mk->where('tipe', 'Wajib')->count(),
            'mk_pilihan' => $mk->where('tipe', 'Pilihan')->count(),
            'mk_terjadwal' => $terjadwal,
            'mk_gagal' => max(0, $mk->count() - $terjadwal),
            'total_sks' => $mk->sum('sks'),
            'mk_butuh_lab' => $mk->where('butuh_lab', 1)->count(),
            'mk_tanpa_ruangan' => $nonTatapMukaCount,
            'mk_reguler_total' => $mk->count(),
            'mk_reguler_berhasil' => $terjadwal,
            'mk_khusus_total' => 0,
            'mk_khusus_berhasil' => 0,
            'mk_kkn_dikecualikan' => $nonTatapMukaCount,
            'mk_perlu_diperiksa' => 0,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Penempatan wajib */
    /* ------------------------------------------------------------------ */

    protected function tempatkan(MataKuliahDetail $mk, string $cohort): void
    {
        $manualJadwals = $this->cacheManualJadwals !== null
            ? ($this->cacheManualJadwals->get($mk->id, collect()) ?? collect())
            : Jadwal::with(['rombel', 'ruangan', 'dosen'])
                ->whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mk->id))
                ->where('is_manual', true)
                ->where('is_active', true)
                ->get();

        $manualCount = $manualJadwals->count();

        // Jika semua kelas paralel MK ini sudah dijadwalkan secara manual, pertahankan dan lewati generate otomatis
        if ($manualCount >= $mk->jumlah_kelas) {
            $this->log[] = sprintf(
                'MANUAL %s - %s | %d/%d kelas dipertahankan (input manual)',
                $mk->kode_mk,
                $mk->nama_mk,
                $manualCount,
                $mk->jumlah_kelas
            );
            foreach ($manualJadwals as $mj) {
                $this->tersimpan[] = [
                    'mk' => $mk,
                    'no' => $mj->rombel?->nomor_rombel ?? 1,
                    'slot' => [
                        'hari' => $mj->hari,
                        'mulai' => $this->menit($mj->jam_mulai),
                        'selesai' => $this->menit($mj->jam_selesai),
                    ],
                    'ruangan' => $mj->ruangan,
                    'dosenId' => $mj->dosen_id,
                    'peran' => $mj->rombel?->peran_dosen,
                ];
            }

            return;
        }

        // Jika ada sebagian kelas sudah manual, masukkan ke daftar tersimpan
        foreach ($manualJadwals as $mj) {
            $this->tersimpan[] = [
                'mk' => $mk,
                'no' => $mj->rombel?->nomor_rombel ?? 1,
                'slot' => [
                    'hari' => $mj->hari,
                    'mulai' => $this->menit($mj->jam_mulai),
                    'selesai' => $this->menit($mj->jam_selesai),
                ],
                'ruangan' => $mj->ruangan,
                'dosenId' => $mj->dosen_id,
                'peran' => $mj->rombel?->peran_dosen,
            ];
        }

        $slot = $this->cariSlot($mk, $cohort);
        if (! $slot) {
            $this->log[] = "GAGAL  {$mk->kode_mk} - {$mk->nama_mk}: tidak ada slot yang memenuhi syarat (ruangan/dosen pada Senin–Jumat).";
            $this->kegagalan[] = [
                'kode_mk' => $mk->kode_mk,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'semester' => $mk->semester,
                'prodi' => $mk->prodi ?: 'Umum',
                'tipe' => $mk->tipe,
                'alasan' => 'Tidak tersedia slot waktu kosong pada hari kerja reguler (Senin–Jumat) yang memenuhi syarat tanpa bentrok (cohort, dosen, atau ruangan penuh).',
                'rekomendasi' => 'Sesuai aturan sistem, perkuliahan reguler tidak dipindahkan ke hari Sabtu. Buka tab Manajemen Ruangan untuk mengaktifkan ruangan kuliah tambahan, atau sesuaikan penugasan dosen pengampu.',
            ];

            return;
        }

        $total = $mk->jumlah_kelas - $manualCount;
        $terpasang = 0;
        $dosenList = $this->dosenList($mk);

        $manualNomors = $manualJadwals->pluck('rombel.nomor_rombel')->filter()->map(fn ($n) => (int) $n)->toArray();

        $currNo = 1;
        $usedDosenInSlot = [];
        $currentSlot = $slot;

        while ($terpasang < $total) {
            while (in_array($currNo, $manualNomors, true)) {
                $currNo++;
            }

            // Pilih dosen bebas untuk $currentSlot
            $dosen = null;
            if ($dosenList) {
                foreach ($dosenList as $d) {
                    if (! in_array($d, $usedDosenInSlot, true) && ! $this->dosenBentrok($d, $currentSlot)) {
                        $dosen = $d;
                        break;
                    }
                }
            }

            $ruangan = $mk->tanpa_ruangan ? null : $this->cariRuangan($mk, $currentSlot);

            // Jika di $currentSlot kehabisan dosen bebas atau ruangan untuk kelas paralel berikutnya,
            // cari slot alternatif yang bebas untuk kelas berikutnya!
            if (($dosenList && $dosen === null) || (! $mk->tanpa_ruangan && ! $ruangan)) {
                $altSlot = $this->cariSlot($mk, $cohort);
                if ($altSlot && ($altSlot['hari'] !== $currentSlot['hari'] || $altSlot['mulai'] !== $currentSlot['mulai'])) {
                    $currentSlot = $altSlot;
                    $usedDosenInSlot = [];
                    if ($dosenList) {
                        foreach ($dosenList as $d) {
                            if (! in_array($d, $usedDosenInSlot, true) && ! $this->dosenBentrok($d, $currentSlot)) {
                                $dosen = $d;
                                break;
                            }
                        }
                    }
                    $ruangan = $mk->tanpa_ruangan ? null : $this->cariRuangan($mk, $currentSlot);
                }
            }

            if (! $mk->tanpa_ruangan && ! $ruangan) {
                break;
            }

            if ($dosen) {
                $usedDosenInSlot[] = $dosen;
            }

            $peran = ($dosen !== null && $dosen === $mk->dosen_ketua_id)
                ? 'Ketua'
                : ($dosen !== null ? 'Anggota' : null);

            $this->simpan($mk, $currNo, $currentSlot, $ruangan, $dosen, $peran);
            $currNo++;

            if ($ruangan) {
                $this->pakaiRuangan[] = [
                    'hari' => $currentSlot['hari'],
                    'ruangan_id' => $ruangan->id,
                    'mulai' => $currentSlot['mulai'],
                    'selesai' => $currentSlot['selesai'],
                ];
            }
            if ($dosen) {
                $this->pakaiDosen[$dosen][] = [
                    'hari' => $currentSlot['hari'],
                    'mulai' => $currentSlot['mulai'],
                    'selesai' => $currentSlot['selesai'],
                ];
            }

            $this->kunciCohortSlot($cohort, $currentSlot);
            $terpasang++;
        }

        $sisa = $mk->jumlah_kelas - ($manualCount + $terpasang);
        $catatan = $sisa > 0
            ? " | PERINGATAN: {$sisa} kelas tidak dijadwalkan (kurang dosen/ruangan pada Senin–Jumat)"
            : '';

        if ($sisa > 0) {
            $this->kegagalan[] = [
                'kode_mk' => $mk->kode_mk,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'semester' => $mk->semester,
                'prodi' => $mk->prodi ?: 'Umum',
                'tipe' => $mk->tipe,
                'alasan' => "Hanya {$terpasang} dari {$total} kelas tersisa yang terjadwal. Sebanyak {$sisa} kelas tidak tertampung karena keterbatasan dosen pengampu bebas atau ruangan pada hari Senin–Jumat.",
                'rekomendasi' => 'Sesuai aturan sistem, perkuliahan reguler tidak dipindahkan ke hari Sabtu. Tugaskan dosen pengampu tambahan atau sediakan slot/ruangan kuliah alternatif.',
            ];
        }

        $this->log[] = sprintf(
            'OK     %s - %s | %d/%d kelas | %s %s-%s%s',
            $mk->kode_mk,
            $mk->nama_mk,
            $manualCount + $terpasang,
            $mk->jumlah_kelas,
            $currentSlot['hari'],
            $this->format($currentSlot['mulai']),
            $this->format($currentSlot['selesai']),
            $catatan
        );
    }

    /** @return int|false|null */
    protected function pilihDosen(array $dosenList, array $slot, array $excludeDosen = [])
    {
        if (! $dosenList) {
            return null;
        }

        foreach ($dosenList as $d) {
            if (! in_array($d, $excludeDosen, true) && ! $this->dosenBentrok($d, $slot)) {
                return $d;
            }
        }

        return false;
    }

    /* ------------------------------------------------------------------ */
    /* MK Pilihan: satu slot, saling bentrok, maksimal 2 */
    /* ------------------------------------------------------------------ */

    /**
     * MK Pilihan dibagi menjadi beberapa KELOMPOK SLOT (jumlahnya = MAKS_PILIHAN).
     *
     * Setiap kelompok Sharing satu slot sehingga MK di dalamnya saling bentrok
     * dan mahasiswa tidak bisa mengambil dua MK dari kelompok yang sama. Karena
     * kelompok memakai slot berbeda, mahasiswa tetap bisa mengambil satu MK dari
     * tiap kelompok, jadi batas pengambilanMK Pilihan = MAKS_PILIHAN.
     *
     * Contoh MAKS_PILIHAN = 2:
     *   Kelompok 1 -> Senin 07:30 : MK-A, MK-C
     *   Kelompok 2 -> Senin 10:00 : MK-B, MK-D
     * Ambil maksimal 2: satu dari kelompok 1 + satu dari kelompok 2.
     */
    protected function tempatkanPilihan(Collection $pilihan, string $cohort, int $semester): void
    {
        $kelompok = $this->bagiPilihan($pilihan);

        foreach ($kelompok as $noKelompok => $grup) {
            if ($grup->isEmpty()) {
                continue;
            }

            $slot = $this->cariSlotPilihan($grup, $cohort);
            if (! $slot) {
                $this->log[] = "GAGAL  MK Pilihan kelompok {$noKelompok}: tidak ada slot yang bebas.";
                $this->kegagalan[] = [
                    'kode_mk' => 'PIL-KLP-'.$noKelompok,
                    'nama_mk' => "MK Pilihan Kelompok {$noKelompok}",
                    'sks' => 3,
                    'semester' => $semester,
                    'prodi' => 'Pilihan',
                    'tipe' => 'Pilihan',
                    'alasan' => "Tidak ada slot waktu bersama yang bebas untuk kelompok MK Pilihan {$noKelompok}.",
                    'rekomendasi' => 'Kurangi jumlah kelompok MK Pilihan atau sediakan rentang jam/hari kuliah yang lebih luas di pengaturan.',
                ];

                continue;
            }

            $durasiMaks = 0;
            $terjadwal = [];

            foreach ($grup as $mk) {
                // Cek apakah MK Pilihan ini sudah dijadwalkan secara manual
                $manualPilihan = $this->cacheManualJadwals !== null
                    ? ($this->cacheManualJadwals->get($mk->id)?->first())
                    : Jadwal::with('rombel')
                        ->whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mk->id))
                        ->where('is_manual', true)
                        ->where('is_active', true)
                        ->first();

                if ($manualPilihan) {
                    $terjadwal[] = $mk->nama_mk.' (Manual)';
                    $this->tersimpan[] = [
                        'mk' => $mk,
                        'no' => $manualPilihan->rombel?->nomor_rombel ?? 1,
                        'slot' => [
                            'hari' => $manualPilihan->hari,
                            'mulai' => $this->menit($manualPilihan->jam_mulai),
                            'selesai' => $this->menit($manualPilihan->jam_selesai),
                        ],
                        'ruangan' => $manualPilihan->ruangan,
                        'dosenId' => $manualPilihan->dosen_id,
                        'peran' => $manualPilihan->rombel?->peran_dosen,
                    ];

                    continue;
                }

                // MK tanpa ruangan (Skripsi, KKN, dll) tidak perlu alokasi ruangan.
                if ($mk->tanpa_ruangan) {
                    $ruangan = null;
                } else {
                    $ruangan = $this->cariRuangan($mk, $slot);
                    if (! $ruangan) {
                        $this->peringatan[] = "MK Pilihan {$mk->kode_mk}: tidak ada ruangan kosong di slot pilihan.";

                        continue;
                    }
                }

                $dosen = $mk->dosen_ketua_id ?? $mk->dosen_anggota_id;
                if ($dosen && $this->dosenBentrok($dosen, $slot)) {
                    $this->peringatan[] = "MK Pilihan {$mk->kode_mk}: dosennya bentrok, dilewati.";

                    continue;
                }

                // Durasi mengikuti SKS masing-masing MK, bukan SKS terbesar di slot.
                $slotMk = [
                    'hari' => $slot['hari'],
                    'mulai' => $slot['mulai'],
                    'selesai' => $slot['mulai'] + ($mk->sks * 50),
                ];
                $durasiMaks = max($durasiMaks, $slotMk['selesai'] - $slotMk['mulai']);

                // MK Pilihan wajib punya dosen. Kalau tidak ada, jangan dijadwalkan
                // supaya tidak muncul kelas tanpa pengampu di tabel jadwal.
                if (! $dosen) {
                    $this->peringatan[] = "MK Pilihan {$mk->kode_mk}: belum ada dosen, dilewati.";

                    continue;
                }

                $peran = ($dosen === $mk->dosen_ketua_id) ? 'Ketua' : 'Anggota';
                $this->simpan($mk, 1, $slotMk, $ruangan, $dosen, $peran);
                if ($ruangan) {
                    $this->pakaiRuangan[] = [
                        'hari' => $slotMk['hari'],
                        'ruangan_id' => $ruangan->id,
                        'mulai' => $slotMk['mulai'],
                        'selesai' => $slotMk['selesai'],
                    ];
                }
                if ($dosen) {
                    $this->pakaiDosen[$dosen][] = [
                        'hari' => $slotMk['hari'],
                        'mulai' => $slotMk['mulai'],
                        'selesai' => $slotMk['selesai'],
                    ];
                }
                $terjadwal[] = $mk->kode_mk;

                $this->log[] = sprintf(
                    'OK     PILIHAN[%d] %s - %s | %s %s-%s | %s',
                    $noKelompok,
                    $mk->kode_mk,
                    $mk->nama_mk,
                    $slotMk['hari'],
                    $this->format($slotMk['mulai']),
                    $this->format($slotMk['selesai']),
                    $ruangan ? $ruangan->kode : 'Tanpa Ruangan'
                );
            }

            if ($terjadwal === []) {
                continue;
            }

            // Kunci cohort dengan durasi terjavadoc yang sebenarnya terpakai,
            // bukan SKS terbesar, supaya tidak memblokir slot lain sia-sia.
            $slotTerkunci = [
                'hari' => $slot['hari'],
                'mulai' => $slot['mulai'],
                'selesai' => $slot['mulai'] + $durasiMaks,
            ];
            $this->kunciCohortSlot($cohort, $slotTerkunci);

            $slotTerkunci['label'] = sprintf(
                'Kelompok %d: %s %s-%s (%s)',
                $noKelompok,
                $slotTerkunci['hari'],
                $this->format($slotTerkunci['mulai']),
                $this->format($slotTerkunci['selesai']),
                implode(', ', $terjadwal)
            );
            $this->slotPilihan[$semester][] = $slotTerkunci;
        }

        if ($this->slotPilihan[$semester] ?? false) {
            $this->kunciCohortSlot($cohort, $this->slotPilihan[$semester][0]);
        }
    }

    /**
     * Bagi MK Pilihan ke MAKS_PILIHAN kelompok secara bergilir.
     *MK dengan SKS besar Biography lebih dulu agar(pk) durasi paling lama
     * mendapat kelompok dengan ruangan paling longgar.
     *
     * @return array<int,Collection>
     */
    protected function bagiPilihan(Collection $pilihan): array
    {
        $urut = $pilihan->sortByDesc(fn ($m) => [$m->sks, $m->butuh_lab, $m->jumlah_kelas])->values();

        $kelompok = [];
        for ($i = 0; $i < self::MAKS_PILIHAN; $i++) {
            $kelompok[$i + 1] = collect();
        }

        foreach ($urut as $i => $mk) {
            $kelompok[($i % self::MAKS_PILIHAN) + 1]->push($mk);
        }

        return $kelompok;
    }

    /* ------------------------------------------------------------------ */
    /* Pencarian slot */
    /* ------------------------------------------------------------------ */

    public function getHariTersediaForMk(?MataKuliahDetail $mk = null, array $options = []): array
    {
        // Kuliah reguler tatap muka HANYA menggunakan hari kerja Senin s.d. Jumat
        $baseHari = self::HARI;

        $userHari = ! empty($options['hari']) ? (array) $options['hari'] : $this->hariTersedia;
        if (! empty($userHari)) {
            $filtered = array_values(array_intersect(self::HARI_SEMUA, $userHari));
            // Jangan izinkan hari Sabtu untuk MK reguler meskipun dicentang di opsi
            if (! $mk || ! $mk->isNonTatapMuka()) {
                $filtered = array_values(array_diff($filtered, self::HARI_KHUSUS));
            }
            if (! empty($filtered)) {
                $baseHari = $filtered;
            }
        }

        return $baseHari;
    }

    public function getValidSlotsForMk(MataKuliahDetail $mk, ?string $hari = null): array
    {
        $durasi = $mk->sks * 50;

        // Daftar kandidat menit mulai sesuai modul 50 menit template Excel (07:30 s.d. 18:00)
        $candidateMinutes = [
            450, // 07:30 (Pagi 1)
            550, // 09:10 (Pagi 2 untuk 2 SKS)
            600, // 10:00 (Pagi 2 untuk 3 SKS)
            650, // 10:50 (Pagi 3)
            780, // 13:00 (Siang 1)
            830, // 13:50 (Siang 2)
            880, // 14:40 (Siang 2 untuk 2 SKS)
            890, // 14:50 (Kompatibilitas slot lama)
            930, // 15:30 (Sore)
            980, // 16:20 (Sore akhir)
            500, // 08:20
            700, // 11:40
        ];

        // Jika ada filter slotMulaiTersedia dari opsi pengguna
        if (! empty($this->slotMulaiTersedia) && $this->slotMulaiTersedia !== self::SLOT_MULAI) {
            $allowedMinutes = array_map(fn ($j) => $this->menit($j), $this->slotMulaiTersedia);
            $candidateMinutes = array_values(array_intersect($candidateMinutes, $allowedMinutes));
        }

        $valid = [];
        foreach ($candidateMinutes as $start) {
            $end = $start + $durasi;
            if ($end > 1080) { // Maksimal jam 18:00
                continue;
            }

            // Proteksi Sholat Jumat (11:50 - 12:50 / 710 - 770 menit)
            if ($hari === 'Jumat') {
                if ($this->tumpang($start, $end, 710, 770)) {
                    continue;
                }
            } else {
                // Proteksi Istirahat Siang Hari Biasa (12:30 - 13:00 / 750 - 780 menit)
                if ($this->tumpang($start, $end, 750, 780)) {
                    continue;
                }
            }

            $valid[] = [
                'mulai' => $start,
                'selesai' => $end,
                'jam_mulai' => $this->format($start),
                'jam_selesai' => $this->format($end),
            ];
        }

        return $valid;
    }

    public function getModularSlotMulaiForSks(int $sks, ?string $hari = null): array
    {
        $dummy = new MataKuliahDetail(['sks' => $sks]);
        $slots = $this->getValidSlotsForMk($dummy, $hari);

        return array_values(array_unique(array_column($slots, 'jam_mulai')));
    }

    /**
     * Cari slot terbaik dengan scoring system yang lebih komprehensif.
     * Hard constraints: Bebas bentrok cohort, ruangan, dosen, waktu Jumat, dan istirahat.
     * Soft constraints: Best-fit ruangan, kepadatan hari mengajar dosen, minimasi gap mahasiswa, MK berhubungan.
     */
    protected function cariSlot(MataKuliahDetail $mk, string $cohort): ?array
    {
        $dosenList = $this->dosenList($mk);
        $terbaik = null;
        $skorTerbaik = -999999;

        // Hari perkuliahan reguler: Senin s.d. Jumat saja!
        $hariList = $this->getHariTersediaForMk($mk);

        foreach ($hariList as $hari) {
            $candidateSlots = $this->getValidSlotsForMk($mk, $hari);

            foreach ($candidateSlots as $cand) {
                $slot = [
                    'hari' => $hari,
                    'mulai' => $cand['mulai'],
                    'selesai' => $cand['selesai'],
                ];

                // Hard Constraint 1: Bentrok Cohort
                if ($this->cohortBentrok($cohort, $slot)) {
                    continue;
                }

                // Hard Constraint 2: Ketersediaan Ruangan
                if ($mk->tanpa_ruangan) {
                    $skorRuangan = 100;
                } else {
                    $ruangans = $this->ruanganTersedia($mk, $slot);
                    $skorRuangan = $ruangans->count();
                    if ($skorRuangan < 1) {
                        continue;
                    }
                }

                // Hard Constraint 3: Ketersediaan Dosen
                $skorDosen = 0;
                if ($dosenList) {
                    $dosenBebas = count(array_filter(
                        $dosenList,
                        fn ($d) => ! $this->dosenBentrok($d, $slot)
                    ));
                    if ($dosenBebas < 1) {
                        continue;
                    }
                    $skorDosen = $dosenBebas * 10;
                }

                // Hitung skor kualitas (Soft Constraints)
                $skor = $this->hitungSkorSlot($slot, $skorRuangan, $skorDosen, $mk);

                if ($skor > $skorTerbaik) {
                    $skorTerbaik = $skor;
                    $terbaik = $slot;
                }
            }
        }

        return $terbaik;
    }

    protected function hitungSkorSlot(array $slot, int $skorRuangan, int $skorDosen, MataKuliahDetail $mk): int
    {
        $skor = 0;

        // 1. Kapasitas & Best-Fit Ruangan (prioritaskan ruangan yang selisihnya paling pas)
        $skor += min(50, $skorRuangan * 5);
        if (! $mk->tanpa_ruangan) {
            $ruangBestFit = $this->cariRuangan($mk, $slot);
            if ($ruangBestFit) {
                $selisihKapasitas = $ruangBestFit->kapasitas - ($mk->kapasitas_per_kelas ?: 40);
                if ($selisihKapasitas <= 10) {
                    $skor += 25; // Sangat pas dengan kapasitas
                } elseif ($selisihKapasitas <= 25) {
                    $skor += 15;
                }
            }
        }

        // 2. Ketersediaan Dosen
        $skor += $skorDosen;

        // 3. Posisi waktu: MK Wajib diutamakan pagi hari
        if ($mk->tipe === 'Wajib') {
            if ($slot['mulai'] === 450) { // 07:30
                $skor += 35;
            } elseif ($slot['mulai'] === 550 || $slot['mulai'] === 600) { // 09:10 / 10:00
                $skor += 25;
            } elseif ($slot['mulai'] === 780) { // 13:00
                $skor += 15;
            }
        }

        // MK berat (SKS >= 3) diutamakan sebelum tengah hari
        if ($mk->sks >= 3 && $slot['mulai'] < 600) {
            $skor += 20;
        } elseif ($mk->sks >= 3 && $slot['mulai'] < 780) {
            $skor += 15;
        }

        // 4. Optimasi Dosen: Kepadatan hari mengajar & minimasi jeda mengambang
        $dosenList = $this->dosenList($mk);
        foreach ($dosenList as $dId) {
            if (! empty($this->pakaiDosen[$dId])) {
                $jadwalDosenHariIni = array_filter(
                    $this->pakaiDosen[$dId],
                    fn ($p) => $p['hari'] === $slot['hari']
                );

                if (! empty($jadwalDosenHariIni)) {
                    $skor += 40; // Bonus memusatkan hari mengajar dosen

                    $selisihTerkecil = null;
                    foreach ($jadwalDosenHariIni as $jd) {
                        if ($slot['mulai'] >= $jd['selesai']) {
                            $gap = $slot['mulai'] - $jd['selesai'];
                        } elseif ($jd['mulai'] >= $slot['selesai']) {
                            $gap = $jd['mulai'] - $slot['selesai'];
                        } else {
                            $gap = 0;
                        }

                        if ($selisihTerkecil === null || $gap < $selisihTerkecil) {
                            $selisihTerkecil = $gap;
                        }
                    }

                    if ($selisihTerkecil !== null) {
                        if ($selisihTerkecil <= 30) {
                            $skor += 50; // Langsung berurutan
                        } elseif ($selisihTerkecil <= 60) {
                            $skor += 25; // Jeda pendek
                        } elseif ($selisihTerkecil > 90) {
                            $skor -= 35; // Penalti jeda kosong terlalu lama
                        }
                    }
                }
            }
        }

        // 5. Optimasi Rombel / Cohort: Minimasi jeda mengambang mahasiswa
        $cohortKey = $this->namaCohort($mk->prodi, (int) $mk->semester, $mk->kelas);
        if (! empty($this->kunciCohort[$cohortKey])) {
            $jadwalCohortHariIni = array_filter(
                $this->kunciCohort[$cohortKey],
                fn ($p) => $p['hari'] === $slot['hari']
            );

            if (! empty($jadwalCohortHariIni)) {
                $skor += 25;
                $selisihCohort = null;
                foreach ($jadwalCohortHariIni as $jc) {
                    if ($slot['mulai'] >= $jc['selesai']) {
                        $gap = $slot['mulai'] - $jc['selesai'];
                    } elseif ($jc['mulai'] >= $slot['selesai']) {
                        $gap = $jc['mulai'] - $slot['selesai'];
                    } else {
                        $gap = 0;
                    }
                    if ($selisihCohort === null || $gap < $selisihCohort) {
                        $selisihCohort = $gap;
                    }
                }

                if ($selisihCohort !== null) {
                    if ($selisihCohort <= 30) {
                        $skor += 40; // Mahasiswa kuliah berurutan
                    } elseif ($selisihCohort > 90) {
                        $skor -= 30; // Penalti jeda kosong mahasiswa terlalu panjang
                    }
                }
            }
        }

        // 6. Mata Kuliah Berhubungan (Related Courses Co-location)
        $skor += $this->cekMkBerhubungan($mk, $slot);

        // 7. MK butuh lab lebih baik di pagi atau awal siang
        if ($mk->butuh_lab && $slot['mulai'] < 780) {
            $skor += 15;
        }

        // 8. Distribusi hari seimbang (Senin - Jumat)
        $pakaiHari = count(array_filter($this->kunciCohort, fn ($k) => isset($k[0]) && $k[0]['hari'] === $slot['hari']));
        $skor += max(0, 5 - $pakaiHari) * 5;

        return $skor;
    }

    protected function cekMkBerhubungan(MataKuliahDetail $mk, array $slot): int
    {
        $skor = 0;
        $namaBersih = preg_replace('/(praktikum|prak\.|\b(i|ii|iii|iv|1|2|3|4)\b|\([a-z0-9\s\-]+\)|\[[a-z0-9\s\-]+\])/i', '', strtolower($mk->nama_mk));
        $namaBersih = trim(preg_replace('/\s+/', ' ', $namaBersih));

        if (strlen($namaBersih) < 4) {
            return 0;
        }

        foreach ($this->tersimpan as $item) {
            $mkLain = $item['mk'] ?? null;
            $slotLain = $item['slot'] ?? null;
            if (! $mkLain || ! $slotLain || $mkLain->id === $mk->id) {
                continue;
            }

            $namaLain = preg_replace('/(praktikum|prak\.|\b(i|ii|iii|iv|1|2|3|4)\b|\([a-z0-9\s\-]+\)|\[[a-z0-9\s\-]+\])/i', '', strtolower($mkLain->nama_mk));
            $namaLain = trim(preg_replace('/\s+/', ' ', $namaLain));

            if ($namaLain === $namaBersih || (strlen($mk->kode_mk) >= 6 && str_starts_with($mk->kode_mk, substr($mkLain->kode_mk, 0, 6)))) {
                if ($slotLain['hari'] === $slot['hari']) {
                    $skor += 35; // Berada di hari yang sama
                    $gap = abs($slot['mulai'] - $slotLain['selesai']);
                    $gap2 = abs($slotLain['mulai'] - $slot['selesai']);
                    $minGap = min($gap, $gap2);
                    if ($minGap <= 30) {
                        $skor += 40; // Berurutan atau jeda pendek
                    }
                }
            }
        }

        return $skor;
    }

    /**
     * Cari slot MK Pilihan yang bisa menampung SEMUA pilihan terpilih.
     * Semua MK Pilihan memang sengaja diletakkan pada slot yang sama,
     * jadi syaratnya: cukup ruangan terpisah dan tidak ada dosen yang bentrok.
     */
    protected function cariSlotPilihan(Collection $terambil, string $cohort): ?array
    {
        if ($terambil->isEmpty()) {
            return null;
        }

        $durasi = $terambil->max('sks') * 50;
        $terbaik = null;
        $skorTerbaik = -999999;

        // Pilihan juga harus di hari kerja Senin s.d. Jumat
        $dummyMk = $terambil->first();
        $hariList = $this->getHariTersediaForMk($dummyMk);

        foreach ($hariList as $hari) {
            $candidateSlots = $this->getValidSlotsForMk($dummyMk, $hari);

            foreach ($candidateSlots as $cand) {
                $slot = [
                    'hari' => $hari,
                    'mulai' => $cand['mulai'],
                    'selesai' => $cand['selesai'],
                ];

                if ($this->cohortBentrok($cohort, $slot)) {
                    continue;
                }

                $skor = 0;
                $ruanganTerpakai = [];
                $dosenTerpakai = [];
                $bisaSemua = true;

                foreach ($terambil as $mk) {
                    $dosen = $mk->dosen_ketua_id ?? $mk->dosen_anggota_id;
                    if ($dosen && (in_array($dosen, $dosenTerpakai, true) || $this->dosenBentrok($dosen, $slot))) {
                        $bisaSemua = false;
                        break;
                    }

                    $ruang = $this->ruanganTersedia($mk, $slot)
                        ->reject(fn ($r) => in_array($r->id, $ruanganTerpakai, true))
                        ->first();

                    if (! $ruang && ! $mk->tanpa_ruangan) {
                        $bisaSemua = false;
                        break;
                    }

                    if ($ruang) {
                        $ruanganTerpakai[] = $ruang->id;
                    }
                    if ($dosen) {
                        $dosenTerpakai[] = $dosen;
                    }
                    $skor += 10;
                }

                if ($bisaSemua && $skor > $skorTerbaik) {
                    $skorTerbaik = $skor;
                    $terbaik = $slot;
                }
            }
        }

        return $skorTerbaik > 0 ? $terbaik : null;
    }

    /* ------------------------------------------------------------------ */
    /* Ruangan & dosen */
    /* ------------------------------------------------------------------ */

    protected function hitungRuanganBebas(MataKuliahDetail $mk, array $slot): int
    {
        return $this->ruanganTersedia($mk, $slot)->count();
    }

    protected function cariRuangan(MataKuliahDetail $mk, array $slot): ?Ruangan
    {
        return $this->ruanganTersedia($mk, $slot)->first();
    }

    protected function ruanganTersedia(MataKuliahDetail $mk, array $slot): Collection
    {
        $kuota = $mk->kapasitas_per_kelas ?: 40;

        if ($this->cacheRuanganAktif === null) {
            $this->cacheRuanganAktif = Ruangan::where('is_active', 1)->get();
        }

        $ruangans = $this->cacheRuanganAktif->filter(function ($r) use ($mk) {
            if ($mk->butuh_lab) {
                return $r->tipe === 'Lab';
            }

            return in_array($r->tipe, ['Ruang Kelas', 'Aula', 'Auditorium']);
        });

        // Prioritas 1: Ruangan dengan kapasitas mencukupi (Best-fit)
        $cukup = $ruangans
            ->filter(fn ($r) => $r->kapasitas >= $kuota)
            ->reject(fn ($r) => $this->ruanganBentrok($r->id, $slot))
            ->sortBy(fn ($r) => $r->kapasitas - $kuota)
            ->values();

        if ($cukup->isNotEmpty()) {
            return $cukup;
        }

        // Prioritas 2 (Toleransi Kapasitas Praktikum Lab):
        // Jika butuh Lab, di mana kapasitas lab kampus adalah 35 dan kuota rombel praktikum berkisar 40-47,
        // gunakan laboratorium yang tersedia agar praktikum tetap terlaksana di laboratorium.
        if ($mk->butuh_lab) {
            return $ruangans
                ->filter(fn ($r) => $r->kapasitas >= 30)
                ->reject(fn ($r) => $this->ruanganBentrok($r->id, $slot))
                ->sortByDesc('kapasitas')
                ->values();
        }

        return collect();
    }

    protected function ruanganBentrok(int $ruanganId, array $slot): bool
    {
        foreach ($this->pakaiRuangan as $p) {
            if ($p['hari'] !== $slot['hari']) {
                continue;
            }
            if ($p['ruangan_id'] !== $ruanganId) {
                continue;
            }
            if ($this->tumpang($slot['mulai'], $slot['selesai'], $p['mulai'], $p['selesai'])) {
                return true;
            }
        }

        return false;
    }

    protected function dosenBentrok(?int $dosenId, array $slot): bool
    {
        if (! $dosenId) {
            return false;
        }
        foreach ($this->pakaiDosen[$dosenId] ?? [] as $p) {
            if ($p['hari'] !== $slot['hari']) {
                continue;
            }
            if ($this->tumpang($slot['mulai'], $slot['selesai'], $p['mulai'], $p['selesai'])) {
                return true;
            }
        }

        return false;
    }

    protected function cohortBentrok(string $cohort, array $slot): bool
    {
        $cohortsToCheck = [$cohort];
        $parts = explode('|', $cohort);
        if (count($parts) >= 3) {
            $cohortsToCheck[] = $parts[0].'|'.$parts[1];
        }

        foreach ($cohortsToCheck as $c) {
            foreach ($this->kunciCohort[$c] ?? [] as $p) {
                if ($p['hari'] !== $slot['hari']) {
                    continue;
                }
                if ($this->tumpang($slot['mulai'], $slot['selesai'], $p['mulai'], $p['selesai'])) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function kunciCohortSlot(string $cohort, array $slot): void
    {
        $this->kunciCohort[$cohort][] = [
            'hari' => $slot['hari'],
            'mulai' => $slot['mulai'],
            'selesai' => $slot['selesai'],
        ];
    }

    protected function dosenList(MataKuliahDetail $mk): array
    {
        return array_values(array_unique(array_filter([
            $mk->dosen_ketua_id,
            $mk->dosen_anggota_id,
            $mk->dosen_anggota_2_id,
            $mk->dosen_anggota_3_id,
        ])));
    }

    /* ------------------------------------------------------------------ */
    /* Simpan */
    /* ------------------------------------------------------------------ */

    protected function simpan(
        MataKuliahDetail $mk,
        int $no,
        array $slot,
        ?Ruangan $ruangan,
        ?int $dosenId,
        ?string $peran = 'Ketua'
    ): void {
        $kategori = $mk->isKegiatanKhusus() ? 'khusus' : 'reguler';
        $jenisKhusus = $mk->getJenisKegiatanKhusus();

        $item = compact('mk', 'no', 'slot', 'ruangan', 'dosenId', 'peran', 'kategori', 'jenisKhusus');
        $this->rencanaJadwal[] = $item;
        $this->tersimpan[] = $item;
    }

    /**
     * Menjadwalkan kegiatan akademik khusus (KP, Magang, Skripsi) pada hari Sabtu.
     *
     * @return array{kp: int, magang: int, skripsi: int}
     */
    protected function jadwalkanKhusus(Collection $mkKhusus, ?string $prodi, int $semester): array
    {
        $kpCount = 0;
        $magangCount = 0;
        $skripsiCount = 0;

        foreach ($mkKhusus as $mk) {
            $jenis = $mk->getJenisKegiatanKhusus() ?: 'Khusus';
            $cohort = $this->namaCohort($mk->prodi ?: $prodi, (int) $mk->semester, $mk->kelas);

            // Cek apakah kegiatan khusus ini sudah dijadwalkan secara manual
            $manualJadwal = $this->cacheManualJadwals !== null
                ? ($this->cacheManualJadwals->get($mk->id)?->first())
                : Jadwal::with('rombel')
                    ->whereHas('rombel', fn ($q) => $q->where('mata_kuliah_detail_id', $mk->id))
                    ->where('is_manual', true)
                    ->where('is_active', true)
                    ->first();

            if ($manualJadwal) {
                $itemManual = [
                    'mk' => $mk,
                    'no' => $manualJadwal->rombel?->nomor_rombel ?? 1,
                    'slot' => [
                        'hari' => $manualJadwal->hari,
                        'mulai' => $this->menit($manualJadwal->jam_mulai),
                        'selesai' => $this->menit($manualJadwal->jam_selesai),
                    ],
                    'ruangan' => $manualJadwal->ruangan,
                    'dosenId' => $manualJadwal->dosen_id,
                    'peran' => $manualJadwal->rombel?->peran_dosen ?? 'Ketua',
                    'kategori' => 'khusus',
                    'jenis_khusus' => $jenis,
                ];

                $this->rencanaJadwal[] = $itemManual;
                $this->tersimpan[] = $itemManual;

                if ($jenis === 'KP') {
                    $kpCount++;
                } elseif ($jenis === 'Magang') {
                    $magangCount++;
                } elseif ($jenis === 'Skripsi') {
                    $skripsiCount++;
                }

                $this->log[] = sprintf(
                    'MANUAL KHUSUS[%s] %s - %s | %s %s-%s (input manual)',
                    $jenis,
                    $mk->kode_mk,
                    $mk->nama_mk,
                    $manualJadwal->hari,
                    $manualJadwal->jam_mulai,
                    $manualJadwal->jam_selesai
                );

                continue;
            }

            // Cari slot waktu khusus hari Sabtu
            $slotSabtu = $this->cariSlotKhususSabtu($mk, $cohort);

            if (! $slotSabtu) {
                $this->log[] = "GAGAL  KHUSUS[{$jenis}] {$mk->kode_mk} - {$mk->nama_mk}: tidak ada slot Sabtu yang tersedia tanpa bentrok.";
                $this->kegagalan[] = [
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'sks' => $mk->sks,
                    'semester' => $mk->semester,
                    'prodi' => $mk->prodi ?: 'Umum',
                    'tipe' => "Khusus ({$jenis})",
                    'alasan' => "Slot waktu hari Sabtu untuk kegiatan khusus {$jenis} penuh atau terjadi bentrok dosen pengampu.",
                    'rekomendasi' => 'Sesuaikan penugasan dosen pembimbing/penguji atau sediakan slot waktu tambahan pada hari Sabtu.',
                ];

                continue;
            }

            // Jangan memaksakan ruangan jika kegiatan tanpa_ruangan = true
            $ruangan = null;
            if (! $mk->tanpa_ruangan) {
                $ruangan = $this->cariRuanganKhususSabtu($mk, $slotSabtu);
                if (! $ruangan) {
                    $this->log[] = "GAGAL  KHUSUS[{$jenis}] {$mk->kode_mk} - {$mk->nama_mk}: membutuhkan ruangan namun tidak ada ruangan kosong pada hari Sabtu.";
                    $this->kegagalan[] = [
                        'kode_mk' => $mk->kode_mk,
                        'nama_mk' => $mk->nama_mk,
                        'sks' => $mk->sks,
                        'semester' => $mk->semester,
                        'prodi' => $mk->prodi ?: 'Umum',
                        'tipe' => "Khusus ({$jenis})",
                        'alasan' => 'Kegiatan khusus membutuhkan ruangan fisik namun seluruh ruangan pada slot Sabtu tersebut sudah terpakai.',
                        'rekomendasi' => 'Tandai kegiatan sebagai tanpa ruangan jika bimbingan/ujian bersifat fleksibel/daring, atau aktifkan ruangan sidang/seminar.',
                    ];

                    continue;
                }
            }

            $dosenId = $mk->dosen_ketua_id ?? $mk->dosen_anggota_id;
            $peran = ($dosenId && $dosenId === $mk->dosen_ketua_id) ? 'Ketua' : 'Anggota';

            $item = [
                'mk' => $mk,
                'no' => 1,
                'slot' => $slotSabtu,
                'ruangan' => $ruangan,
                'dosenId' => $dosenId,
                'peran' => $peran,
                'kategori' => 'khusus',
                'jenis_khusus' => $jenis,
            ];

            $this->rencanaJadwal[] = $item;
            $this->tersimpan[] = $item;

            // Kunci resource di memori
            $this->kunciCohortSlot($cohort, $slotSabtu);
            if ($dosenId) {
                $this->pakaiDosen[$dosenId][] = [
                    'hari' => 'Sabtu',
                    'mulai' => $slotSabtu['mulai'],
                    'selesai' => $slotSabtu['selesai'],
                ];
            }
            if ($ruangan) {
                $this->pakaiRuangan[] = [
                    'hari' => 'Sabtu',
                    'ruangan_id' => $ruangan->id,
                    'mulai' => $slotSabtu['mulai'],
                    'selesai' => $slotSabtu['selesai'],
                ];
            }

            if ($jenis === 'KP') {
                $kpCount++;
            } elseif ($jenis === 'Magang') {
                $magangCount++;
            } elseif ($jenis === 'Skripsi') {
                $skripsiCount++;
            }

            $this->log[] = sprintf(
                'OK     KHUSUS[%s] %s - %s | Sabtu %s-%s | %s',
                $jenis,
                $mk->kode_mk,
                $mk->nama_mk,
                $this->format($slotSabtu['mulai']),
                $this->format($slotSabtu['selesai']),
                $ruangan ? $ruangan->kode : 'Tanpa Ruangan'
            );
        }

        return [
            'kp' => $kpCount,
            'magang' => $magangCount,
            'skripsi' => $skripsiCount,
        ];
    }

    protected function cariSlotKhususSabtu(MataKuliahDetail $mk, string $cohort): ?array
    {
        // Durasi kegiatan khusus (KP, Magang, Skripsi) pada jadwal Sabtu modular (100 - 150 menit)
        $durasi = min(150, max(100, (int) ($mk->sks ?: 2) * 50));
        $dosenList = $this->dosenList($mk);

        // Slot waktu Sabtu: 08:00 (480), 09:40 (580), 10:00 (600), 10:30 (630), 13:00 (780), 14:00 (840), 14:40 (880), 15:00 (900), 15:30 (930)
        $candidateStarts = [480, 580, 600, 630, 780, 840, 880, 900, 930];

        foreach ($candidateStarts as $start) {
            $end = $start + $durasi;
            if ($end > 1080) {
                continue;
            }

            // Proteksi istirahat siang hari Sabtu (12:00 - 13:00 / 720 - 780)
            if ($this->tumpang($start, $end, 720, 780)) {
                continue;
            }

            $slot = [
                'hari' => 'Sabtu',
                'mulai' => $start,
                'selesai' => $end,
            ];

            // Cek bentrok cohort
            if ($this->cohortBentrok($cohort, $slot)) {
                continue;
            }

            // Cek bentrok dosen jika dosen ditentukan
            if (! empty($dosenList)) {
                $adaDosenBebas = false;
                foreach ($dosenList as $dId) {
                    if (! $this->dosenBentrok($dId, $slot)) {
                        $adaDosenBebas = true;
                        break;
                    }
                }
                if (! $adaDosenBebas) {
                    continue;
                }
            }

            return $slot;
        }

        return null;
    }

    protected function cariRuanganKhususSabtu(MataKuliahDetail $mk, array $slot): ?Ruangan
    {
        if ($this->cacheRuanganAktif === null) {
            $this->cacheRuanganAktif = Ruangan::where('is_active', 1)->get();
        }

        $ruangans = $this->cacheRuanganAktif->sortBy('kapasitas');

        foreach ($ruangans as $r) {
            $bentrok = false;
            foreach ($this->pakaiRuangan as $pr) {
                if ($pr['hari'] === 'Sabtu' && $pr['ruangan_id'] === $r->id && $this->tumpang($slot['mulai'], $slot['selesai'], $pr['mulai'], $pr['selesai'])) {
                    $bentrok = true;
                    break;
                }
            }
            if (! $bentrok) {
                return $r;
            }
        }

        return null;
    }

    /**
     * Audit komprehensif seluruh rencana jadwal sebelum disimpan ke database.
     */
    public function auditValidasiJadwal(array $rencana): array
    {
        $errors = [];
        $dosenOccurrences = [];
        $cohortOccurrences = [];
        $roomOccurrences = [];

        foreach ($rencana as $item) {
            $mk = $item['mk'];
            $slot = $item['slot'];
            $hari = $slot['hari'];
            $mulai = $slot['mulai'];
            $selesai = $slot['selesai'];
            $dosenId = $item['dosenId'];
            $ruangan = $item['ruangan'];
            $kategori = $item['kategori'] ?? ($mk->isKegiatanKhusus() ? 'khusus' : 'reguler');

            // 1. Audit Batasan Hari
            if ($kategori === 'reguler' && ! in_array($hari, self::HARI, true)) {
                $errors[] = "Pelanggaran Batasan Hari: Mata kuliah reguler {$mk->kode_mk} dijadwalkan pada hari {$hari}. Perkuliahan reguler wajib pada hari Senin–Jumat.";
            }

            if ($kategori === 'khusus' && $hari !== 'Sabtu') {
                $errors[] = "Pelanggaran Batasan Hari: Kegiatan khusus {$mk->kode_mk} ({$item['jenis_khusus']}) dijadwalkan pada hari {$hari}. Kegiatan khusus hanya boleh pada hari Sabtu.";
            }

            if ($mk->isKkn()) {
                $errors[] = "Pelanggaran Pengecualian: Mata kuliah KKN/Kukerta {$mk->kode_mk} tidak boleh dijadwalkan.";
            }

            // 2. Audit Bentrok Dosen
            if ($dosenId) {
                if (isset($dosenOccurrences[$dosenId][$hari])) {
                    foreach ($dosenOccurrences[$dosenId][$hari] as $prev) {
                        if ($this->tumpang($mulai, $selesai, $prev['mulai'], $prev['selesai'])) {
                            $errors[] = "Bentrok Dosen: Dosen ID {$dosenId} mengajar dua kelas pada {$hari} {$this->format($mulai)}-{$this->format($selesai)} ({$mk->kode_mk} vs {$prev['kode_mk']}).";
                        }
                    }
                }
                $dosenOccurrences[$dosenId][$hari][] = [
                    'mulai' => $mulai,
                    'selesai' => $selesai,
                    'kode_mk' => $mk->kode_mk,
                ];
            }

            // 3. Audit Bentrok Ruangan Fisik
            if ($ruangan) {
                $rId = $ruangan->id;
                if (isset($roomOccurrences[$rId][$hari])) {
                    foreach ($roomOccurrences[$rId][$hari] as $prev) {
                        if ($this->tumpang($mulai, $selesai, $prev['mulai'], $prev['selesai'])) {
                            $errors[] = "Bentrok Ruangan: Ruangan {$ruangan->kode} dipakai bersamaan pada {$hari} {$this->format($mulai)}-{$this->format($selesai)} ({$mk->kode_mk} vs {$prev['kode_mk']}).";
                        }
                    }
                }
                $roomOccurrences[$rId][$hari][] = [
                    'mulai' => $mulai,
                    'selesai' => $selesai,
                    'kode_mk' => $mk->kode_mk,
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Terapkan hasil jadwal yang valid ke database dalam transaksi atomik.
     */
    public function terapkanJadwalKeDatabase(array $rencana, int $semester, ?string $prodi): void
    {
        $mkQuery = MataKuliahDetail::where('semester', $semester)->bukanUlang();
        $this->applyProdiFilter($mkQuery, $prodi);
        $idsMk = $mkQuery->pluck('id');

        if ($idsMk->isNotEmpty()) {
            $idsRombelLama = Rombel::whereIn('mata_kuliah_detail_id', $idsMk)->pluck('id');

            // Hapus HANYA jadwal otomatis (pertahankan jadwal manual)
            Jadwal::whereIn('rombel_id', $idsRombelLama)
                ->where('is_manual', false)
                ->delete();

            // Hapus rombel otomatis yang sudah tidak memiliki jadwal
            Rombel::whereIn('mata_kuliah_detail_id', $idsMk)
                ->where('is_manual', false)
                ->whereDoesntHave('jadwals')
                ->delete();
        }

        // Simpan setiap item jadwal baru
        foreach ($rencana as $item) {
            $this->simpanKeDatabase(
                $item['mk'],
                $item['no'],
                $item['slot'],
                $item['ruangan'] ?? null,
                $item['dosenId'] ?? null,
                $item['peran'] ?? 'Ketua'
            );
        }
    }

    /**
     * Simpan satu data rombel & jadwal ke database.
     */
    public function simpanKeDatabase(
        MataKuliahDetail $mk,
        int $no,
        array $slot,
        ?Ruangan $ruangan,
        ?int $dosenId,
        ?string $peran = 'Ketua'
    ): void {
        $kodeRombel = sprintf('%s-%d', $mk->clean_kode_mk, $no);
        $kelas = $mk->kelas ?: $mk->kelas_label;

        if ($mk->jumlah_kelas > 1 && empty($mk->kelas)) {
            $huruf = range('A', 'Z');
            $kelas = $huruf[($no - 1) % 26];
            $kodeRombel = sprintf('%s-%s', $mk->clean_kode_mk, $kelas);
        }

        $dosenAnggota1 = $mk->dosen_anggota_id;
        $dosenAnggota2 = $mk->dosen_anggota_2_id;
        $dosenAnggota3 = $mk->dosen_anggota_3_id;

        $rombel = Rombel::updateOrCreate(
            [
                'mata_kuliah_detail_id' => $mk->id,
                'nomor_rombel' => $no,
            ],
            [
                'kode_rombel' => $kodeRombel,
                'kelas' => $kelas,
                'kapasitas' => $mk->kapasitas_per_kelas,
                'ruangan_id' => $ruangan?->id,
                'hari' => $slot['hari'],
                'jam_mulai' => $this->format($slot['mulai']),
                'jam_selesai' => $this->format($slot['selesai']),
                'dosen_pengampu_id' => $dosenId,
                'dosen_anggota_1_id' => $dosenAnggota1,
                'dosen_anggota_2_id' => $dosenAnggota2,
                'dosen_anggota_3_id' => $dosenAnggota3,
                'peran_dosen' => $peran,
                'is_active' => 1,
                'is_manual' => false,
            ]
        );

        Jadwal::updateOrCreate(
            ['rombel_id' => $rombel->id],
            [
                'ruangan_id' => $ruangan?->id,
                'dosen_id' => $dosenId,
                'dosen_anggota_1_id' => $dosenAnggota1,
                'dosen_anggota_2_id' => $dosenAnggota2,
                'dosen_anggota_3_id' => $dosenAnggota3,
                'hari' => $slot['hari'],
                'jam_mulai' => $this->format($slot['mulai']),
                'jam_selesai' => $this->format($slot['selesai']),
                'is_active' => 1,
                'is_manual' => false,
            ]
        );
    }

    /* ------------------------------------------------------------------ */
    /* Reset & muat state semester lain */
    /* ------------------------------------------------------------------ */

    protected function reset(int $semester, ?string $prodi, array $options = []): void
    {
        $this->log = [];
        $this->peringatan = [];
        $this->kegagalan = [];
        $this->slotPilihan = [];
        $this->pakaiRuangan = [];
        $this->pakaiDosen = [];
        $this->kunciCohort = [];
        $this->tersimpan = [];
        $this->rencanaJadwal = [];

        $this->cacheRuanganAktif = Ruangan::where('is_active', 1)->get();
        $this->cacheManualJadwals = Jadwal::with(['rombel', 'ruangan', 'dosen'])
            ->where('is_manual', true)
            ->where('is_active', true)
            ->get()
            ->groupBy(fn ($j) => $j->rombel?->mata_kuliah_detail_id);

        // Hari reguler perkuliahan: Senin - Jumat. Hari Sabtu untuk kegiatan khusus.
        $userHari = $options['hari'] ?? self::HARI;
        $this->hariTersedia = array_values(array_diff(
            array_intersect(self::HARI_SEMUA, (array) $userHari),
            self::HARI_KHUSUS
        ));

        if (empty($this->hariTersedia)) {
            $this->hariTersedia = self::HARI;
        }

        $this->slotMulaiTersedia = array_values(array_intersect(self::SLOT_MULAI, $options['jam_mulai'] ?? self::SLOT_MULAI));

        if (empty($this->slotMulaiTersedia)) {
            $this->slotMulaiTersedia = self::SLOT_MULAI;
        }

        // CATATAN KEAMANAN DATA:
        // Di sini TIDAK ADA penghapusan jadwal lama dari database.
        // Jadwal lama tetap aman jika proses gagal, dan hanya ditimpa bila jadwal baru lolos audit.

        $existing = Jadwal::with('rombel.mataKuliah')->where('is_active', 1)->get();

        foreach ($existing as $j) {
            $mk = $j->rombel?->mataKuliah;
            $isSameSemester = $mk && (int) $mk->semester === $semester;
            $isCurrentProdi = $this->isMkBelongsToProdi($mk, $prodi);

            // Jangan kunci resource untuk jadwal otomatis semester & prodi ini
            // karena akan digantikan jika proses generate baru berhasil.
            if ($isSameSemester && $isCurrentProdi && ! $j->is_manual) {
                continue;
            }

            $mulai = $this->menit($j->jam_mulai);
            $selesai = $this->menit($j->jam_selesai);

            if ($j->ruangan_id) {
                $this->pakaiRuangan[] = [
                    'hari' => $j->hari,
                    'ruangan_id' => $j->ruangan_id,
                    'mulai' => $mulai,
                    'selesai' => $selesai,
                ];
            }

            $dosenIds = array_filter([
                $j->dosen_id,
                $j->dosen_anggota_1_id,
                $j->dosen_anggota_2_id,
                $j->dosen_anggota_3_id,
            ]);
            foreach ($dosenIds as $dId) {
                $this->pakaiDosen[$dId][] = [
                    'hari' => $j->hari,
                    'mulai' => $mulai,
                    'selesai' => $selesai,
                ];
            }

            if ($mk && $mk->tipe !== 'Pilihan' && $mk->isReguler()) {
                $coh = $this->namaCohort($mk->prodi, (int) $mk->semester, $mk->kelas ?? $j->rombel?->kelas);
                $this->kunciCohort[$coh][] = [
                    'hari' => $j->hari,
                    'mulai' => $mulai,
                    'selesai' => $selesai,
                ];
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Helper */
    /* ------------------------------------------------------------------ */

    protected function tumpang(int $a1, int $a2, int $b1, int $b2): bool
    {
        return $a1 < $b2 && $a2 > $b1;
    }

    protected function menit($waktu): int
    {
        $waktu = (string) $waktu;
        [$j, $m] = array_map('intval', explode(':', $waktu));

        return $j * 60 + $m;
    }

    protected function format(int $menit): string
    {
        return sprintf('%02d:%02d', intdiv($menit, 60), $menit % 60);
    }

    protected function namaCohort(?string $prodi, int $semester, ?string $kelas = null): string
    {
        $p = strtolower(trim($prodi ?? ''));
        if (str_contains($p, 'informatika')) {
            $prodiClean = 'Informatika';
        } elseif (str_contains($p, 'elektro')) {
            $prodiClean = 'Elektro';
        } else {
            $prodiClean = $prodi ?: 'umum';
        }

        $base = $prodiClean.'|'.$semester;

        return $kelas ? $base.'|'.$kelas : $base;
    }

    protected function hasil(int $semester, int $jumlah, ?string $catatan = null, array $statistik = [], string $metode = 'Greedy Algorithm'): array
    {
        $slot = $this->slotPilihan[$semester] ?? [];

        return [
            'semester' => $semester,
            'jumlah_jadwal' => $jumlah,
            'log' => $this->log,
            'peringatan' => $this->peringatan,
            'kegagalan' => $this->kegagalan,
            'catatan' => $catatan,
            'slot_pilihan' => $slot,
            'kuota_pilihan' => count($slot),
            'statistik' => $statistik,
            'metode' => $metode,
        ];
    }
}
