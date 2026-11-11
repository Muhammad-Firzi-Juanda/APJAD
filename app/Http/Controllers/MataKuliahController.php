<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MataKuliahController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'kode' => 'required|string|max:30|unique:mata_kuliah,kode',
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'required|integer|min:1|max:8',
            'tipe' => 'required|in:Fakultas,Prodi',
            'level_id' => 'required|integer',
            'deskripsi' => 'nullable|string',
        ], [
            'nama.required' => 'Nama mata kuliah wajib diisi!',
            'kode.required' => 'Kode mata kuliah wajib diisi!',
            'kode.unique' => 'Kode mata kuliah sudah ada!',
            'sks.required' => 'SKS wajib diisi!',
            'semester.required' => 'Semester wajib diisi!',
            'tipe.required' => 'Tipe wajib dipilih!',
            'level_id.required' => 'Pilih Fakultas/Prodi terlebih dahulu!',
        ]);

        MataKuliah::create($validated);

        return redirect()->route('jadwal-kuliah.index')->with('flash_success', 'Mata kuliah berhasil ditambahkan!');
    }

    public function update(Request $request, MataKuliah $mataKuliah): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'kode' => 'required|string|max:30|unique:mata_kuliah,kode,'.$mataKuliah->id,
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'required|integer|min:1|max:8',
            'tipe' => 'required|in:Fakultas,Prodi',
            'level_id' => 'required|integer',
            'deskripsi' => 'nullable|string',
        ], [
            'nama.required' => 'Nama mata kuliah wajib diisi!',
            'kode.required' => 'Kode mata kuliah wajib diisi!',
            'kode.unique' => 'Kode mata kuliah sudah ada!',
            'sks.required' => 'SKS wajib diisi!',
            'semester.required' => 'Semester wajib diisi!',
            'tipe.required' => 'Tipe wajib dipilih!',
            'level_id.required' => 'Pilih Fakultas/Prodi terlebih dahulu!',
        ]);

        $mataKuliah->update($validated);

        return redirect()->route('jadwal-kuliah.index')->with('flash_success', 'Mata kuliah berhasil diperbarui!');
    }

    public function destroy(Request $request, ?MataKuliah $mataKuliah = null): RedirectResponse
    {
        if (! $mataKuliah || ! $mataKuliah->exists) {
            $mkId = (int) $request->input('mata_kuliah_id');
            $mataKuliah = MataKuliah::findOrFail($mkId);
        }

        $mataKuliah->delete();

        return redirect()->route('jadwal-kuliah.index')->with('flash_success', 'Mata kuliah berhasil dihapus!');
    }
}
