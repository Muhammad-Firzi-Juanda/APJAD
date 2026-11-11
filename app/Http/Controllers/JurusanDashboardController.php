<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\MataKuliahDetail;
use App\Models\Rombel;
use Illuminate\View\View;

class JurusanDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_dosen' => Dosen::count(),
            'total_mata_kuliah' => MataKuliahDetail::count(),
            'total_rombel' => Rombel::count(),
            'total_jadwal' => Jadwal::where('is_active', true)->count(),
        ];

        $dosens = Dosen::orderBy('nama')->get();
        $mataKuliahs = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota'])
            ->orderBy('semester')
            ->orderBy('kode_mk')
            ->get()
            ->groupBy('semester');

        $rombels = Rombel::with(['mataKuliah', 'dosenPengampu'])
            ->orderBy('kode_rombel')
            ->get();

        $jadwals = Jadwal::with(['rombel.mataKuliah', 'dosen', 'ruangan'])
            ->where('is_active', true)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $perHari = $jadwals->groupBy('hari');

        return view('jurusan.dashboard', compact(
            'stats', 'dosens', 'mataKuliahs', 'rombels', 'jadwals', 'hariList', 'perHari'
        ));
    }
}
