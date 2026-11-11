<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\MataKuliahDetail;
use App\Models\Rombel;
use App\Models\Ruangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProdiManagementController extends Controller
{
    public function dosen(): View
    {
        $user = Auth::user();
        $prodi = $user->prodi;
        $prodiName = $prodi?->nama ?? 'Teknik Informatika';

        $dosens = Dosen::where('prodi', $prodiName)
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return view('prodi.dosen', compact('dosens', 'prodiName'));
    }

    public function mataKuliah(): View
    {
        $user = Auth::user();
        $prodi = $user->prodi;
        $prodiName = $prodi?->nama ?? 'Teknik Informatika';

        $mataKuliahs = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota'])
            ->where('prodi', $prodiName)
            ->where('is_active', true)
            ->orderBy('semester')
            ->orderBy('kode_mk')
            ->get()
            ->groupBy('semester');

        $semesterList = MataKuliahDetail::where('prodi', $prodiName)
            ->where('is_active', true)
            ->distinct()
            ->pluck('semester')
            ->sort()
            ->values();

        return view('prodi.mata-kuliah', compact('mataKuliahs', 'semesterList', 'prodiName'));
    }

    public function rombel(): View
    {
        $user = Auth::user();
        $prodi = $user->prodi;
        $prodiName = $prodi?->nama ?? 'Teknik Informatika';

        $rombels = Rombel::with(['mataKuliah', 'dosenPengampu'])
            ->whereHas('mataKuliah', function ($q) use ($prodiName) {
                $q->where('prodi', $prodiName);
            })
            ->orderBy('kode_rombel')
            ->get();

        return view('prodi.rombel', compact('rombels', 'prodiName'));
    }

    public function jadwal(): View
    {
        $user = Auth::user();
        $prodi = $user->prodi;
        $prodiName = $prodi?->nama ?? 'Teknik Informatika';

        $jadwals = Jadwal::with(['rombel.mataKuliah', 'dosen', 'ruangan'])
            ->whereHas('rombel.mataKuliah', function ($q) use ($prodiName) {
                $q->where('prodi', $prodiName);
            })
            ->where('is_active', true)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $perHari = $jadwals->groupBy('hari');
        $semesterList = MataKuliahDetail::where('prodi', $prodiName)
            ->where('is_active', true)
            ->distinct()
            ->pluck('semester')
            ->sort()
            ->values();

        return view('prodi.jadwal', compact('jadwals', 'perHari', 'hariList', 'semesterList', 'prodiName'));
    }

    public function storeJadwal(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $prodi = $user->prodi;
        $prodiName = $prodi?->nama ?? 'Teknik Informatika';

        $validated = $request->validate([
            'rombel_id' => 'required|exists:rombels,id',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'ruangan_id' => 'required|exists:ruangans,id',
            'dosen_id' => 'required|exists:dosens,id',
        ], [
            'rombel_id.required' => 'Pilih rombel terlebih dahulu!',
            'rombel_id.exists' => 'Rombel tidak valid!',
            'hari.required' => 'Pilih hari!',
            'jam_mulai.required' => 'Jam mulai wajib diisi!',
            'jam_selesai.required' => 'Jam selesai wajib diisi!',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai!',
            'ruangan_id.required' => 'Pilih ruangan!',
            'ruangan_id.exists' => 'Ruangan tidak valid!',
            'dosen_id.required' => 'Pilih dosen!',
            'dosen_id.exists' => 'Dosen tidak valid!',
        ]);

        // Verify rombel belongs to this prodi
        $rombel = Rombel::with('mataKuliah')->findOrFail($validated['rombel_id']);
        if ($rombel->mataKuliah->prodi !== $prodiName) {
            return back()->with('flash_error', 'Rombel bukan milik prodi Anda!');
        }

        // Verify dosen belongs to this prodi
        $dosen = Dosen::findOrFail($validated['dosen_id']);
        if ($dosen->prodi !== $prodiName) {
            return back()->with('flash_error', 'Dosen bukan milik prodi Anda!');
        }

        // Check for conflict (same ruangan, same hari, overlapping time)
        $conflict = Jadwal::where('ruangan_id', $validated['ruangan_id'])
            ->where('hari', $validated['hari'])
            ->where('is_active', true)
            ->where(function ($q) use ($validated) {
                $q->where(function ($sub) use ($validated) {
                    $sub->where('jam_mulai', '<', $validated['jam_selesai'])
                        ->where('jam_selesai', '>', $validated['jam_mulai']);
                });
            })
            ->exists();

        if ($conflict) {
            return back()->with('flash_error', 'Ruangan sudah terpakai pada jam tersebut!');
        }

        // Check for dosen conflict
        $dosenConflict = Jadwal::where('dosen_id', $validated['dosen_id'])
            ->where('hari', $validated['hari'])
            ->where('is_active', true)
            ->where(function ($q) use ($validated) {
                $q->where(function ($sub) use ($validated) {
                    $sub->where('jam_mulai', '<', $validated['jam_selesai'])
                        ->where('jam_selesai', '>', $validated['jam_mulai']);
                });
            })
            ->exists();

        if ($dosenConflict) {
            return back()->with('flash_error', 'Dosen sudah memiliki jadwal pada jam tersebut!');
        }

        // Check for rombel conflict
        $rombelConflict = Jadwal::where('rombel_id', $validated['rombel_id'])
            ->where('hari', $validated['hari'])
            ->where('is_active', true)
            ->where(function ($q) use ($validated) {
                $q->where(function ($sub) use ($validated) {
                    $sub->where('jam_mulai', '<', $validated['jam_selesai'])
                        ->where('jam_selesai', '>', $validated['jam_mulai']);
                });
            })
            ->exists();

        if ($rombelConflict) {
            return back()->with('flash_error', 'Rombel sudah memiliki jadwal pada jam tersebut!');
        }

        $jadwal = Jadwal::create([
            'rombel_id' => $validated['rombel_id'],
            'hari' => $validated['hari'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'ruangan_id' => $validated['ruangan_id'],
            'dosen_id' => $validated['dosen_id'],
            'is_active' => true,
        ]);

        return redirect()->route('prodi.dashboard', ['#section-jadwal'])
            ->with('flash_success', 'Jadwal berhasil ditambahkan!');
    }
}
