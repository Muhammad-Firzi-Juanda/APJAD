<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\MataKuliahDetail;
use App\Models\Rombel;
use App\Models\Ruangan;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProdiDashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $prodi = $user->prodi;
        $prodiName = $prodi?->nama ?? 'Teknik Informatika';
        $shortProdiName = trim(preg_replace('/^(S1|D3|D4|S2)\s+/i', '', $prodiName));
        $prodiNames = array_values(array_unique(array_filter([$prodiName, $shortProdiName])));

        $stats = [
            'total_dosen' => Dosen::whereIn('prodi', $prodiNames)->where('is_active', true)->count(),
            'total_mata_kuliah' => MataKuliahDetail::whereIn('prodi', $prodiNames)->where('is_active', true)->count(),
            'total_rombel' => Rombel::whereHas('mataKuliah', function ($q) use ($prodiNames) {
                $q->whereIn('prodi', $prodiNames);
            })->count(),
            'total_jadwal' => Jadwal::whereHas('rombel.mataKuliah', function ($q) use ($prodiNames) {
                $q->whereIn('prodi', $prodiNames);
            })->where('is_active', true)->count(),
        ];

        $dosens = Dosen::whereIn('prodi', $prodiNames)->where('is_active', true)->orderBy('nama')->get();

        $rombels = Rombel::with(['mataKuliah', 'dosenPengampu'])
            ->whereHas('mataKuliah', function ($q) use ($prodiNames) {
                $q->whereIn('prodi', $prodiNames);
            })
            ->orderBy('kode_rombel')
            ->get();

        $mataKuliahs = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota'])
            ->whereIn('prodi', $prodiNames)
            ->where('is_active', true)
            ->orderBy('semester')
            ->orderBy('kode_mk')
            ->get()
            ->groupBy('semester');

        $jadwals = Jadwal::with(['rombel.mataKuliah', 'dosen', 'ruangan'])
            ->whereHas('rombel.mataKuliah', function ($q) use ($prodiNames) {
                $q->whereIn('prodi', $prodiNames);
            })
            ->where('is_active', true)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        // Data untuk modal tambah jadwal
        $ruangans = Ruangan::where('is_active', true)->orderBy('kode')->get();
        $dosenProdi = Dosen::whereIn('prodi', $prodiNames)->where('is_active', true)->orderBy('nama')->get();
        $rombelProdi = Rombel::with('mataKuliah')
            ->whereHas('mataKuliah', function ($q) use ($prodiNames) {
                $q->whereIn('prodi', $prodiNames);
            })
            ->orderBy('kode_rombel')
            ->get();

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $perHari = $jadwals->groupBy('hari');
        $semesterList = MataKuliahDetail::whereIn('prodi', $prodiNames)
            ->where('is_active', true)
            ->distinct()
            ->pluck('semester')
            ->sort()
            ->values();

        return view('prodi.dashboard', compact(
            'stats', 'dosens', 'rombels', 'mataKuliahs',
            'jadwals', 'perHari', 'hariList', 'semesterList',
            'prodiName', 'ruangans', 'dosenProdi', 'rombelProdi'
        ));
    }
}
