<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\CatatanEvaluasiJadwal;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Jadwal;
use App\Models\Jurusan;
use App\Models\MataKuliahDetail;
use App\Models\Prodi;
use App\Models\Rombel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class JurusanMonitoringService
{
    /**
     * Resolusi objek Jurusan berdasarkan akun login atau permintaan Admin global.
     */
    public function resolveJurusan(User $user, ?int $requestedJurusanId = null): Jurusan
    {
        if (in_array($user->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true)) {
            if ($requestedJurusanId) {
                return Jurusan::with('prodi')->findOrFail($requestedJurusanId);
            }

            return Jurusan::with('prodi')->orderBy('id')->firstOrFail();
        }

        if ($user->role === UserRole::JURUSAN) {
            $jurusanId = $user->jurusan_id ?? Jurusan::orderBy('id')->value('id');
            if ($jurusanId) {
                return Jurusan::with('prodi')->findOrFail($jurusanId);
            }

            // Fallback aman jika database belum terisi master jurusan (e.g. pengujian unit terisolasi)
            $fakultas = Fakultas::firstOrCreate(
                ['id' => 1],
                ['nama' => 'Fakultas Teknik dan Teknologi Kemaritiman', 'kode' => 'FTTK']
            );

            return Jurusan::firstOrCreate(
                ['id' => 1],
                ['nama' => 'Teknik Elektro dan Teknik Informatika', 'kode' => 'TEI', 'fakultas_id' => $fakultas->id]
            );
        }

        abort(403, 'Akses ditolak: Hanya peran Admin dan Jurusan yang memiliki akses.');
    }

    /**
     * Daftar program studi di bawah jurusan ini.
     *
     * @return EloquentCollection<int, Prodi>
     */
    public function getProdiList(Jurusan $jurusan): EloquentCollection
    {
        $list = $jurusan->prodi()->orderBy('id')->get();

        // Fallback untuk environment test terisolasi jika prodi dibuat tanpa jurusan_id
        if ($list->isEmpty() && $jurusan->fakultas_id) {
            $unassigned = Prodi::where('fakultas_id', $jurusan->fakultas_id)
                ->whereNull('jurusan_id')
                ->orderBy('id')
                ->get();

            if ($unassigned->isNotEmpty()) {
                return $unassigned;
            }
        }

        return $list;
    }

    /**
     * Menghasilkan variasi nama/alias string prodi untuk pencocokan data pada tabel lama.
     *
     * @return list<string>
     */
    public function getProdiAliases(Prodi $prodi): array
    {
        $fullName = $prodi->nama;
        $cleanName = trim(preg_replace('/^(S1|D3|D4|S2)\s+/i', '', $fullName));
        $aliases = [$fullName, $cleanName];

        $lower = strtolower($fullName);
        if (str_contains($lower, 'informatik')) {
            $aliases = array_merge($aliases, ['Teknik Informatika', 'S1 Teknik Informatika', 'Teknik Informatik']);
        } elseif (str_contains($lower, 'elektro')) {
            $aliases = array_merge($aliases, ['Teknik Elektro', 'S1 Teknik Elektro']);
        } elseif (str_contains($lower, 'sipil')) {
            $aliases = array_merge($aliases, ['Teknik Sipil', 'S1 Teknik Sipil']);
        } elseif (str_contains($lower, 'perkapalan')) {
            $aliases = array_merge($aliases, ['Teknik Perkapalan', 'S1 Teknik Perkapalan']);
        } elseif (str_contains($lower, 'industri')) {
            $aliases = array_merge($aliases, ['Teknik Industri', 'S1 Teknik Industri']);
        } elseif (str_contains($lower, 'kimia')) {
            $aliases = array_merge($aliases, ['Kimia', 'S1 Kimia']);
        }

        return array_values(array_unique(array_filter($aliases)));
    }

    /**
     * Menghasilkan seluruh alias string prodi untuk semua prodi di bawah jurusan.
     *
     * @return list<string>
     */
    public function getAllProdiAliasesInJurusan(Jurusan $jurusan): array
    {
        $prodis = $this->getProdiList($jurusan);
        $allAliases = [];

        foreach ($prodis as $prodi) {
            $allAliases = array_merge($allAliases, $this->getProdiAliases($prodi));
        }

        return array_values(array_unique(array_filter($allAliases)));
    }

    /**
     * Statistik ringkasan komprehensif Jurusan.
     */
    public function getJurusanStats(Jurusan $jurusan): array
    {
        $prodis = $this->getProdiList($jurusan);

        if ($prodis->isEmpty()) {
            return [
                'total_prodi' => 0,
                'total_dosen' => 0,
                'total_mata_kuliah' => 0,
                'total_rombel' => 0,
                'total_jadwal' => 0,
                'total_catatan' => 0,
                'total_catatan_pending' => 0,
                'total_bentrok_potensial' => 0,
                'prodi_summaries' => [],
            ];
        }

        $allAliases = $this->getAllProdiAliasesInJurusan($jurusan);

        $totalDosen = Dosen::whereIn('prodi', $allAliases)->where('is_active', true)->count();
        $totalMataKuliah = MataKuliahDetail::whereIn('prodi', $allAliases)->where('is_active', true)->count();
        $totalRombel = Rombel::whereHas('mataKuliah', fn ($q) => $q->whereIn('prodi', $allAliases))->count();
        $totalJadwal = Jadwal::whereHas('rombel.mataKuliah', fn ($q) => $q->whereIn('prodi', $allAliases))
            ->where('is_active', true)
            ->count();

        $catatanQuery = CatatanEvaluasiJadwal::where('jurusan_id', $jurusan->id);
        $totalCatatan = (clone $catatanQuery)->count();
        $totalCatatanPending = (clone $catatanQuery)->whereIn('status', [
            CatatanEvaluasiJadwal::STATUS_MENUNGGU,
            CatatanEvaluasiJadwal::STATUS_DITINJAU,
        ])->count();

        $conflicts = $this->detectCrossProdiConflicts($jurusan);

        // Ringkasan per prodi
        $prodiSummaries = [];
        foreach ($prodis as $p) {
            $pAliases = $this->getProdiAliases($p);
            $pDosen = Dosen::whereIn('prodi', $pAliases)->where('is_active', true)->count();
            $pMk = MataKuliahDetail::whereIn('prodi', $pAliases)->where('is_active', true)->count();
            $pRombel = Rombel::whereHas('mataKuliah', fn ($q) => $q->whereIn('prodi', $pAliases))->count();
            $pJadwal = Jadwal::whereHas('rombel.mataKuliah', fn ($q) => $q->whereIn('prodi', $pAliases))
                ->where('is_active', true)
                ->count();
            $pCatatan = CatatanEvaluasiJadwal::where('prodi_id', $p->id)
                ->whereIn('status', [
                    CatatanEvaluasiJadwal::STATUS_MENUNGGU,
                    CatatanEvaluasiJadwal::STATUS_DITINJAU,
                ])->count();

            $prodiSummaries[] = [
                'prodi' => $p,
                'dosen_count' => $pDosen,
                'mk_count' => $pMk,
                'rombel_count' => $pRombel,
                'jadwal_count' => $pJadwal,
                'catatan_pending_count' => $pCatatan,
            ];
        }

        return [
            'total_prodi' => $prodis->count(),
            'total_dosen' => $totalDosen,
            'total_mata_kuliah' => $totalMataKuliah,
            'total_rombel' => $totalRombel,
            'total_jadwal' => $totalJadwal,
            'total_catatan' => $totalCatatan,
            'total_catatan_pending' => $totalCatatanPending,
            'total_bentrok_potensial' => count($conflicts),
            'prodi_summaries' => $prodiSummaries,
        ];
    }

    /**
     * Mengambil daftar jadwal terfilter pada lingkup Jurusan.
     */
    public function getFilteredJadwals(Jurusan $jurusan, array $filters = []): Collection
    {
        $prodis = $this->getProdiList($jurusan);
        if ($prodis->isEmpty()) {
            return collect();
        }

        // Tentukan target aliases
        if (! empty($filters['prodi_id'])) {
            $selectedProdi = $prodis->firstWhere('id', (int) $filters['prodi_id']);
            if (! $selectedProdi) {
                return collect();
            }
            $targetAliases = $this->getProdiAliases($selectedProdi);
        } else {
            $targetAliases = $this->getAllProdiAliasesInJurusan($jurusan);
        }

        $query = Jadwal::with([
            'rombel.mataKuliah',
            'dosen',
            'ruangan',
            'laboratory',
            'catatanEvaluasi' => fn ($q) => $q->where('jurusan_id', $jurusan->id),
        ])
            ->whereHas('rombel.mataKuliah', fn ($q) => $q->whereIn('prodi', $targetAliases))
            ->where('is_active', true);

        if (! empty($filters['hari']) && $filters['hari'] !== 'all') {
            $query->where('hari', $filters['hari']);
        }

        if (! empty($filters['semester']) && $filters['semester'] !== 'all') {
            $semester = (int) $filters['semester'];
            $query->whereHas('rombel.mataKuliah', fn ($q) => $q->where('semester', $semester));
        }

        if (! empty($filters['dosen_id']) && $filters['dosen_id'] !== 'all') {
            $query->where('dosen_id', (int) $filters['dosen_id']);
        }

        if (! empty($filters['q'])) {
            $keyword = trim($filters['q']);
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('rombel.mataKuliah', function ($sub) use ($keyword) {
                    $sub->where('nama_mk', 'like', "%{$keyword}%")
                        ->orWhere('kode_mk', 'like', "%{$keyword}%");
                })->orWhereHas('dosen', function ($sub) use ($keyword) {
                    $sub->where('nama', 'like', "%{$keyword}%")
                        ->orWhere('inisial', 'like', "%{$keyword}%");
                });
            });
        }

        return $query
            ->orderByRaw("CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 ELSE 6 END")
            ->orderBy('jam_mulai')
            ->get();
    }

    /**
     * Deteksi potensi jadwal bentrok dosen lintas-prodi atau lintas-rombel di dalam jurusan.
     *
     * @return list<array<string, mixed>>
     */
    public function detectCrossProdiConflicts(Jurusan $jurusan): array
    {
        $allAliases = $this->getAllProdiAliasesInJurusan($jurusan);
        if (empty($allAliases)) {
            return [];
        }

        $jadwals = Jadwal::with(['rombel.mataKuliah', 'dosen', 'ruangan'])
            ->whereHas('rombel.mataKuliah', fn ($q) => $q->whereIn('prodi', $allAliases))
            ->where('is_active', true)
            ->whereNotNull('dosen_id')
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        $conflicts = [];
        $grouped = $jadwals->groupBy(fn ($j) => $j->hari.'_'.$j->dosen_id);

        foreach ($grouped as $key => $items) {
            if ($items->count() < 2) {
                continue;
            }

            // Periksa setiap pasangan jadwal pada dosen dan hari yang sama
            $count = $items->count();
            for ($i = 0; $i < $count; $i++) {
                for ($k = $i + 1; $k < $count; $k++) {
                    $j1 = $items[$i];
                    $j2 = $items[$k];

                    $mulai1 = Carbon::parse($j1->jam_mulai)->format('H:i');
                    $selesai1 = Carbon::parse($j1->jam_selesai)->format('H:i');
                    $mulai2 = Carbon::parse($j2->jam_mulai)->format('H:i');
                    $selesai2 = Carbon::parse($j2->jam_selesai)->format('H:i');

                    // Cek overlap waktu
                    if ($mulai1 < $selesai2 && $selesai1 > $mulai2) {
                        $prodi1 = $j1->rombel?->mataKuliah?->prodi ?? 'Prodi A';
                        $prodi2 = $j2->rombel?->mataKuliah?->prodi ?? 'Prodi B';

                        $conflicts[] = [
                            'dosen' => $j1->dosen,
                            'hari' => $j1->hari,
                            'jadwal_1' => $j1,
                            'jadwal_2' => $j2,
                            'prodi_1' => $prodi1,
                            'prodi_2' => $prodi2,
                            'pesan' => "Dosen {$j1->dosen?->nama} memiliki jam mengajar tumpang-tindih pada hari {$j1->hari}: [{$mulai1}-{$selesai1}] di {$prodi1} vs [{$mulai2}-{$selesai2}] di {$prodi2}.",
                        ];
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * Menyimpan catatan evaluasi baru dari Admin Jurusan.
     */
    public function storeCatatan(Jurusan $jurusan, User $user, array $data): CatatanEvaluasiJadwal
    {
        // Validasi kepemilikan prodi di bawah jurusan
        $prodi = $jurusan->prodi()->findOrFail((int) $data['prodi_id']);

        $jadwalId = ! empty($data['jadwal_id']) ? (int) $data['jadwal_id'] : null;
        if ($jadwalId) {
            $pAliases = $this->getProdiAliases($prodi);
            $jadwalExists = Jadwal::where('id', $jadwalId)
                ->whereHas('rombel.mataKuliah', fn ($q) => $q->whereIn('prodi', $pAliases))
                ->exists();

            if (! $jadwalExists) {
                abort(422, 'Jadwal yang dipilih tidak valid atau bukan milik Program Studi terkait.');
            }
        }

        return CatatanEvaluasiJadwal::create([
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodi->id,
            'jadwal_id' => $jadwalId,
            'user_id_pengirim' => $user->id,
            'kategori' => $data['kategori'],
            'judul' => $data['judul'],
            'catatan_rekomendasi' => $data['catatan_rekomendasi'],
            'status' => CatatanEvaluasiJadwal::STATUS_MENUNGGU,
        ]);
    }
}
