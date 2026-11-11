<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Fakultas;
use App\Models\Laboratory;
use App\Models\Prodi;
use App\Models\StudyProgramLaboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FakultasLaboratoryController extends Controller
{
    private function getFacultyId(): int
    {
        $user = Auth::user();
        if ($user && $user->role === UserRole::ADMIN) {
            $requestedFacultyId = request()->input('faculty_id') ?? request()->input('fakultas_id');
            if ($requestedFacultyId) {
                return (int) $requestedFacultyId;
            }

            return (int) (Fakultas::active()->orderBy('id')->value('id')
                ?? Fakultas::orderBy('id')->value('id')
                ?? 1);
        }

        if (! $user || ! $user->fakultas_id || $user->jurusan_id !== null || $user->prodi_id !== null) {
            abort(403, 'Akses ditolak: Akun Fakultas wajib memiliki scope Fakultas yang valid.');
        }

        return (int) $user->fakultas_id;
    }

    public function index(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $query = Laboratory::with('studyPrograms')->where('faculty_id', $facultyId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('lab_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('lab_type')) {
            $query->where('lab_type', $request->lab_type);
        }

        if ($request->filled('status') && in_array($request->status, ['ACTIVE', 'INACTIVE'], true)) {
            $query->where('status', $request->status);
        }

        $laboratories = $query->orderBy('id')->paginate(15)->withQueryString();

        $labTypes = Laboratory::where('faculty_id', $facultyId)
            ->whereNotNull('lab_type')
            ->distinct()
            ->orderBy('lab_type')
            ->pluck('lab_type');

        $allLabs = Laboratory::with('studyPrograms')
            ->where('faculty_id', $facultyId)
            ->orderBy('id')
            ->get();

        $prodis = Prodi::where('fakultas_id', $facultyId)
            ->where('status', Prodi::STATUS_ACTIVE)
            ->orderBy('kode')
            ->get();

        $allocations = StudyProgramLaboratory::with(['laboratory', 'studyProgram'])
            ->whereHas('laboratory', fn ($q) => $q->where('faculty_id', $facultyId))
            ->orderBy('laboratory_id')
            ->paginate(20, ['*'], 'alokasi_page')
            ->withQueryString();

        $activeTab = $request->query('tab', 'laboratorium');

        return view('fakultas.resources.laboratories', compact(
            'laboratories',
            'labTypes',
            'allLabs',
            'prodis',
            'allocations',
            'activeTab'
        ));
    }

    public function store(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('laboratories', 'name')->where(fn ($q) => $q->where('faculty_id', $facultyId)),
            ],
            'capacity' => 'required|integer|min:1|max:500',
            'lab_type' => 'required|string|max:100',
            'status' => 'nullable|in:ACTIVE,INACTIVE',
        ], [
            'name.required' => 'Nama laboratorium wajib diisi.',
            'name.unique' => 'Nama laboratorium sudah terdaftar di fakultas ini.',
            'capacity.required' => 'Kapasitas laboratorium wajib diisi.',
            'capacity.min' => 'Kapasitas minimal 1.',
            'lab_type.required' => 'Jenis / tipe laboratorium wajib diisi.',
        ]);

        Laboratory::create([
            'faculty_id' => $facultyId,
            'name' => trim($validated['name']),
            'capacity' => $validated['capacity'],
            'lab_type' => trim($validated['lab_type']),
            'status' => $validated['status'] ?? 'ACTIVE',
        ]);

        return redirect()->route('fakultas.laboratories.index')->with('flash_success', 'Laboratorium baru berhasil ditambahkan.');
    }

    public function update(Request $request, Laboratory $laboratory)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $laboratory->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laboratorium ini.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('laboratories', 'name')
                    ->where(fn ($q) => $q->where('faculty_id', $facultyId))
                    ->ignore($laboratory->id),
            ],
            'capacity' => 'required|integer|min:1|max:500',
            'lab_type' => 'required|string|max:100',
            'status' => 'nullable|in:ACTIVE,INACTIVE',
        ], [
            'name.required' => 'Nama laboratorium wajib diisi.',
            'name.unique' => 'Nama laboratorium sudah terdaftar di fakultas ini.',
            'capacity.required' => 'Kapasitas laboratorium wajib diisi.',
            'capacity.min' => 'Kapasitas minimal 1.',
            'lab_type.required' => 'Jenis / tipe laboratorium wajib diisi.',
        ]);

        $laboratory->update([
            'name' => trim($validated['name']),
            'capacity' => $validated['capacity'],
            'lab_type' => trim($validated['lab_type']),
            'status' => $validated['status'] ?? $laboratory->status,
        ]);

        return redirect()->route('fakultas.laboratories.index')->with('flash_success', "Laboratorium {$laboratory->name} (Kapasitas: {$laboratory->capacity}) berhasil diperbarui.");
    }

    public function toggleStatus(Laboratory $laboratory)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $laboratory->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses pada laboratorium ini.');
        }

        $laboratory->status = $laboratory->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $laboratory->save();

        return redirect()->route('fakultas.laboratories.index')->with('flash_success', "Status {$laboratory->name} diubah menjadi {$laboratory->status}.");
    }

    public function destroy(Laboratory $laboratory)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $laboratory->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus laboratorium ini.');
        }

        if ($laboratory->studyPrograms()->exists()) {
            return redirect()
                ->route('fakultas.laboratories.index')
                ->with('flash_error', "Laboratorium {$laboratory->name} tidak dapat dihapus karena masih memiliki alokasi aktif ke Program Studi. Silakan hapus alokasi laboratorium terlebih dahulu.");
        }

        $name = $laboratory->name;
        $laboratory->delete();

        return redirect()->route('fakultas.laboratories.index')->with('flash_success', "Laboratorium {$name} berhasil dihapus.");
    }
}
