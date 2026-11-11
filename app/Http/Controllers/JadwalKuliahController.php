<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\View\View;

class JadwalKuliahController extends Controller
{
    public function index(): View
    {
        $fakultas = Fakultas::all();
        $prodi = Prodi::with('fakultas')->get();
        $mataKuliahFakultas = MataKuliah::where('tipe', 'Fakultas')->get();

        $prodiInformatika = Prodi::where('nama', 'like', '%Informatika%')->first();
        $mataKuliahProdi = $prodiInformatika
            ? MataKuliah::where('tipe', 'Prodi')->where('level_id', $prodiInformatika->id)->get()
            : MataKuliah::where('tipe', 'Prodi')->get();

        $semesterGanjil = [1, 3, 5, 7];

        return view('jadwal-kuliah.index', compact(
            'fakultas',
            'prodi',
            'mataKuliahFakultas',
            'mataKuliahProdi',
            'prodiInformatika',
            'semesterGanjil'
        ));
    }
}
