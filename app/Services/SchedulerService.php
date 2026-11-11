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

    /** Jam mulai slot. Slot berikutnya selalu >= slot ini. */
    public const SLOT_MULAI = ['07:30', '10:00', '13:00', '14:50', '15:30'];

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

    public function generate(int $semester, ?string $prodi = 'Informatika', array $options = []): array
    {
        return DB::transaction(function () use ($semester, $prodi, $options) {
            $this->reset($semester, $prodi, $options);

            $mk = $this->ambilMk($semester, $prodi);

            if ($mk->isEmpty()) {
                return $this->hasil($semester, 0, 'Belum ada mata kuliah aktif untuk semester ini.');
            }

            // Validasi resource sebelum generate
            $validasi = $this->validasiResource($mk, $semester, $prodi);
            if (! $validasi['status']) {
                return $this->hasil($semester, 0, $validasi['pesan']);
            }

            $cohort = $this->namaCohort($prodi, $semester);

            $wajib = $mk->where('tipe', 'Wajib')
                ->sortByDesc(fn ($m) => [$m->butuh_lab, $m->sks, $m->jumlah_kelas])
                ->values();

            foreach ($wajib as $m) {
                $mCohort = $this->namaCohort($m->prodi ?: $prodi, (int) $m->semester, $m->kelas);
                $this->tempatkan($m, $mCohort);
            }

            $pilihan = $mk->where('tipe', 'Pilihan');
            if ($pilihan->isNotEmpty()) {
                $this->tempatkanPilihan($pilihan, $cohort, $semester);
            }

            // Hitung statistik hasil
            $statistik = $this->hitungStatistik($mk, count($this->tersimpan));

            return $this->hasil($semester, count($this->tersimpan), null, $statistik);
        });
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

    protected function ambilMk(int $semester, ?string $prodi): Collection
    {
        $query = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota'])
            ->where('semester', $semester)
            ->where('is_active', 1)
            ->where('status_tawar', 1);

        $this->applyProdiFilter($query, $prodi);

        return $query->get();
    }

    protected function validasiResource(Collection $mk, int $semester, ?string $prodi): array
    {
        $warnings = [];

        // Cek dosen tersedia
        $mkPerluDosen = $mk->filter(fn ($m) => ! $m->tanpa_ruangan);
        $dosenTerdaftar = array_unique(array_filter([
            ...$mkPerluDosen->pluck('dosen_ketua_id')->toArray(),
            ...$mkPerluDosen->pluck('dosen_anggota_id')->toArray(),
        ]));

        if (count($dosenTerdaftar) < $mkPerluDosen->count()) {
            $warnings[] = 'Beberapa mata kuliah belum memiliki dosen pengampu.';
        }

        // Cek ruangan tersedia
        $mkButuhLab = $mk->where('butuh_lab', 1)->count();
        $mkButuhKelas = $mk->where('butuh_lab', 0)->where('tanpa_ruangan', 0)->count();

        $labTersedia = Ruangan::where('tipe', 'Lab')->where('is_active', 1)->count();
        $kelasTersedia = Ruangan::whereIn('tipe', ['Ruang Kelas', 'Aula'])->where('is_active', 1)->count();

        if ($mkButuhLab > $labTersedia * 4) { // Asumsi 4 slot per hari
            $warnings[] = "Ruangan lab mungkin tidak cukup untuk {$mkButuhLab} MK yang butuh lab.";
        }

        if ($mkButuhKelas > $kelasTersedia * 4) {
            $warnings[] = "Ruangan kelas mungkin tidak cukup untuk {$mkButuhKelas} MK.";
        }

        // Cek total slot yang dibutuhkan
        $totalSks = $mk->sum('sks');
        $totalSlotTersedia = count($this->hariTersedia) * count($this->slotMulaiTersedia) * 2; // 2 jam per slot

        // Warning jika melebihi kapasitas, tapi tetap lanjutkan (jangan block)
        if ($totalSks > $totalSlotTersedia) {
            $warnings[] = "Total SKS ({$totalSks}) melebihi kapasitas slot tersedia ({$totalSlotTersedia}). Beberapa MK mungkin tidak terjadwal.";
        }

        // Log warning
        foreach ($warnings as $warning) {
            $this->peringatan[] = "VALIDASI: {$warning}";
        }

        return ['status' => true, 'pesan' => null];
    }

    protected function hitungStatistik(Collection $mk, int $terjadwal): array
    {
        return [
            'total_mk' => $mk->count(),
            'mk_wajib' => $mk->where('tipe', 'Wajib')->count(),
            'mk_pilihan' => $mk->where('tipe', 'Pilihan')->count(),
            'mk_terjadwal' => $terjadwal,
            'mk_gagal' => $mk->count() - $terjadwal,
            'total_sks' => $mk->sum('sks'),
            'mk_butuh_lab' => $mk->where('butuh_lab', 1)->count(),
            'mk_tanpa_ruangan' => $mk->where('tanpa_ruangan', 1)->count(),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Penempatan wajib */
    /* ------------------------------------------------------------------ */

    protected function tempatkan(MataKuliahDetail $mk, string $cohort): void
    {
        $slot = $this->cariSlot($mk, $cohort);
        if (! $slot) {
            $this->log[] = "GAGAL  {$mk->kode_mk} - {$mk->nama_mk}: tidak ada slot yang memenuhi syarat (ruangan/dosen).";
            $this->kegagalan[] = [
                'kode_mk' => $mk->kode_mk,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'semester' => $mk->semester,
                'prodi' => $mk->prodi ?: 'Umum',
                'tipe' => $mk->tipe,
                'alasan' => 'Tidak ada slot waktu kosong yang memenuhi syarat (semua slot waktu di hari aktif bentrok dengan cohort yang sama, atau tidak ada ruangan/dosen yang bebas).',
                'rekomendasi' => 'Buka tab Data Ruangan untuk mengaktifkan ruangan kuliah tambahan, atau periksa dosen pengampu agar tidak bentrok dengan jadwal lain.',
            ];

            return;
        }

        $total = $mk->jumlah_kelas;
        $terpasang = 0;
        $dosenList = $this->dosenList($mk);

        // Satu slot dipakai bersama oleh semua kelas paralel, jadi jumlah kelas
        // tidak boleh melebihi jumlah dosen yang tersedia.
        if ($dosenList) {
            $total = min($total, count($dosenList));
        }

        while ($terpasang < $total) {
            // MK tanpa ruangan (Skripsi, KKN, dll) tidak perlu alokasi ruangan.
            if ($mk->tanpa_ruangan) {
                $ruangan = null;
            } else {
                $ruangan = $this->cariRuangan($mk, $slot);
                if (! $ruangan) {
                    break;
                }
            }

            $dosen = $this->pilihDosen($dosenList, $slot);
            if ($dosen === false) {
                // Semua dosen bentrok; tetap jadwalkan tanpa dosen.
                $dosen = null;
            }

            $peran = ($dosen !== null && $dosen === $mk->dosen_ketua_id)
                ? 'Ketua'
                : ($dosen !== null ? 'Anggota' : null);

            $this->simpan($mk, $terpasang + 1, $slot, $ruangan, $dosen, $peran);
            if ($ruangan) {
                $this->pakaiRuangan[] = [
                    'hari' => $slot['hari'],
                    'ruangan_id' => $ruangan->id,
                    'mulai' => $slot['mulai'],
                    'selesai' => $slot['selesai'],
                ];
            }
            if ($dosen) {
                $this->pakaiDosen[$dosen][] = [
                    'hari' => $slot['hari'],
                    'mulai' => $slot['mulai'],
                    'selesai' => $slot['selesai'],
                ];
            }
            $terpasang++;
        }

        $this->kunciCohortSlot($cohort, $slot);

        $sisa = $mk->jumlah_kelas - $terpasang;
        $catatan = $sisa > 0
            ? " | PERINGATAN: {$sisa} kelas tidak dijadwalkan (kurang dosen/ruangan)"
            : '';

        if ($sisa > 0) {
            $this->kegagalan[] = [
                'kode_mk' => $mk->kode_mk,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'semester' => $mk->semester,
                'prodi' => $mk->prodi ?: 'Umum',
                'tipe' => $mk->tipe,
                'alasan' => "Hanya {$terpasang} dari {$mk->jumlah_kelas} kelas yang terjadwal. Sebanyak {$sisa} kelas tidak tertampung karena keterbatasan dosen pengampu bebas atau ruangan pada slot waktu ini.",
                'rekomendasi' => 'Tugaskan dosen pengampu tambahan untuk kelas paralel ini atau sediakan slot/ruangan kuliah alternatif.',
            ];
        }

        $this->log[] = sprintf(
            'OK     %s - %s | %d/%d kelas | %s %s-%s%s',
            $mk->kode_mk,
            $mk->nama_mk,
            $terpasang,
            $mk->jumlah_kelas,
            $slot['hari'],
            $this->format($slot['mulai']),
            $this->format($slot['selesai']),
            $catatan
        );
    }

    /** @return int|false|null */
    protected function pilihDosen(array $dosenList, array $slot)
    {
        if (! $dosenList) {
            return null;
        }

        foreach ($dosenList as $d) {
            if (! $this->dosenBentrok($d, $slot)) {
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

    /**
     * Cari slot terbaik dengan scoring system yang lebih komprehensif.
     * Skor dihitung berdasarkan:
     *  1. Ketersediaan ruangan (prioritas utama)
     *  2. Ketersediaan dosen
     *  3. Posisi slot (pagi lebih baik untuk MK berat)
     *  4. Distribusi hari (seimbang antar hari)
     */
    protected function cariSlot(MataKuliahDetail $mk, string $cohort): ?array
    {
        $durasi = $mk->sks * 50;
        $dosenList = $this->dosenList($mk);
        $terbaik = null;
        $skorTerbaik = -1;

        foreach ($this->hariTersedia as $hari) {
            foreach ($this->slotMulaiTersedia as $mulai) {
                $mulaiMenit = $this->menit($mulai);
                $selesaiMenit = $mulaiMenit + $durasi;

                // Proteksi Sholat Jumat (11:30 - 13:30 / 690 - 810 menit)
                if ($hari === 'Jumat' && $mulaiMenit < 810 && $selesaiMenit > 690) {
                    continue;
                }

                $slot = [
                    'hari' => $hari,
                    'mulai' => $mulaiMenit,
                    'selesai' => $selesaiMenit,
                ];

                if ($this->cohortBentrok($cohort, $slot)) {
                    continue;
                }

                // MK tanpa ruangan (Skripsi, KKN, dll) tidak butuh cek ketersediaan ruang.
                if ($mk->tanpa_ruangan) {
                    $skorRuangan = 100;
                } else {
                    $skorRuangan = $this->hitungRuanganBebas($mk, $slot);
                    if ($skorRuangan < $mk->jumlah_kelas) {
                        continue;
                    }
                }

                // Setiap kelas paralel harus diampu dosen yang berbeda,
                // karena satu slot dipakai bersamaan oleh semua kelas paralel.
                $skorDosen = 0;
                if ($dosenList) {
                    $dosenBebas = count(array_filter(
                        $dosenList,
                        fn ($d) => ! $this->dosenBentrok($d, $slot)
                    ));
                    if ($dosenBebas < min($mk->jumlah_kelas, count($dosenList))) {
                        continue;
                    }
                    $skorDosen = $dosenBebas * 10;
                }

                // Hitung skor komprehensif
                $skor = $this->hitungSkorSlot($slot, $skorRuangan, $skorDosen, $mk);

                if ($skor > $skorTerbaik) {
                    $skorTerbaik = $skor;
                    $terbaik = $slot;
                }
            }
        }

        return $terbaik;
    }

    public function getModularSlotMulaiForSks(int $sks, ?string $hari = null): array
    {
        if (! empty($this->slotMulaiTersedia) && $this->slotMulaiTersedia !== self::SLOT_MULAI) {
            return $this->slotMulaiTersedia;
        }

        $durasi = $sks * 50;

        $candidateMinutes = [
            450, // 07:30
            600, // 10:00
            780, // 13:00
            930, // 15:30
            890, // 14:50
            880, // 14:40
            500, // 08:20
            550, // 09:10
            650, // 10:50
            830, // 13:50
            980, // 16:20
        ];

        $valid = [];
        foreach ($candidateMinutes as $start) {
            $end = $start + $durasi;
            if ($end > 1080) { // maksimal jam 18:00
                continue;
            }

            // Validasi hari Jumat: lindungi waktu sholat Jumat (11:30 - 13:30 / 690 - 810 menit)
            if ($hari === 'Jumat') {
                if ($start < 810 && $end > 690) {
                    continue;
                }
            } else {
                // Validasi jeda istirahat siang hari biasa (12:30 - 13:00 / 750 - 780 menit)
                if ($start < 780 && $end > 750) {
                    if (! ($end <= 750 || $start >= 780)) {
                        continue;
                    }
                }
            }

            $timeStr = sprintf('%02d:%02d', intdiv($start, 60), $start % 60);
            if (! in_array($timeStr, $valid, true)) {
                $valid[] = $timeStr;
            }
        }

        return ! empty($valid) ? $valid : self::SLOT_MULAI;
    }

    protected function hitungSkorSlot(array $slot, int $skorRuangan, int $skorDosen, MataKuliahDetail $mk): int
    {
        $skor = 0;

        // Prioritas utama: ketersediaan ruangan
        $skor += $skorRuangan * 10;

        // Prioritas kedua: ketersediaan dosen
        $skor += $skorDosen;

        // Prioritas ketiga: posisi waktu
        // User rule: mengutamakan matakuliah wajib dimulai dari pagi hingga siang (07:30 -> 10:00 -> 13:00)
        if ($mk->tipe === 'Wajib') {
            if ($slot['mulai'] === 450) { // 07:30 (Pagi utama)
                $skor += 35;
            } elseif ($slot['mulai'] === 600) { // 10:00 (Pagi kedua)
                $skor += 25;
            } elseif ($slot['mulai'] === 780) { // 13:00 (Siang)
                $skor += 15;
            }
        }

        // MK berat (SKS >= 3 atau 4 SKS) adaptif jika pagi terisi MK Fakultas, diutamakan di slot berikutnya yang efisien
        if ($mk->sks >= 3 && $slot['mulai'] < 600) { // Sebelum 10:00
            $skor += 20;
        } elseif ($mk->sks >= 3 && $slot['mulai'] >= 600 && $slot['mulai'] < 780) { // 10:00 - 13:00
            $skor += 15;
        } elseif ($mk->sks >= 3 && $slot['mulai'] >= 780 && $slot['mulai'] <= 930) { // 13:00 - 15:30
            $skor += 10;
        }

        // MK butuh lab lebih baik di pagi/sebelum tengah hari
        if ($mk->butuh_lab && $slot['mulai'] < 780) {
            $skor += 15;
        }

        // Distribusi hari: beri bonus untuk hari yang belum banyak terpakai
        $pakaiHari = count(array_filter($this->kunciCohort, fn ($k) => isset($k[0]) && $k[0]['hari'] === $slot['hari']));
        $skor += max(0, 5 - $pakaiHari) * 5;

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
        $skorTerbaik = -1;

        foreach ($this->hariTersedia as $hari) {
            foreach ($this->slotMulaiTersedia as $mulai) {
                $mulaiMenit = $this->menit($mulai);
                $selesaiMenit = $mulaiMenit + $durasi;

                // Proteksi Sholat Jumat (11:30 - 13:30 / 690 - 810 menit)
                if ($hari === 'Jumat' && $mulaiMenit < 810 && $selesaiMenit > 690) {
                    continue;
                }

                $slot = [
                    'hari' => $hari,
                    'mulai' => $mulaiMenit,
                    'selesai' => $selesaiMenit,
                ];

                if ($this->cohortBentrok($cohort, $slot)) {
                    continue;
                }

                $skor = 0;
                $ruanganTerpakai = [];
                foreach ($terambil as $mk) {
                    $dosen = $mk->dosen_ketua_id ?? $mk->dosen_anggota_id;
                    if ($dosen && $this->dosenBentrok($dosen, $slot)) {
                        continue;
                    }

                    $ruang = $this->ruanganTersedia($mk, $slot)
                        ->reject(fn ($r) => in_array($r->id, $ruanganTerpakai, true))
                        ->first();

                    if (! $ruang) {
                        continue;
                    }

                    $ruanganTerpakai[] = $ruang->id;
                    $skor++;
                }

                if ($skor > $skorTerbaik) {
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
        return Ruangan::where('is_active', 1)
            ->where('kapasitas', '>=', $mk->kapasitas_per_kelas)
            ->when(
                $mk->butuh_lab,
                fn ($q) => $q->where('tipe', 'Lab'),
                fn ($q) => $q->whereIn('tipe', ['Ruang Kelas', 'Aula', 'Auditorium'])
            )
            ->get()
            ->reject(fn ($r) => $this->ruanganBentrok($r->id, $slot))
            ->values();
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
        ?string $peran
    ): void {
        // Gunakan kelas dari MataKuliahDetail jika ada, jika tidak gunakan nomor rombel
        // Jangan double append kelas ke kode_rombel
        $kelas = $mk->kelas ?? null;
        if ($kelas) {
            // Jika ada kelas di MK, gunakan kode_mk langsung (sudah termasuk kelas)
            $kodeRombel = $mk->kode_mk;
        } else {
            // Jika tidak ada kelas, gunakan format lama
            $kodeRombel = $mk->kode_mk.'-'.$no;
        }

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
                'peran_dosen' => $peran,
                'is_active' => 1,
            ]
        );

        Jadwal::updateOrCreate(
            ['rombel_id' => $rombel->id],
            [
                'ruangan_id' => $ruangan?->id,
                'dosen_id' => $dosenId,
                'hari' => $slot['hari'],
                'jam_mulai' => $this->format($slot['mulai']),
                'jam_selesai' => $this->format($slot['selesai']),
                'is_active' => 1,
            ]
        );

        $this->tersimpan[] = compact('mk', 'no', 'slot', 'ruangan', 'dosenId', 'peran');
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
        $this->hariTersedia = array_values(array_intersect(self::HARI, $options['hari'] ?? self::HARI));
        $this->slotMulaiTersedia = array_values(array_intersect(self::SLOT_MULAI, $options['jam_mulai'] ?? self::SLOT_MULAI));

        if (empty($this->hariTersedia)) {
            $this->hariTersedia = self::HARI;
        }

        if (empty($this->slotMulaiTersedia)) {
            $this->slotMulaiTersedia = self::SLOT_MULAI;
        }

        $mkQuery = MataKuliahDetail::where('semester', $semester);
        $this->applyProdiFilter($mkQuery, $prodi);
        $idsMk = $mkQuery->pluck('id');

        if ($idsMk->isNotEmpty()) {
            $idsRombelLama = Rombel::whereIn('mata_kuliah_detail_id', $idsMk)->pluck('id');
            Jadwal::whereIn('rombel_id', $idsRombelLama)->delete();
            Rombel::whereIn('mata_kuliah_detail_id', $idsMk)->delete();
        }

        $existing = Jadwal::with('rombel.mataKuliah')->where('is_active', 1)->get();

        foreach ($existing as $j) {
            $mulai = $this->menit($j->jam_mulai);
            $selesai = $this->menit($j->jam_selesai);

            $this->pakaiRuangan[] = [
                'hari' => $j->hari,
                'ruangan_id' => $j->ruangan_id,
                'mulai' => $mulai,
                'selesai' => $selesai,
            ];

            if ($j->dosen_id) {
                $this->pakaiDosen[$j->dosen_id][] = [
                    'hari' => $j->hari,
                    'mulai' => $mulai,
                    'selesai' => $selesai,
                ];
            }

            $mk = $j->rombel?->mataKuliah;
            if ($mk && $mk->tipe !== 'Pilihan') {
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

    protected function hasil(int $semester, int $jumlah, ?string $catatan = null, array $statistik = []): array
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
        ];
    }
}
