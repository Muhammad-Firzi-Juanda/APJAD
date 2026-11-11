<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Prodi;
use App\Services\AccountAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProdiController extends Controller
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
        $filterJurusanId = $request->query('jurusan_id');

        $query = Prodi::with(['fakultas', 'jurusan'])->withCount('users');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if (in_array($filterStatus, [Prodi::STATUS_ACTIVE, Prodi::STATUS_INACTIVE], true)) {
            $query->where('status', $filterStatus);
        } else {
            $filterStatus = '';
        }

        if (! empty($filterFakultasId)) {
            $query->where('fakultas_id', (int) $filterFakultasId);
        }

        if (! empty($filterJurusanId)) {
            $query->where('jurusan_id', (int) $filterJurusanId);
        }

        $prodiList = $query->orderBy('kode')->orderBy('nama')->get();
        $fakultasList = Fakultas::active()->orderBy('nama')->get();
        $jurusanList = Jurusan::active()->with('fakultas')->orderBy('nama')->get();
        $allFakultas = Fakultas::orderBy('nama')->get();
        $allJurusan = Jurusan::with('fakultas')->orderBy('nama')->get();

        return view('admin.master-data.prodi', compact(
            'prodiList',
            'fakultasList',
            'jurusanList',
            'allFakultas',
            'allJurusan',
            'search',
            'filterStatus',
            'filterFakultasId',
            'filterJurusanId',
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
            'jurusan_id' => $request->input('jurusan_id') ?? $request->input('department_id'),
            'kode' => trim((string) ($request->input('kode') ?? $request->input('code') ?? '')),
            'nama' => trim((string) ($request->input('nama') ?? $request->input('name') ?? '')),
        ]);

        $validated = $request->validate([
            'fakultas_id' => ['required', 'integer', 'exists:fakultas,id'],
            'jurusan_id' => ['required', 'integer', 'exists:jurusan,id'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('prodi', 'kode')],
            'nama' => ['required', 'string', 'max:100', Rule::unique('prodi', 'nama')],
            'kaprodi' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([Prodi::STATUS_ACTIVE, Prodi::STATUS_INACTIVE])],
        ], [
            'fakultas_id.required' => 'Pilih fakultas terlebih dahulu!',
            'fakultas_id.exists' => 'Fakultas tidak ditemukan!',
            'jurusan_id.required' => 'Pilih jurusan terlebih dahulu!',
            'jurusan_id.exists' => 'Jurusan tidak ditemukan!',
            'kode.required' => 'Kode prodi wajib diisi!',
            'kode.unique' => 'Kode prodi sudah ada!',
            'nama.required' => 'Nama prodi wajib diisi!',
            'nama.unique' => 'Nama prodi sudah ada!',
        ]);

        $fakultas = Fakultas::findOrFail((int) $validated['fakultas_id']);
        $jurusan = Jurusan::findOrFail((int) $validated['jurusan_id']);

        if (! $fakultas->isActive()) {
            throw ValidationException::withMessages([
                'fakultas_id' => ['Fakultas yang dipilih berstatus nonaktif dan tidak dapat digunakan untuk membuat relasi baru.'],
            ]);
        }

        if (! $jurusan->isActive()) {
            throw ValidationException::withMessages([
                'jurusan_id' => ['Jurusan yang dipilih berstatus nonaktif dan tidak dapat digunakan untuk membuat relasi baru.'],
            ]);
        }

        if ((int) $jurusan->fakultas_id !== (int) $fakultas->id) {
            throw ValidationException::withMessages([
                'jurusan_id' => ['Jurusan yang dipilih tidak berada di bawah Fakultas tersebut.'],
            ]);
        }

        Prodi::create([
            'fakultas_id' => $jurusan->fakultas_id,
            'jurusan_id' => $jurusan->id,
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'kaprodi' => $validated['kaprodi'] ?? null,
            'status' => $validated['status'] ?? Prodi::STATUS_ACTIVE,
        ]);

        return redirect()->back(fallback: route('master-data.prodi.index'))
            ->with('flash_success', 'Program studi berhasil ditambahkan!');
    }

    public function update(Request $request, Prodi $prodi): RedirectResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        $request->merge([
            'fakultas_id' => $request->input('fakultas_id') ?? $request->input('faculty_id'),
            'jurusan_id' => $request->input('jurusan_id') ?? $request->input('department_id'),
            'kode' => trim((string) ($request->input('kode') ?? $request->input('code') ?? '')),
            'nama' => trim((string) ($request->input('nama') ?? $request->input('name') ?? '')),
        ]);

        $validated = $request->validate([
            'fakultas_id' => ['required', 'integer', 'exists:fakultas,id'],
            'jurusan_id' => ['required', 'integer', 'exists:jurusan,id'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('prodi', 'kode')->ignore($prodi->id)],
            'nama' => ['required', 'string', 'max:100', Rule::unique('prodi', 'nama')->ignore($prodi->id)],
            'kaprodi' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([Prodi::STATUS_ACTIVE, Prodi::STATUS_INACTIVE])],
        ], [
            'fakultas_id.required' => 'Pilih fakultas terlebih dahulu!',
            'fakultas_id.exists' => 'Fakultas tidak ditemukan!',
            'jurusan_id.required' => 'Pilih jurusan terlebih dahulu!',
            'jurusan_id.exists' => 'Jurusan tidak ditemukan!',
            'kode.required' => 'Kode prodi wajib diisi!',
            'kode.unique' => 'Kode prodi sudah ada!',
            'nama.required' => 'Nama prodi wajib diisi!',
            'nama.unique' => 'Nama prodi sudah ada!',
        ]);

        $fakultas = Fakultas::findOrFail((int) $validated['fakultas_id']);
        $jurusan = Jurusan::findOrFail((int) $validated['jurusan_id']);

        if ((int) $prodi->fakultas_id !== $fakultas->id && ! $fakultas->isActive()) {
            throw ValidationException::withMessages([
                'fakultas_id' => ['Fakultas yang dipilih berstatus nonaktif dan tidak dapat digunakan untuk relasi baru.'],
            ]);
        }

        if ((int) $prodi->jurusan_id !== $jurusan->id && ! $jurusan->isActive()) {
            throw ValidationException::withMessages([
                'jurusan_id' => ['Jurusan yang dipilih berstatus nonaktif dan tidak dapat digunakan untuk relasi baru.'],
            ]);
        }

        if ((int) $jurusan->fakultas_id !== (int) $fakultas->id) {
            throw ValidationException::withMessages([
                'jurusan_id' => ['Jurusan yang dipilih tidak berada di bawah Fakultas tersebut.'],
            ]);
        }

        $prodi->update([
            'fakultas_id' => $jurusan->fakultas_id,
            'jurusan_id' => $jurusan->id,
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'kaprodi' => $validated['kaprodi'] ?? $prodi->kaprodi,
            'status' => $validated['status'] ?? $prodi->status,
        ]);

        return redirect()->back(fallback: route('master-data.prodi.index'))
            ->with('flash_success', 'Program studi berhasil diperbarui!');
    }

    public function toggleStatus(Request $request, Prodi $prodi): RedirectResponse|JsonResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        $newStatus = $prodi->isActive() ? Prodi::STATUS_INACTIVE : Prodi::STATUS_ACTIVE;
        $prodi->update(['status' => $newStatus]);

        $statusLabel = $newStatus === Prodi::STATUS_ACTIVE ? 'diaktifkan' : 'dinonaktifkan';
        $message = "Program Studi {$prodi->nama} berhasil {$statusLabel}!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $message,
            ]);
        }

        return redirect()->back(fallback: route('master-data.prodi.index'))
            ->with('flash_success', $message);
    }

    public function destroy(Request $request, ?Prodi $prodi = null): RedirectResponse
    {
        if (! $request->user() || ! AccountAuthorizationService::canAccessMasterData($request->user())) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses Master Data Organisasi.');
        }

        if (! $prodi || ! $prodi->exists) {
            $prodiId = (int) $request->input('prodi_id');
            $prodi = Prodi::findOrFail($prodiId);
        }

        if ($prodi->hasRelatedData()) {
            return redirect()->back(fallback: route('master-data.prodi.index'))
                ->with('flash_error', 'Program Studi tidak dapat dihapus permanen karena sudah digunakan oleh Akun atau data akademik. Silakan nonaktifkan Program Studi ini.');
        }

        $prodi->delete();

        return redirect()->back(fallback: route('master-data.prodi.index'))
            ->with('flash_success', 'Program studi berhasil dihapus!');
    }
}
