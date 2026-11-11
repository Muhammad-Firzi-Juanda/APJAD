<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RuanganController extends Controller
{
    /**
     * Tampilkan daftar ruangan (redirect ke tab ruangan di halaman penjadwalan).
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('penjadwalan.index')->with('tab_aktif', 'tab-ruangan');
    }

    /**
     * Form tambah ruangan (redirect ke tab ruangan).
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('penjadwalan.index')->with('tab_aktif', 'tab-ruangan');
    }

    /**
     * Ambil data satu ruangan (untuk kebutuhan AJAX modal edit).
     */
    public function show(Ruangan $ruangan): JsonResponse
    {
        return response()->json($ruangan);
    }

    /**
     * Simpan ruangan baru ke database.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:ruangans,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'tipe' => ['required', 'string', Rule::in(['Lab', 'Ruang Kelas', 'Aula', 'Auditorium', 'Lainnya'])],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:500'],
            'gedung' => ['nullable', 'string', 'max:50'],
            'lantai' => ['nullable', 'string', 'max:10'],
            'is_active' => ['nullable'],
        ], [
            'kode.required' => 'Kode ruangan wajib diisi.',
            'kode.unique' => 'Kode ruangan sudah digunakan.',
            'nama.required' => 'Nama ruangan wajib diisi.',
            'tipe.required' => 'Tipe ruangan wajib dipilih.',
            'kapasitas.required' => 'Kapasitas ruangan wajib diisi.',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        $ruangan = Ruangan::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $ruangan,
                'message' => "Ruangan {$ruangan->kode} berhasil ditambahkan!",
            ]);
        }

        return redirect()
            ->route('penjadwalan.index')
            ->with('tab_aktif', 'tab-ruangan')
            ->with('flash_success', "Ruangan {$ruangan->kode} berhasil ditambahkan!");
    }

    /**
     * Form edit ruangan (redirect ke tab ruangan).
     */
    public function edit(Ruangan $ruangan): RedirectResponse
    {
        return redirect()->route('penjadwalan.index')->with('tab_aktif', 'tab-ruangan');
    }

    /**
     * Update data ruangan yang ada.
     */
    public function update(Request $request, Ruangan $ruangan): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', Rule::unique('ruangans', 'kode')->ignore($ruangan->id)],
            'nama' => ['required', 'string', 'max:100'],
            'tipe' => ['required', 'string', Rule::in(['Lab', 'Ruang Kelas', 'Aula', 'Auditorium', 'Lainnya'])],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:500'],
            'gedung' => ['nullable', 'string', 'max:50'],
            'lantai' => ['nullable', 'string', 'max:10'],
            'is_active' => ['nullable'],
        ], [
            'kode.required' => 'Kode ruangan wajib diisi.',
            'kode.unique' => 'Kode ruangan sudah digunakan.',
            'nama.required' => 'Nama ruangan wajib diisi.',
            'tipe.required' => 'Tipe ruangan wajib dipilih.',
            'kapasitas.required' => 'Kapasitas ruangan wajib diisi.',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : $ruangan->is_active;

        $ruangan->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $ruangan,
                'message' => "Ruangan {$ruangan->kode} berhasil diperbarui!",
            ]);
        }

        return redirect()
            ->route('penjadwalan.index')
            ->with('tab_aktif', 'tab-ruangan')
            ->with('flash_success', "Ruangan {$ruangan->kode} berhasil diperbarui!");
    }

    /**
     * Hapus ruangan dari database.
     */
    public function destroy(Request $request, Ruangan $ruangan): JsonResponse|RedirectResponse
    {
        if ($ruangan->isDefault()) {
            $pesan = "Ruangan bawaan resmi kampus ({$ruangan->kode}) tidak dapat dihapus. Anda dapat menonaktifkannya jika tidak digunakan.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $pesan], 422);
            }

            return redirect()
                ->route('penjadwalan.index')
                ->with('tab_aktif', 'tab-ruangan')
                ->with('flash_error', $pesan);
        }

        $kode = $ruangan->kode;
        $ruangan->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Ruangan {$kode} berhasil dihapus!",
            ]);
        }

        return redirect()
            ->route('penjadwalan.index')
            ->with('tab_aktif', 'tab-ruangan')
            ->with('flash_success', "Ruangan {$kode} berhasil dihapus!");
    }

    /**
     * Toggle status aktif / nonaktif ruangan.
     */
    public function toggleStatus(Request $request, Ruangan $ruangan): JsonResponse|RedirectResponse
    {
        $ruangan->is_active = ! $ruangan->is_active;
        $ruangan->save();

        $statusText = $ruangan->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $pesan = "Ruangan {$ruangan->kode} berhasil {$statusText}!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $ruangan->is_active,
                'message' => $pesan,
            ]);
        }

        return redirect()
            ->route('penjadwalan.index')
            ->with('tab_aktif', 'tab-ruangan')
            ->with('flash_success', $pesan);
    }
}
