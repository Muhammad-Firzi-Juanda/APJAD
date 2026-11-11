<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Services\AccountAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JurusanController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->user() || ! AccountAuthorizationService::canViewMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Super Admin dan Admin yang dapat melihat Master Data Organisasi.');
        }

        $canManageMasterData = AccountAuthorizationService::canManageMasterData($request->user());

        $search = trim((string) $request->query('search'));
        $filterStatus = strtoupper(trim((string) $request->query('status')));
        $filterFakultasId = $request->query('fakultas_id');

        $query = Jurusan::with('fakultas')->withCount(['prodi', 'users']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if (in_array($filterStatus, [Jurusan::STATUS_ACTIVE, Jurusan::STATUS_INACTIVE], true)) {
            $query->where('status', $filterStatus);
        } else {
            $filterStatus = '';
        }

        if (! empty($filterFakultasId)) {
            $query->where('fakultas_id', (int) $filterFakultasId);
        }

        $jurusanList = $query->orderBy('kode')->orderBy('nama')->get();
        $fakultasList = Fakultas::active()->orderBy('nama')->get();
        $allFakultas = Fakultas::orderBy('nama')->get();

        return view('admin.master-data.jurusan', compact(
            'jurusanList',
            'fakultasList',
            'allFakultas',
            'search',
            'filterStatus',
            'filterFakultasId',
            'canManageMasterData'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        $request->merge([
            'fakultas_id' => $request->input('fakultas_id') ?? $request->input('faculty_id'),
            'kode' => trim((string) ($request->input('kode') ?? $request->input('code') ?? '')),
            'nama' => trim((string) ($request->input('nama') ?? $request->input('name') ?? '')),
        ]);

        $validated = $request->validate([
            'fakultas_id' => ['required', 'integer', 'exists:fakultas,id'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('jurusan', 'kode')],
            'nama' => ['required', 'string', 'max:100', Rule::unique('jurusan', 'nama')],
            'kajur' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([Jurusan::STATUS_ACTIVE, Jurusan::STATUS_INACTIVE])],
        ], [
            'fakultas_id.required' => 'Fakultas induk wajib dipilih!',
            'fakultas_id.exists' => 'Fakultas induk tidak ditemukan!',
            'kode.required' => 'Kode jurusan wajib diisi!',
            'kode.unique' => 'Kode jurusan sudah digunakan!',
            'nama.required' => 'Nama jurusan wajib diisi!',
            'nama.unique' => 'Nama jurusan sudah ada!',
        ]);

        $fakultas = Fakultas::findOrFail((int) $validated['fakultas_id']);
        if (! $fakultas->isActive()) {
            throw ValidationException::withMessages([
                'fakultas_id' => ['Fakultas yang dipilih berstatus nonaktif dan tidak dapat digunakan untuk membuat relasi baru.'],
            ]);
        }

        Jurusan::create([
            'fakultas_id' => $fakultas->id,
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'kajur' => $validated['kajur'] ?? null,
            'status' => $validated['status'] ?? Jurusan::STATUS_ACTIVE,
        ]);

        return redirect()->back(fallback: route('master-data.jurusan.index'))
            ->with('flash_success', 'Jurusan berhasil ditambahkan!');
    }

    public function update(Request $request, Jurusan $jurusan): RedirectResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        $request->merge([
            'fakultas_id' => $request->input('fakultas_id') ?? $request->input('faculty_id'),
            'kode' => trim((string) ($request->input('kode') ?? $request->input('code') ?? '')),
            'nama' => trim((string) ($request->input('nama') ?? $request->input('name') ?? '')),
        ]);

        $validated = $request->validate([
            'fakultas_id' => ['required', 'integer', 'exists:fakultas,id'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('jurusan', 'kode')->ignore($jurusan->id)],
            'nama' => ['required', 'string', 'max:100', Rule::unique('jurusan', 'nama')->ignore($jurusan->id)],
            'kajur' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([Jurusan::STATUS_ACTIVE, Jurusan::STATUS_INACTIVE])],
        ], [
            'fakultas_id.required' => 'Fakultas induk wajib dipilih!',
            'fakultas_id.exists' => 'Fakultas induk tidak ditemukan!',
            'kode.required' => 'Kode jurusan wajib diisi!',
            'kode.unique' => 'Kode jurusan sudah digunakan!',
            'nama.required' => 'Nama jurusan wajib diisi!',
            'nama.unique' => 'Nama jurusan sudah ada!',
        ]);

        $fakultas = Fakultas::findOrFail((int) $validated['fakultas_id']);
        if ((int) $jurusan->fakultas_id !== $fakultas->id && ! $fakultas->isActive()) {
            throw ValidationException::withMessages([
                'fakultas_id' => ['Fakultas yang dipilih berstatus nonaktif dan tidak dapat digunakan untuk relasi baru.'],
            ]);
        }

        $jurusan->update([
            'fakultas_id' => $fakultas->id,
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'kajur' => $validated['kajur'] ?? $jurusan->kajur,
            'status' => $validated['status'] ?? $jurusan->status,
        ]);

        // Sinkronkan fakultas_id pada seluruh Prodi di bawah Jurusan ini
        $jurusan->prodi()->update(['fakultas_id' => $fakultas->id]);

        return redirect()->back(fallback: route('master-data.jurusan.index'))
            ->with('flash_success', 'Jurusan berhasil diperbarui!');
    }

    public function toggleStatus(Request $request, Jurusan $jurusan): RedirectResponse|JsonResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        $newStatus = $jurusan->isActive() ? Jurusan::STATUS_INACTIVE : Jurusan::STATUS_ACTIVE;
        $jurusan->update(['status' => $newStatus]);

        $statusLabel = $newStatus === Jurusan::STATUS_ACTIVE ? 'diaktifkan' : 'dinonaktifkan';
        $message = "Jurusan {$jurusan->nama} berhasil {$statusLabel}!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $message,
            ]);
        }

        return redirect()->back(fallback: route('master-data.jurusan.index'))
            ->with('flash_success', $message);
    }

    public function destroy(Request $request, ?Jurusan $jurusan = null): RedirectResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        if (! $jurusan || ! $jurusan->exists) {
            $jurusanId = (int) $request->input('jurusan_id');
            $jurusan = Jurusan::findOrFail($jurusanId);
        }

        if ($jurusan->hasRelatedData()) {
            return redirect()->back(fallback: route('master-data.jurusan.index'))
                ->with('flash_error', 'Jurusan tidak dapat dihapus permanen karena sudah digunakan oleh Program Studi atau Akun. Silakan nonaktifkan Jurusan ini.');
        }

        $jurusan->delete();

        return redirect()->back(fallback: route('master-data.jurusan.index'))
            ->with('flash_success', 'Jurusan berhasil dihapus!');
    }
}
<<<<<<< HEAD
=======

>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
