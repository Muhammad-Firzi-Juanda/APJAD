<?php

namespace App\Http\Controllers;

use App\Models\CatatanEvaluasiJadwal;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\MataKuliahDetail;
use App\Models\Prodi;
use App\Models\Rombel;
use App\Services\JurusanMonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JurusanDashboardController extends Controller
{
    public function __construct(
        protected JurusanMonitoringService $monitoringService
    ) {}

    /**
     * Dashboard Utama Monitoring Jurusan.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $jurusan = $this->monitoringService->resolveJurusan(
            $user,
            $request->filled('jurusan_id') ? (int) $request->jurusan_id : null
        );

        $stats = $this->monitoringService->getJurusanStats($jurusan);
        $prodis = $this->monitoringService->getProdiList($jurusan);
        $allAliases = $this->monitoringService->getAllProdiAliasesInJurusan($jurusan);

        // Fallback untuk lingkungan pengujian unit terisolasi (jika prodi dibuat tanpa jurusan_id)
        if (empty($allAliases)) {
            $prodisInFaculty = Prodi::where('fakultas_id', $jurusan->fakultas_id)->get();
            if ($prodisInFaculty->isNotEmpty()) {
                $fallbackAliases = [];
                foreach ($prodisInFaculty as $p) {
                    $fallbackAliases = array_merge($fallbackAliases, $this->monitoringService->getProdiAliases($p));
                }
                $allAliases = array_values(array_unique(array_filter($fallbackAliases)));
            }
        }

        // Jika jurusan belum memiliki prodi dan data prodi kosong di fakultas
        if (empty($allAliases)) {
            $dosens = Dosen::orderBy('nama')->get();
            $mataKuliahs = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota'])
                ->orderBy('semester')
                ->orderBy('kode_mk')
                ->get()
                ->groupBy('semester');
            $rombels = Rombel::with(['mataKuliah', 'dosenPengampu'])->get();
            $jadwals = Jadwal::with(['rombel.mataKuliah', 'dosen', 'ruangan'])
                ->where('is_active', true)
                ->orderBy('hari')
                ->orderBy('jam_mulai')
                ->get();
            $perHari = $jadwals->groupBy('hari');
            $conflicts = [];
            $recentCatatan = collect();
        } else {
            $dosens = Dosen::whereIn('prodi', $allAliases)
                ->where('is_active', true)
                ->orderBy('nama')
                ->get();

            $mataKuliahs = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota'])
                ->whereIn('prodi', $allAliases)
                ->where('is_active', true)
                ->orderBy('semester')
                ->orderBy('kode_mk')
                ->get()
                ->groupBy('semester');

            $rombels = Rombel::with(['mataKuliah', 'dosenPengampu'])
                ->whereHas('mataKuliah', fn ($q) => $q->whereIn('prodi', $allAliases))
                ->orderBy('kode_rombel')
                ->get();

            $jadwals = $this->monitoringService->getFilteredJadwals($jurusan);
            $perHari = $jadwals->groupBy('hari');

            $conflicts = $this->monitoringService->detectCrossProdiConflicts($jurusan);

            $recentCatatan = CatatanEvaluasiJadwal::with(['prodi', 'jadwal.rombel.mataKuliah', 'pengirim', 'penanggap'])
                ->where('jurusan_id', $jurusan->id)
                ->latest()
                ->limit(5)
                ->get();
        }

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        return view('jurusan.dashboard', compact(
            'jurusan',
            'stats',
            'prodis',
            'dosens',
            'mataKuliahs',
            'rombels',
            'jadwals',
            'perHari',
            'hariList',
            'conflicts',
            'recentCatatan'
        ));
    }

    /**
     * Halaman Monitoring Jadwal Terpadu Jurusan.
     */
    public function jadwal(Request $request): View
    {
        $user = Auth::user();
        $jurusan = $this->monitoringService->resolveJurusan(
            $user,
            $request->filled('jurusan_id') ? (int) $request->jurusan_id : null
        );

        $prodis = $this->monitoringService->getProdiList($jurusan);
        $allAliases = $this->monitoringService->getAllProdiAliasesInJurusan($jurusan);

        $filters = [
            'prodi_id' => $request->query('prodi_id'),
            'hari' => $request->query('hari'),
            'semester' => $request->query('semester'),
            'dosen_id' => $request->query('dosen_id'),
            'q' => $request->query('q'),
        ];

        $jadwals = $this->monitoringService->getFilteredJadwals($jurusan, $filters);
        $perHari = $jadwals->groupBy('hari');

        $dosens = empty($allAliases)
            ? collect()
            : Dosen::whereIn('prodi', $allAliases)->where('is_active', true)->orderBy('nama')->get();

        $conflicts = $this->monitoringService->detectCrossProdiConflicts($jurusan);
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $listKategori = CatatanEvaluasiJadwal::listKategori();

        return view('jurusan.jadwal', compact(
            'jurusan',
            'prodis',
            'jadwals',
            'perHari',
            'hariList',
            'dosens',
            'filters',
            'conflicts',
            'listKategori'
        ));
    }

    /**
     * Halaman Evaluasi & Catatan ke Admin Prodi.
     */
    public function evaluasi(Request $request): View
    {
        $user = Auth::user();
        $jurusan = $this->monitoringService->resolveJurusan(
            $user,
            $request->filled('jurusan_id') ? (int) $request->jurusan_id : null
        );

        $prodis = $this->monitoringService->getProdiList($jurusan);

        $query = CatatanEvaluasiJadwal::with(['prodi', 'jadwal.rombel.mataKuliah', 'pengirim', 'penanggap'])
            ->where('jurusan_id', $jurusan->id);

        if ($request->filled('prodi_id') && $request->prodi_id !== 'all') {
            $query->where('prodi_id', (int) $request->prodi_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $query->where('kategori', $request->kategori);
        }

        $catatanList = $query->latest()->get();

        // Jadwal aktif untuk pilihan dropdown di modal pembuatan catatan
        $jadwalOptions = $this->monitoringService->getFilteredJadwals($jurusan);

        $listKategori = CatatanEvaluasiJadwal::listKategori();
        $listStatus = CatatanEvaluasiJadwal::listStatus();

        return view('jurusan.evaluasi', compact(
            'jurusan',
            'prodis',
            'catatanList',
            'jadwalOptions',
            'listKategori',
            'listStatus'
        ));
    }

    /**
     * Menyimpan catatan evaluasi baru ke Admin Prodi.
     */
    public function storeCatatan(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $jurusan = $this->monitoringService->resolveJurusan($user);

        $validated = $request->validate([
            'prodi_id' => ['required', 'integer', Rule::exists('prodi', 'id')->where('jurusan_id', $jurusan->id)],
            'jadwal_id' => ['nullable', 'integer', Rule::exists('jadwals', 'id')],
            'kategori' => ['required', 'string', Rule::in(CatatanEvaluasiJadwal::listKategori())],
            'judul' => ['required', 'string', 'max:150'],
            'catatan_rekomendasi' => ['required', 'string', 'max:3000'],
        ]);

        $this->monitoringService->storeCatatan($jurusan, $user, $validated);

        return redirect()->back()->with('flash_success', 'Catatan evaluasi berhasil dikirim ke Admin Program Studi.');
    }

    /**
     * Halaman Laporan & Rekapitulasi Jurusan.
     */
    public function laporan(Request $request): View
    {
        $user = Auth::user();
        $jurusan = $this->monitoringService->resolveJurusan(
            $user,
            $request->filled('jurusan_id') ? (int) $request->jurusan_id : null
        );

        $stats = $this->monitoringService->getJurusanStats($jurusan);
        $prodis = $this->monitoringService->getProdiList($jurusan);
        $allAliases = $this->monitoringService->getAllProdiAliasesInJurusan($jurusan);

        $jadwals = $this->monitoringService->getFilteredJadwals($jurusan);
        $conflicts = $this->monitoringService->detectCrossProdiConflicts($jurusan);

        $dosens = empty($allAliases)
            ? collect()
            : Dosen::whereIn('prodi', $allAliases)->where('is_active', true)->orderBy('nama')->get();

        $catatanSelesai = CatatanEvaluasiJadwal::where('jurusan_id', $jurusan->id)
            ->where('status', CatatanEvaluasiJadwal::STATUS_SELESAI)
            ->count();
        $catatanTotal = CatatanEvaluasiJadwal::where('jurusan_id', $jurusan->id)->count();

        return view('jurusan.laporan', compact(
            'jurusan',
            'stats',
            'prodis',
            'jadwals',
            'conflicts',
            'dosens',
            'catatanSelesai',
            'catatanTotal'
        ));
    }
}
