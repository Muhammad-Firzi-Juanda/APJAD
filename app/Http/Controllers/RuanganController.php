<?php

namespace App\Http\Controllers;

use App\Models\Prodi;
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
        return response()->json($ruangan->load('prodi'));
    }

    /**
     * Simpan ruangan baru ke database.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:ruangans,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'tipe' => ['required', 'string', Rule::in(['Lab', 'Ruang Kelas', 'Aula', 'Auditorium', 'Lainnya'])],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:500'],
            'gedung' => ['nullable', 'string', 'max:50'],
            'lantai' => ['nullable', 'string', 'max:10'],
            'prodi_id' => ['nullable', 'exists:prodi,id'],
            'is_active' => ['nullable'],
        ], [
            'kode.required' => 'Kode ruangan wajib diisi.',
            'kode.unique' => 'Kode ruangan sudah digunakan.',
            'nama.required' => 'Nama ruangan wajib diisi.',
            'tipe.required' => 'Tipe ruangan wajib dipilih.',
            'kapasitas.required' => 'Kapasitas ruangan wajib diisi.',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        // Auto-detect prodi_id dari kode jika tidak diisi langsung
        if (empty($validated['prodi_id']) && ! empty($validated['kode'])) {
            $validated['prodi_id'] = $this->inferProdiIdFromKode($validated['kode']);
        }

        $ruangan = Ruangan::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $ruangan->load('prodi'),
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
            'kode' => ['required', 'string', 'max:30', Rule::unique('ruangans', 'kode')->ignore($ruangan->id)],
            'nama' => ['required', 'string', 'max:100'],
            'tipe' => ['required', 'string', Rule::in(['Lab', 'Ruang Kelas', 'Aula', 'Auditorium', 'Lainnya'])],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:500'],
            'gedung' => ['nullable', 'string', 'max:50'],
            'lantai' => ['nullable', 'string', 'max:10'],
            'prodi_id' => ['nullable', 'exists:prodi,id'],
            'is_active' => ['nullable'],
        ], [
            'kode.required' => 'Kode ruangan wajib diisi.',
            'kode.unique' => 'Kode ruangan sudah digunakan.',
            'nama.required' => 'Nama ruangan wajib diisi.',
            'tipe.required' => 'Tipe ruangan wajib dipilih.',
            'kapasitas.required' => 'Kapasitas ruangan wajib diisi.',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : $ruangan->is_active;

        if (empty($validated['prodi_id']) && ! empty($validated['kode'])) {
            $validated['prodi_id'] = $this->inferProdiIdFromKode($validated['kode']);
        }

        $ruangan->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $ruangan->load('prodi'),
                'message' => "Ruangan {$ruangan->kode} berhasil diperbarui!",
            ]);
        }

        return redirect()
            ->route('penjadwalan.index')
            ->with('tab_aktif', 'tab-ruangan')
            ->with('flash_success', "Ruangan {$ruangan->kode} berhasil diperbarui!");
    }

    /**
     * Toggle status aktif ruangan.
     */
    public function toggleStatus(Ruangan $ruangan): RedirectResponse
    {
        $ruangan->update(['is_active' => ! $ruangan->is_active]);

        $statusText = $ruangan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('penjadwalan.index')
            ->with('tab_aktif', 'tab-ruangan')
            ->with('flash_success', "Ruangan {$ruangan->kode} berhasil {$statusText}.");
    }

    /**
     * Hapus ruangan dari database.
     */
    public function destroy(Ruangan $ruangan): RedirectResponse
    {
        if ($ruangan->isDefault()) {
            return redirect()
                ->route('penjadwalan.index')
                ->with('tab_aktif', 'tab-ruangan')
                ->with('flash_error', "Ruangan {$ruangan->kode} adalah ruangan bawaan resmi kampus dan tidak dapat dihapus. Anda dapat menonaktifkannya jika tidak digunakan.");
        }

        if ($ruangan->jadwals()->where('is_active', true)->exists()) {
            return redirect()
                ->route('penjadwalan.index')
                ->with('tab_aktif', 'tab-ruangan')
                ->with('flash_error', "Ruangan {$ruangan->kode} tidak dapat dihapus karena masih digunakan dalam jadwal aktif.");
        }

        $kode = $ruangan->kode;
        $ruangan->delete();

        return redirect()
            ->route('penjadwalan.index')
            ->with('tab_aktif', 'tab-ruangan')
            ->with('flash_success', "Ruangan {$kode} berhasil dihapus.");
    }

    /**
     * Infer prodi_id dari kode ruangan bila ada pengenal prodi.
     */
    protected function inferProdiIdFromKode(string $kode): ?int
    {
        $kodeUpper = strtoupper(trim($kode));
        $prodis = Prodi::all();

        foreach ($prodis as $pr) {
            $pk = strtoupper($pr->kode);
            if ($pk && (str_contains($kodeUpper, "-{$pk}") || str_contains($kodeUpper, "_{$pk}") || str_contains($kodeUpper, "{$pk}-") || str_contains($kodeUpper, "{$pk}_"))) {
                return $pr->id;
            }
            if ($pk === 'TI' && (str_contains($kodeUpper, 'TIF') || str_contains($kodeUpper, 'INF'))) {
                return $pr->id;
            }
        }

        return null;
    }
}
