<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Fakultas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FakultasCourseController extends Controller
{
    private function getFacultyId(): int
    {
        $user = Auth::user();
        if ($user && $user->role === \App\Enums\UserRole::ADMIN) {
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

    /**
     * Halaman daftar MKWU (Kode Kategori: UNV).
     */
    public function mkwuIndex(Request $request)
    {
        $facultyId = $this->getFacultyId();
        $category = CourseCategory::firstOrCreate(
            ['code' => 'UNV'],
            ['name' => 'Mata Kuliah Wajib Universitas', 'scope' => 'UNIVERSITY', 'faculty_id' => null]
        );

        $query = Course::with('category')
            ->where('faculty_id', $facultyId)
            ->where('course_category_id', $category->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('semester')) {
            $query->where('semester', (int) $request->semester);
        }

        if ($request->filled('type') && in_array($request->type, ['THEORY', 'PRACTICUM'], true)) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status') && in_array($request->status, ['ACTIVE', 'INACTIVE'], true)) {
            $query->where('status', $request->status);
        }

        $courses = $query->orderBy('semester')->orderBy('code')->paginate(15)->withQueryString();

        return view('fakultas.courses.mkwu', compact('courses', 'category'));
    }

    /**
     * Halaman daftar MKWF (Kode Kategori: FST).
     */
    public function mkwfIndex(Request $request)
    {
        $facultyId = $this->getFacultyId();
        $category = CourseCategory::firstOrCreate(
            ['code' => 'FST'],
            ['name' => 'Mata Kuliah Wajib Fakultas', 'scope' => 'FACULTY', 'faculty_id' => $facultyId]
        );

        $query = Course::with('category')
            ->where('faculty_id', $facultyId)
            ->where('course_category_id', $category->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('semester')) {
            $query->where('semester', (int) $request->semester);
        }

        if ($request->filled('type') && in_array($request->type, ['THEORY', 'PRACTICUM'], true)) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status') && in_array($request->status, ['ACTIVE', 'INACTIVE'], true)) {
            $query->where('status', $request->status);
        }

        $courses = $query->orderBy('semester')->orderBy('code')->paginate(15)->withQueryString();

        return view('fakultas.courses.mkwf', compact('courses', 'category'));
    }

    /**
     * Tambah MKWU atau MKWF.
     */
    public function store(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $validated = $request->validate([
            'category_code' => 'required|in:UNV,FST',
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('courses', 'code')->where(fn ($q) => $q->where('faculty_id', $facultyId)),
            ],
            'name' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'required|integer|min:1|max:8',
            'type' => 'required|in:THEORY,PRACTICUM',
            'status' => 'nullable|in:ACTIVE,INACTIVE',
        ], [
            'code.required' => 'Kode mata kuliah wajib diisi.',
            'code.unique' => 'Kode mata kuliah sudah terdaftar pada fakultas ini.',
            'name.required' => 'Nama mata kuliah wajib diisi.',
            'sks.min' => 'SKS minimal 1.',
            'sks.max' => 'SKS maksimal 6.',
            'semester.min' => 'Semester minimal 1.',
            'semester.max' => 'Semester maksimal 8.',
            'type.in' => 'Jenis mata kuliah hanya boleh THEORY atau PRACTICUM.',
            'status.in' => 'Status hanya boleh ACTIVE atau INACTIVE.',
        ]);

        $category = CourseCategory::where('code', $validated['category_code'])->firstOrFail();

        Course::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'sks' => $validated['sks'],
            'semester' => $validated['semester'],
            'type' => $validated['type'],
            'faculty_id' => $facultyId,
            'course_category_id' => $category->id,
            'status' => $validated['status'] ?? 'ACTIVE',
        ]);

        $redirectRoute = $validated['category_code'] === 'UNV' ? 'fakultas.mkwu' : 'fakultas.mkwf';
        $label = $validated['category_code'] === 'UNV' ? 'MKWU' : 'MKWF';

        return redirect()->route($redirectRoute)->with('flash_success', "Data {$label} berhasil ditambahkan.");
    }

    /**
     * Update MKWU atau MKWF.
     */
    public function update(Request $request, Course $course)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $course->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah mata kuliah fakultas lain.');
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('courses', 'code')
                    ->where(fn ($q) => $q->where('faculty_id', $facultyId))
                    ->ignore($course->id),
            ],
            'name' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'required|integer|min:1|max:8',
            'type' => 'required|in:THEORY,PRACTICUM',
            'status' => 'nullable|in:ACTIVE,INACTIVE',
        ], [
            'code.required' => 'Kode mata kuliah wajib diisi.',
            'code.unique' => 'Kode mata kuliah sudah terdaftar pada fakultas ini.',
            'name.required' => 'Nama mata kuliah wajib diisi.',
            'sks.min' => 'SKS minimal 1.',
            'sks.max' => 'SKS maksimal 6.',
            'semester.min' => 'Semester minimal 1.',
            'semester.max' => 'Semester maksimal 8.',
            'type.in' => 'Jenis mata kuliah hanya boleh THEORY atau PRACTICUM.',
            'status.in' => 'Status hanya boleh ACTIVE atau INACTIVE.',
        ]);

        $course->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'sks' => $validated['sks'],
            'semester' => $validated['semester'],
            'type' => $validated['type'],
            'status' => $validated['status'] ?? $course->status,
        ]);

        $categoryCode = $course->category->code ?? 'UNV';
        $redirectRoute = $categoryCode === 'UNV' ? 'fakultas.mkwu' : 'fakultas.mkwf';
        $label = $categoryCode === 'UNV' ? 'MKWU' : 'MKWF';

        return redirect()->route($redirectRoute)->with('flash_success', "Data {$label} {$course->code} berhasil diperbarui.");
    }

    /**
     * Toggle status ACTIVE / INACTIVE.
     */
    public function toggleStatus(Course $course)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $course->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses pada mata kuliah ini.');
        }

        $course->status = $course->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $course->save();

        $categoryCode = $course->category->code ?? 'UNV';
        $redirectRoute = $categoryCode === 'UNV' ? 'fakultas.mkwu' : 'fakultas.mkwf';

        return redirect()->route($redirectRoute)->with('flash_success', "Status mata kuliah {$course->code} diubah menjadi {$course->status}.");
    }

    /**
     * Hapus MKWU atau MKWF.
     */
    public function destroy(Course $course)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $course->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus mata kuliah ini.');
        }

        $categoryCode = $course->category->code ?? 'UNV';
        $code = $course->code;
        $course->delete();

        $redirectRoute = $categoryCode === 'UNV' ? 'fakultas.mkwu' : 'fakultas.mkwf';

        return redirect()->route($redirectRoute)->with('flash_success', "Mata kuliah {$code} berhasil dihapus.");
    }

    /**
     * Bulk Delete MKWU atau MKWF yang dipilih (Select All / Checkbox).
     */
    public function bulkDestroy(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $validated = $request->validate([
            'selected_ids' => 'required|array|min:1',
            'selected_ids.*' => 'integer|exists:courses,id',
            'category_code' => 'nullable|in:UNV,FST',
        ]);

        $deletedCount = Course::where('faculty_id', $facultyId)
            ->whereIn('id', $validated['selected_ids'])
            ->delete();

        $categoryCode = $validated['category_code'] ?? 'UNV';
        $redirectRoute = $categoryCode === 'UNV' ? 'fakultas.mkwu' : 'fakultas.mkwf';
        $label = $categoryCode === 'UNV' ? 'MKWU' : 'MKWF';

        return redirect()->route($redirectRoute)->with('flash_success', "{$deletedCount} data {$label} berhasil dihapus.");
    }
}
