<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use App\Services\AccountAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FakultasController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->user() || ! AccountAuthorizationService::canViewMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Super Admin dan Admin yang dapat melihat Master Data Organisasi.');
        }

        $canManageMasterData = AccountAuthorizationService::canManageMasterData($request->user());

        $search = trim((string) $request->query('search'));
        $filterStatus = strtoupper(trim((string) $request->query('status')));

        $query = Fakultas::withCount(['jurusans', 'prodi', 'users']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if (in_array($filterStatus, [Fakultas::STATUS_ACTIVE, Fakultas::STATUS_INACTIVE], true)) {
            $query->where('status', $filterStatus);
        } else {
            $filterStatus = '';
        }

        $fakultasList = $query->orderBy('kode')->orderBy('nama')->get();

        return view('admin.master-data.fakultas', compact('fakultasList', 'search', 'filterStatus', 'canManageMasterData'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        $request->merge([
            'kode' => trim((string) ($request->input('kode') ?? $request->input('code') ?? '')),
            'nama' => trim((string) ($request->input('nama') ?? $request->input('name') ?? '')),
        ]);

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', Rule::unique('fakultas', 'kode')],
            'nama' => ['required', 'string', 'max:100', Rule::unique('fakultas', 'nama')],
            'dekan' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([Fakultas::STATUS_ACTIVE, Fakultas::STATUS_INACTIVE])],
        ], [
            'kode.required' => 'Kode fakultas wajib diisi!',
            'kode.unique' => 'Kode fakultas sudah digunakan!',
            'nama.required' => 'Nama fakultas wajib diisi!',
            'nama.unique' => 'Nama fakultas sudah ada!',
        ]);

        Fakultas::create([
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'dekan' => $validated['dekan'] ?? null,
            'status' => $validated['status'] ?? Fakultas::STATUS_ACTIVE,
        ]);

        return redirect()->back(fallback: route('master-data.fakultas.index'))
            ->with('flash_success', 'Fakultas berhasil ditambahkan!');
    }

    public function update(Request $request, Fakultas $fakultas): RedirectResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        $request->merge([
            'kode' => trim((string) ($request->input('kode') ?? $request->input('code') ?? '')),
            'nama' => trim((string) ($request->input('nama') ?? $request->input('name') ?? '')),
        ]);

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', Rule::unique('fakultas', 'kode')->ignore($fakultas->id)],
            'nama' => ['required', 'string', 'max:100', Rule::unique('fakultas', 'nama')->ignore($fakultas->id)],
            'dekan' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([Fakultas::STATUS_ACTIVE, Fakultas::STATUS_INACTIVE])],
        ], [
            'kode.required' => 'Kode fakultas wajib diisi!',
            'kode.unique' => 'Kode fakultas sudah digunakan!',
            'nama.required' => 'Nama fakultas wajib diisi!',
            'nama.unique' => 'Nama fakultas sudah ada!',
        ]);

        $fakultas->update([
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'dekan' => $validated['dekan'] ?? $fakultas->dekan,
            'status' => $validated['status'] ?? $fakultas->status,
        ]);

        return redirect()->back(fallback: route('master-data.fakultas.index'))
            ->with('flash_success', 'Fakultas berhasil diperbarui!');
    }

    public function toggleStatus(Request $request, Fakultas $fakultas): RedirectResponse|JsonResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        $newStatus = $fakultas->isActive() ? Fakultas::STATUS_INACTIVE : Fakultas::STATUS_ACTIVE;
        $fakultas->update(['status' => $newStatus]);

        $statusLabel = $newStatus === Fakultas::STATUS_ACTIVE ? 'diaktifkan' : 'dinonaktifkan';
        $message = "Fakultas {$fakultas->nama} berhasil {$statusLabel}!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $message,
            ]);
        }

        return redirect()->back(fallback: route('master-data.fakultas.index'))
            ->with('flash_success', $message);
    }

    public function destroy(Request $request, ?Fakultas $fakultas = null): RedirectResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        if (! $fakultas || ! $fakultas->exists) {
            $fakultasId = (int) $request->input('fakultas_id');
            $fakultas = Fakultas::findOrFail($fakultasId);
        }

        if ($fakultas->hasRelatedData()) {
            return redirect()->back(fallback: route('master-data.fakultas.index'))
                ->with('flash_error', 'Fakultas tidak dapat dihapus permanen karena sudah digunakan oleh Jurusan, Prodi, Akun, atau data akademik. Silakan nonaktifkan Fakultas ini.');
        }

        $fakultas->delete();

        return redirect()->back(fallback: route('master-data.fakultas.index'))
            ->with('flash_success', 'Fakultas berhasil dihapus!');
    }
}
