<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\MataKuliahDetail;
use App\Models\Prodi;
use App\Models\Rombel;
use App\Models\Ruangan;
use App\Services\GeneticSchedulerService;
use App\Services\MataKuliahImportService;
use App\Services\SchedulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PenjadwalanController extends Controller
{
    public const SEMESTER_GANJIL = [1, 3, 5, 7];

    public const SEMESTER_GENAP = [2, 4, 6, 8];

    public const SEMESTER_ALL = [1, 2, 3, 4, 5, 6, 7, 8];

    /**
     * Check if the logged-in prodi user owns or has access to the specified prodi.
     */
    protected function userOwnsProdi($user, ?string $prodi): bool
    {
        if (! $user || $user->role !== 'Prodi' || ! $user->prodi?->nama) {
            return true;
        }

        $userProdiFull = strtolower($user->prodi->nama);
        $userProdiClean = strtolower(trim(preg_replace('/^(S1|D3|D4|S2)\s+/i', '', $user->prodi->nama)));
        $targetProdi = strtolower($prodi ?? '');

        return str_contains($targetProdi, $userProdiClean) || str_contains($targetProdi, $userProdiFull);
    }

    /**
     * Get clean standardized prodi name for current user.
     */
    protected function getUserProdiName($user): ?string
    {
        if (! $user?->prodi?->nama) {
            return null;
        }

        return trim(preg_replace('/^(S1|D3|D4|S2)\s+/i', '', $user->prodi->nama));
    }

    public function index()
    {
        $user = auth()->user();
        $userRole = $user?->role;
        $userProdi = $this->getUserProdiName($user);

        $query = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota', 'masterMk'])
            ->where('is_active', 1)
            ->where('status_tawar', 1)
            ->orderBy('semester')
            ->orderBy('kode_mk');

        if ($userRole === 'Prodi' && $userProdi) {
            $query->where(function ($q) use ($userProdi) {
                $q->where('prodi', 'like', "%{$userProdi}%")
                    ->orWhereIn('kategori', ['MK Universitas', 'MK Fakultas']);
            });
        }

        $mataKuliah = $query->get()->groupBy('semester');

        $jadwalQuery = DB::table('jadwals')
            ->join('rombels', 'rombels.id', '=', 'jadwals.rombel_id')
            ->join('mata_kuliah_details', 'mata_kuliah_details.id', '=', 'rombels.mata_kuliah_detail_id')
            ->leftJoin('ruangans', 'ruangans.id', '=', 'jadwals.ruangan_id')
            ->leftJoin('dosens', 'dosens.id', '=', 'jadwals.dosen_id')
            ->where('jadwals.is_active', 1)
            ->where('rombels.is_active', 1);

        if ($userRole === 'Prodi' && $userProdi) {
            $jadwalQuery->where(function ($q) use ($userProdi) {
                $q->where('mata_kuliah_details.prodi', 'like', "%{$userProdi}%")
                    ->orWhereIn('mata_kuliah_details.kategori', ['MK Universitas', 'MK Fakultas']);
            });
        }

        $jadwal = $jadwalQuery
            ->select([
                'mata_kuliah_details.semester',
                'mata_kuliah_details.kode_mk',
                'mata_kuliah_details.nama_mk',
                'mata_kuliah_details.sks',
                'mata_kuliah_details.tipe',
                'mata_kuliah_details.prodi',
                'mata_kuliah_details.kelas as mk_kelas',
                'rombels.kode_rombel',
                'rombels.kelas as rombel_kelas',
                'rombels.nomor_rombel',
                'jadwals.hari',
                'jadwals.jam_mulai',
                'jadwals.jam_selesai',
                'ruangans.kode as kode_ruangan',
                'ruangans.nama as nama_ruangan',
                'dosens.nama as nama_dosen',
                'dosens.inisial as inisial_dosen',
            ])
            ->orderBy('mata_kuliah_details.semester')
            ->orderBy('jadwals.hari')
            ->orderBy('jadwals.jam_mulai')
            ->orderBy('rombels.nomor_rombel')
            ->get()
            ->groupBy('semester');

        $logGenerate = session('log_generate');
        if (empty($logGenerate)) {
            $logGenerate = Cache::get('penjadwalan_last_log', []);
            if (! empty($logGenerate)) {
                session(['log_generate' => $logGenerate]);
            }
        }

        $totalJadwalCount = 0;
        $totalSksCount = 0;
        foreach ($jadwal as $semJadwals) {
            $totalJadwalCount += $semJadwals->count();
            $totalSksCount += $semJadwals->sum('sks');
        }

        $totalMkCount = 0;
        foreach ($mataKuliah as $semMks) {
            $totalMkCount += $semMks->count();
        }

        $ruanganAktif = Ruangan::where('is_active', 1)->orderBy('kode')->get();

        $dosensQuery = Dosen::query();
        if ($userRole === 'Prodi' && $userProdi) {
            $dosensQuery->where(function ($q) use ($userProdi, $user) {
                $q->where('prodi', 'like', "%{$userProdi}%");
                if ($user?->prodi?->nama) {
                    $q->orWhere('prodi', 'like', "%{$user->prodi->nama}%");
                }
            });
        }
        $dosens = $dosensQuery->orderBy('prodi')->orderBy('nama')->get();

        return view('penjadwalan.index', [
            'mataKuliah' => $mataKuliah,
            'jadwal' => $jadwal,
            'dosens' => $dosens,
            'ruangans' => Ruangan::orderBy('kode')->get(),
            'ruangan' => Ruangan::orderBy('kode')->get(),
            'ruanganAktif' => $ruanganAktif,
            'totalJadwalCount' => $totalJadwalCount,
            'totalSksCount' => $totalSksCount,
            'totalMkCount' => $totalMkCount,
            'totalRuanganCount' => $ruanganAktif->count(),
            'daftarProdi' => Prodi::where('status', 'ACTIVE')->orderBy('nama')->get(),
            'semesterGanjil' => self::SEMESTER_GANJIL,
            'semesterGenap' => self::SEMESTER_GENAP,
            'semesterAll' => self::SEMESTER_ALL,
            'maksPilihan' => SchedulerService::MAKS_PILIHAN,
            'logGenerate' => $logGenerate,
            'userRole' => $userRole,
            'userProdi' => $userProdi,
            'prodiAktif' => session('prodi_aktif', 'all'),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $this->validasi($request);

        // Jika user adalah Prodi, kunci prodi ke prodi milik user
        if ($user?->role === 'Prodi' && $user->prodi?->nama) {
            $data['prodi'] = $this->getUserProdiName($user);
        }

        // Sinkronisasi ke master mata_kuliah jika ada
        $cleanKode = preg_replace('/-[A-Za-z0-9]+$/', '', $data['kode_mk']);
        $master = MataKuliah::firstOrCreate(
            ['kode' => $cleanKode],
            [
                'nama' => $data['nama_mk'],
                'sks' => $data['sks'],
                'semester' => $data['semester'],
                'tipe' => 'Prodi',
                'kategori' => 'MK Prodi Wajib',
                'pemilik' => $data['prodi'],
                'level_id' => 1,
            ]
        );
        $data['master_mk_id'] = $master->id;

        MataKuliahDetail::create($data);

        return $this->kembali('Mata kuliah berhasil ditambahkan.');
    }

    public function update(Request $request, MataKuliahDetail $mataKuliah)
    {
        $user = auth()->user();

        if ($user?->role === 'Prodi' && $user->prodi?->nama) {
            if (! $this->userOwnsProdi($user, $mataKuliah->prodi)) {
                return back()->with('flash_error', 'Anda tidak memiliki hak akses mengubah mata kuliah prodi lain.');
            }
        }

        $data = $this->validasi($request, $mataKuliah->id);

        if ($user?->role === 'Prodi' && $user->prodi?->nama) {
            $data['prodi'] = $this->getUserProdiName($user);
        }

        $cleanKode = preg_replace('/-[A-Za-z0-9]+$/', '', $data['kode_mk']);
        $master = MataKuliah::firstOrCreate(
            ['kode' => $cleanKode],
            [
                'nama' => $data['nama_mk'],
                'sks' => $data['sks'],
                'semester' => $data['semester'],
                'tipe' => 'Prodi',
                'kategori' => 'MK Prodi Wajib',
                'pemilik' => $data['prodi'],
                'level_id' => 1,
            ]
        );
        $data['master_mk_id'] = $master->id;

        $mataKuliah->update($data);

        return $this->kembali('Mata kuliah berhasil diperbarui.');
    }

    public function destroy(MataKuliahDetail $mataKuliah)
    {
        $user = auth()->user();

        if ($user?->role === 'Prodi' && $user->prodi?->nama) {
            if (! $this->userOwnsProdi($user, $mataKuliah->prodi)) {
                return back()->with('flash_error', 'Anda tidak memiliki hak akses menghapus mata kuliah prodi lain.');
            }
        }

        $rombelIds = $mataKuliah->rombels()->pluck('id');
        if ($rombelIds->isNotEmpty()) {
            DB::table('jadwals')->whereIn('rombel_id', $rombelIds)->delete();
            DB::table('rombels')->whereIn('id', $rombelIds)->delete();
        }

        $mataKuliah->delete();

        return $this->kembali('Mata kuliah berhasil dihapus.');
    }

    public function destroyAll(Request $request)
    {
        $user = auth()->user();

        $userProdi = ($user?->role === 'Prodi') ? $this->getUserProdiName($user) : null;
        $scope = $request->input('scope', 'all');

        if ($scope === 'jadwal_only') {
            if ($userProdi) {
                $idsRombel = Rombel::whereHas('mataKuliah', fn ($q) => $q->where('prodi', 'like', "%{$userProdi}%"))->pluck('id');
                DB::table('jadwals')->whereIn('rombel_id', $idsRombel)->delete();
                DB::table('rombels')->whereIn('id', $idsRombel)->delete();
            } else {
                DB::table('jadwals')->delete();
                DB::table('rombels')->delete();
            }

            session()->forget('log_generate');
            Cache::forget('penjadwalan_last_log');

            return redirect()
                ->route('penjadwalan.index')
                ->with('tab_aktif', 'tab-jadwal')
                ->with('flash_success', 'Semua jadwal kuliah berhasil dihapus.');
        }

        // Hapus data jadwal, rombel, dan mata kuliah
        if ($userProdi) {
            $query = MataKuliahDetail::where('prodi', 'like', "%{$userProdi}%");
            $idsMk = $query->pluck('id');
            $idsRombel = Rombel::whereIn('mata_kuliah_detail_id', $idsMk)->pluck('id');
            DB::table('jadwals')->whereIn('rombel_id', $idsRombel)->delete();
            DB::table('rombels')->whereIn('id', $idsRombel)->delete();
            $count = $query->count();
            $query->delete();
        } else {
            DB::table('jadwals')->delete();
            DB::table('rombels')->delete();

            $query = MataKuliahDetail::query();
            $count = $query->count();
            $query->delete();
        }

        session()->forget(['log_generate', 'prodi_aktif']);
        Cache::forget('penjadwalan_last_log');

        return redirect()
            ->route('penjadwalan.index')
            ->with('tab_aktif', 'tab-input')
            ->with('flash_success', 'Semua data mata kuliah ('.$count.' data) dan jadwal berhasil dihapus.');
    }

    public function generate(Request $request, SchedulerService $scheduler, GeneticSchedulerService $geneticScheduler)
    {
        $user = auth()->user();

        if ($request->has('semester_mode') && ! $request->has('semester')) {
            $mode = $request->input('semester_mode');
            if ($mode === 'ganjil') {
                $request->merge(['semester' => [1, 3, 5, 7]]);
            } elseif ($mode === 'genap') {
                $request->merge(['semester' => [2, 4, 6, 8]]);
            } elseif ($mode === 'all') {
                $request->merge(['semester' => [1, 2, 3, 4, 5, 6, 7, 8]]);
            } elseif (is_numeric($mode)) {
                $request->merge(['semester' => [(int) $mode]]);
            }
        }

        $request->validate([
            'periode' => ['nullable', 'string', Rule::in(['ganjil', 'genap', 'keduanya', 'custom'])],
            'semester' => ['required', 'array', 'min:1'],
            'semester.*' => ['integer', Rule::in(self::SEMESTER_ALL)],
            'prodi' => ['nullable', 'string', 'max:100'],
            'hari' => ['nullable', 'array'],
            'hari.*' => [Rule::in(SchedulerService::HARI)],
            'jam_mulai' => ['nullable', 'array'],
            'jam_mulai.*' => ['date_format:H:i'],
            'meta' => ['nullable', 'array'],
            'algoritma' => ['nullable', 'string', Rule::in(['greedy', 'genetic'])],
        ]);

        $semesters = array_map('intval', $request->input('semester'));
        $prodi = $request->input('prodi') ?: 'Teknik Informatika';

        if ($user?->role === 'Prodi' && $user->prodi?->nama) {
            $prodi = $this->getUserProdiName($user);
        }

        $prodiScheduler = in_array(strtolower(trim($prodi)), ['semua', 'all'], true) ? null : $prodi;

        $algoritma = $request->input('algoritma', 'greedy');
        $options = array_filter([
            'hari' => $request->input('hari'),
            'jam_mulai' => $request->input('jam_mulai'),
            'meta' => $request->input('meta'),
        ]);

        $log = [];
        $totalBerhasil = 0;
        $allKegagalan = [];

        foreach ($semesters as $sem) {
            if ($algoritma === 'genetic') {
                $hasil = $geneticScheduler->generate((int) $sem, $prodiScheduler, $options);
            } else {
                $hasil = $scheduler->generate((int) $sem, $prodiScheduler, $options);
            }

            $totalBerhasil += $hasil['jumlah_jadwal'] ?? 0;
            $kegagalanSem = $hasil['kegagalan'] ?? [];
            if (! empty($kegagalanSem)) {
                $allKegagalan = array_merge($allKegagalan, $kegagalanSem);
            }

            $log[] = [
                'semester' => $hasil['semester'],
                'jumlah' => $hasil['jumlah_jadwal'],
                'log' => $hasil['log'],
                'peringatan' => $hasil['peringatan'],
                'kegagalan' => $kegagalanSem,
                'slot_pilihan' => array_column($hasil['slot_pilihan'] ?? [], 'label'),
                'kuota_pilihan' => $hasil['kuota_pilihan'] ?? 0,
                'statistik' => $hasil['statistik'] ?? [],
                'metode' => $hasil['metode'] ?? 'Greedy',
                'prodi' => $prodi,
            ];
        }

        $periodeTarget = 'ganjil';
        $hasGanjil = ! empty(array_intersect($semesters, self::SEMESTER_GANJIL));
        $hasGenap = ! empty(array_intersect($semesters, self::SEMESTER_GENAP));
        if ($hasGenap && ! $hasGanjil) {
            $periodeTarget = 'genap';
        }

        $metodeLabel = $algoritma === 'genetic' ? 'Genetic Algorithm' : 'Greedy Algorithm';
        $prodiFilterView = $prodiScheduler ?: 'all';

        // Simpan ke persistent session dan cache agar tidak hilang saat pengguna melakukan refresh halaman
        session([
            'tab_aktif' => 'tab-jadwal',
            'periode_aktif' => $periodeTarget,
            'prodi_aktif' => $prodiFilterView,
            'log_generate' => $log,
        ]);
        Cache::forever('penjadwalan_last_log', $log);

        $response = redirect()
            ->route('penjadwalan.index')
            ->with('tab_aktif', 'tab-jadwal')
            ->with('periode_aktif', $periodeTarget)
            ->with('prodi_aktif', $prodiFilterView);

        if (! empty($allKegagalan)) {
            $totalGagal = count($allKegagalan);

            return $response->with('flash_warning', "Penjadwalan selesai dengan {$totalBerhasil} kelas berhasil dan {$totalGagal} kelas membutuhkan penyesuaian/gagal ditempatkan. Silakan periksa Log Diagnosa Penjadwalan.");
        }

        return $response->with('flash_success', "Jadwal berhasil digenerate 100% menggunakan {$metodeLabel} untuk semester ".implode(', ', $semesters).'.');
    }

    public function clearLog(): RedirectResponse
    {
        session()->forget('log_generate');
        Cache::forget('penjadwalan_last_log');

        return redirect()
            ->route('penjadwalan.index')
            ->with('flash_success', 'Log generate terakhir berhasil dibersihkan.');
    }

    public function downloadTemplate(MataKuliahImportService $importer): BinaryFileResponse
    {
        $path = $importer->templatePath();

        return response()->download($path, 'template-mata-kuliah.xlsx')->deleteFileAfterSend(true);
    }

    public function previewImport(Request $request, MataKuliahImportService $importer)
    {
        $user = auth()->user();

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv', 'ods', 'txt'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung. Harap gunakan file .xlsx, .xls, .csv, atau .ods.',
            ], 422);
        }

        try {
            $path = $request->file('file')->getRealPath();
            $data = $importer->preview($path);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca kolom spreadsheet: '.$e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request, MataKuliahImportService $importer)
    {
        $user = auth()->user();

        $request->validate([
            'file' => ['nullable', 'file', 'max:10240'],
            'verified_items' => ['nullable', 'string'],
            'mapping' => ['nullable', 'array'],
            'default_prodi' => ['nullable', 'string', 'max:100'],
            'default_tipe' => ['nullable', 'string', 'in:Wajib,Pilihan'],
        ], [
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        $verifiedItems = [];
        if ($request->filled('verified_items')) {
            $decoded = json_decode($request->input('verified_items'), true);
            if (is_array($decoded) && ! empty($decoded)) {
                $verifiedItems = $decoded;
            }
        }

        if (empty($verifiedItems) && ! $request->hasFile('file')) {
            return redirect()->back()->with('flash_error', 'Pilih file Excel atau Spreadsheet terlebih dahulu.');
        }

        $path = '';
        if ($request->hasFile('file')) {
            $extension = strtolower($request->file('file')->getClientOriginalExtension());
            if (! in_array($extension, ['xlsx', 'xls', 'csv', 'ods', 'txt'], true)) {
                return redirect()->back()->with('flash_error', 'Format file tidak didukung. Harap unggah file .xlsx, .xls, .csv, atau .ods.');
            }
            $path = $request->file('file')->getRealPath();
        }

        $mapping = $request->input('mapping') ?: [];
        $defaultProdi = $request->input('default_prodi') ?: 'Informatika';
        if ($user?->role === 'Prodi' && $user->prodi?->nama) {
            $defaultProdi = $this->getUserProdiName($user);
        }

        $defaults = [
            'prodi' => $defaultProdi,
            'tipe' => $request->input('default_tipe') ?: 'Wajib',
        ];

        $hasil = $importer->import($path, $mapping, $defaults, $verifiedItems);

        if ($hasil['total'] === 0) {
            return redirect()->back()->with('flash_error', 'File kosong atau tidak ada baris data valid.');
        }

        if (! empty($hasil['failed'])) {
            return redirect()->back()
                ->with('flash_error', 'Import selesai dengan catatan: '.$hasil['failed'][0].(count($hasil['failed']) > 1 ? ' (+'.(count($hasil['failed']) - 1).' galat lain)' : ''))
                ->with('import_gagal', $hasil['failed'])
                ->with('flash_success', "Import: {$hasil['created']} tambah, {$hasil['updated']} update.");
        }

        return redirect()->back()->with('flash_success', "Import sukses: {$hasil['created']} tambah, {$hasil['updated']} update.");
    }

    protected function validasi(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'kode_mk' => [
                'required', 'string', 'max:20',
                Rule::unique('mata_kuliah_details', 'kode_mk')->ignore($ignoreId),
            ],
            'nama_mk' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:4'],
            'semester' => ['required', 'integer', Rule::in(self::SEMESTER_ALL)],
            'tipe' => ['required', Rule::in(['Wajib', 'Pilihan'])],
            'prodi' => ['required', 'string', 'max:100'],
            'dosen_ketua_id' => ['nullable', 'integer', 'exists:dosens,id'],
            'dosen_anggota_id' => ['nullable', 'integer', 'exists:dosens,id', 'different:dosen_ketua_id'],
            'jumlah_kelas' => ['required', 'integer', 'min:1', 'max:10'],
            'kapasitas_per_kelas' => ['required', 'integer', 'min:10', 'max:200'],
            'butuh_lab' => ['nullable', 'boolean'],
            'tanpa_ruangan' => ['nullable', 'boolean'],
        ], [
            'kode_mk.unique' => 'Kode MK sudah dipakai mata kuliah lain.',
            'kode_mk.required' => 'Kode MK wajib diisi.',
            'nama_mk.required' => 'Nama MK wajib diisi.',
            'semester.in' => 'Semester harus bernilai antara 1 sampai 8.',
            'sks.max' => 'SKS maksimal 4.',
            'tipe.in' => 'Tipe harus Wajib atau Pilihan.',
            'dosen_ketua_id.exists' => 'Dosen ketua tidak ditemukan.',
            'dosen_anggota_id.exists' => 'Dosen anggota tidak ditemukan.',
            'dosen_anggota_id.different' => 'Dosen anggota harus berbeda dari dosen ketua.',
            'jumlah_kelas.max' => 'Jumlah kelas maksimal 10.',
            'jumlah_kelas.min' => 'Jumlah kelas minimal 1.',
            'kapasitas_per_kelas.min' => 'Kapasitas per kelas minimal 10.',
            'kapasitas_per_kelas.max' => 'Kapasitas per kelas maksimal 200.',
            'nama_mk.max' => 'Nama MK maksimal 150 karakter.',
            'kode_mk.max' => 'Kode MK maksimal 20 karakter.',
            'dosen_ketua_id.integer' => 'Dosen ketua tidak valid.',
            'dosen_anggota_id.integer' => 'Dosen anggota tidak valid.',
            'sks.min' => 'SKS minimal 1.',
            'sks.integer' => 'SKS harus berupa angka.',
            'sks.required' => 'SKS wajib diisi.',
            'prodi.required' => 'Program studi wajib diisi.',
            'prodi.max' => 'Program studi maksimal 100 karakter.',
            'semester.required' => 'Semester wajib diisi.',
            'semester.integer' => 'Semester harus berupa angka.',
            'tipe.required' => 'Tipe wajib diisi.',
            'jumlah_kelas.required' => 'Jumlah kelas wajib diisi.',
            'jumlah_kelas.integer' => 'Jumlah kelas harus berupa angka.',
            'kapasitas_per_kelas.required' => 'Kapasitas per kelas wajib diisi.',
            'kapasitas_per_kelas.integer' => 'Kapasitas per kelas harus berupa angka.',
        ]);

        return [
            'kode_mk' => trim($validated['kode_mk']),
            'nama_mk' => trim($validated['nama_mk']),
            'sks' => $validated['sks'],
            'semester' => $validated['semester'],
            'tipe' => $validated['tipe'],
            'prodi' => trim($validated['prodi']),
            'dosen_ketua_id' => $validated['dosen_ketua_id'] ?? null,
            'dosen_anggota_id' => $validated['dosen_anggota_id'] ?? null,
            'jumlah_kelas' => $validated['jumlah_kelas'],
            'kapasitas_per_kelas' => $validated['kapasitas_per_kelas'],
            'butuh_lab' => $request->boolean('butuh_lab'),
            'tanpa_ruangan' => $request->boolean('tanpa_ruangan'),
        ];
    }

    protected function kembali(string $pesan)
    {
        return redirect()
            ->route('penjadwalan.index')
            ->with('flash_success', $pesan);
    }
}
