<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Fakultas;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use App\Models\MataKuliahDetail;
use App\Models\Rombel;
use App\Models\Room;
use App\Models\Ruangan;
use App\Services\MataKuliahImportService;
use App\Services\SchedulerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
=======
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Fakultas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0

class FakultasCourseController extends Controller
{
    private function getFacultyId(): int
    {
        $user = Auth::user();
<<<<<<< HEAD
        if ($user && $user->role === UserRole::ADMIN) {
=======
        if ($user && $user->role === \App\Enums\UserRole::ADMIN) {
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
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

<<<<<<< HEAD
        $course = Course::create([
=======
        Course::create([
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'sks' => $validated['sks'],
            'semester' => $validated['semester'],
            'type' => $validated['type'],
            'faculty_id' => $facultyId,
            'course_category_id' => $category->id,
            'status' => $validated['status'] ?? 'ACTIVE',
        ]);

<<<<<<< HEAD
        // Sinkronisasi otomatis ke master mata_kuliah & mata_kuliah_details untuk Penjadwalan
        $kategoriLabel = $validated['category_code'] === 'UNV' ? 'MK Universitas' : 'MK Fakultas';
        $pemilikLabel = $validated['category_code'] === 'UNV' ? 'Universitas' : 'Fakultas';
        $codeUpper = strtoupper(trim($validated['code']));

        $masterMk = MataKuliah::firstOrCreate(
            ['kode' => $codeUpper],
            [
                'nama' => trim($validated['name']),
                'sks' => $validated['sks'],
                'semester' => $validated['semester'],
                'tipe' => 'Fakultas',
                'kategori' => $kategoriLabel,
                'pemilik' => $pemilikLabel,
                'level_id' => 1,
            ]
        );

        MataKuliahDetail::updateOrCreate(
            ['kode_mk' => $codeUpper],
            [
                'master_mk_id' => $masterMk->id,
                'nama_mk' => trim($validated['name']),
                'sks' => $validated['sks'],
                'semester' => $validated['semester'],
                'tipe' => 'Wajib',
                'kategori' => $kategoriLabel,
                'prodi' => $pemilikLabel,
                'butuh_lab' => $validated['type'] === 'PRACTICUM',
                'is_active' => ($validated['status'] ?? 'ACTIVE') === 'ACTIVE',
                'status_tawar' => true,
                'jumlah_kelas' => 1,
                'kapasitas_per_kelas' => 40,
            ]
        );

=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
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

<<<<<<< HEAD
        $oldCode = $course->code;
        $newCode = strtoupper(trim($validated['code']));

        $course->update([
            'code' => $newCode,
=======
        $course->update([
            'code' => strtoupper(trim($validated['code'])),
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
            'name' => trim($validated['name']),
            'sks' => $validated['sks'],
            'semester' => $validated['semester'],
            'type' => $validated['type'],
            'status' => $validated['status'] ?? $course->status,
        ]);

<<<<<<< HEAD
        $detail = MataKuliahDetail::where('kode_mk', $oldCode)->first();
        if ($detail) {
            $detail->update([
                'kode_mk' => $newCode,
                'nama_mk' => trim($validated['name']),
                'sks' => $validated['sks'],
                'semester' => $validated['semester'],
                'butuh_lab' => $validated['type'] === 'PRACTICUM',
                'is_active' => ($validated['status'] ?? $course->status) === 'ACTIVE',
            ]);
        }

=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
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

<<<<<<< HEAD
        $detail = MataKuliahDetail::where('kode_mk', $course->code)->first();
        if ($detail) {
            $detail->update(['is_active' => $course->status === 'ACTIVE']);
        }

=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
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

<<<<<<< HEAD
        MataKuliahDetail::where('kode_mk', $code)->delete();

=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
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

<<<<<<< HEAD
        $codesToDelete = Course::where('faculty_id', $facultyId)
            ->whereIn('id', $validated['selected_ids'])
            ->pluck('code');

=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
        $deletedCount = Course::where('faculty_id', $facultyId)
            ->whereIn('id', $validated['selected_ids'])
            ->delete();

<<<<<<< HEAD
        if ($codesToDelete->isNotEmpty()) {
            MataKuliahDetail::whereIn('kode_mk', $codesToDelete)->delete();
        }

=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
        $categoryCode = $validated['category_code'] ?? 'UNV';
        $redirectRoute = $categoryCode === 'UNV' ? 'fakultas.mkwu' : 'fakultas.mkwf';
        $label = $categoryCode === 'UNV' ? 'MKWU' : 'MKWF';

        return redirect()->route($redirectRoute)->with('flash_success', "{$deletedCount} data {$label} berhasil dihapus.");
    }
<<<<<<< HEAD

    /**
     * Unduh Template Excel (.xlsx) khusus MKWU atau MKWF.
     */
    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $categoryCode = strtoupper(trim((string) $request->input('category', 'UNV')));
        $isMkwu = $categoryCode === 'UNV';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($isMkwu ? 'Template_MKWU' : 'Template_MKWF');

        $headers = [
            'kode_mk',
            'nama_mk',
            'sks',
            'semester',
            'kelas',
            'tipe',
            'prodi',
            'dosen_ketua',
            'dosen_anggota',
            'jumlah_kelas',
            'kapasitas_per_kelas',
            'butuh_lab',
        ];

        $sheet->fromArray([$headers], null, 'A1');

        if ($isMkwu) {
            $sampleData = [
                ['UNV101', 'Pendidikan Agama Islam', 2, 1, 'A', 'Wajib', 'Universitas', 'Dr. H. Ahmad Fauzi, M.Ag', '', 2, 40, 'Tidak'],
                ['UNV102', 'Pendidikan Pancasila', 2, 1, 'A', 'Wajib', 'Universitas', 'Drs. Bambang Wijaya, M.Pd', '', 2, 40, 'Tidak'],
                ['UNV103', 'Kewarganegaraan', 2, 2, 'A', 'Wajib', 'Universitas', 'M. Ridwan, M.H', '', 2, 40, 'Tidak'],
                ['UNV104', 'Bahasa Indonesia', 2, 2, 'A', 'Wajib', 'Universitas', 'Dra. Sri Wahyuni, M.Hum', '', 2, 40, 'Tidak'],
                ['UNV105', 'Bahasa Inggris', 2, 1, 'A', 'Wajib', 'Universitas', 'John Doe, M.A', '', 2, 40, 'Tidak'],
            ];
            $filename = 'template-mkwu-universitas.xlsx';
        } else {
            $sampleData = [
                ['FST101', 'Kalkulus I', 3, 1, 'A', 'Wajib', 'Fakultas', 'Dr. Ir. Hendra Gunawan, M.Si', '', 2, 40, 'Tidak'],
                ['FST102', 'Fisika Dasar I', 3, 1, 'A', 'Wajib', 'Fakultas', 'Prof. Dr. Ir. Suyanto, M.T', '', 2, 40, 'Tidak'],
                ['FST103', 'Praktikum Fisika Dasar I', 1, 1, 'A', 'Wajib', 'Fakultas', 'Prof. Dr. Ir. Suyanto, M.T', 'Ahmad Asisten, S.T', 2, 25, 'Ya'],
                ['FST201', 'Statistika & Probabilitas', 3, 3, 'A', 'Wajib', 'Fakultas', 'Dr. Rina Novita, M.Kom', '', 2, 40, 'Tidak'],
            ];
            $filename = 'template-mkwf-fakultas.xlsx';
        }

        $sheet->fromArray($sampleData, null, 'A2');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0860BE'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(26);

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'mk_tpl_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Preview file import Excel dengan verifikasi kolom.
     */
    public function previewImport(Request $request, MataKuliahImportService $importer)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'category_code' => ['nullable', 'in:UNV,FST'],
        ]);

        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv', 'ods', 'txt'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung. Harap gunakan file .xlsx, .xls, .csv, atau .ods.',
            ], 422);
        }

        try {
            $categoryCode = strtoupper(trim((string) $request->input('category_code', 'UNV')));
            $defaults = [
                'prodi' => $categoryCode === 'UNV' ? 'Universitas' : 'Fakultas',
                'tipe' => 'Wajib',
            ];
            $path = $request->file('file')->getRealPath();
            $data = $importer->preview($path, $defaults);

            // Filter agar Fakultas HANYA mengimpor MKWU (UNV) atau MKWF (FST)
            if (! empty($data['items'])) {
                if ($categoryCode === 'UNV') {
                    $unvItems = array_values(array_filter($data['items'], function ($it) {
                        $code = strtoupper(trim((string) ($it['kode_mk'] ?? '')));
                        $prodi = strtolower(trim((string) ($it['prodi'] ?? '')));

                        return str_starts_with($code, 'UNV') || str_contains($prodi, 'universitas');
                    }));
                    if (! empty($unvItems)) {
                        $data['items'] = $unvItems;
                    }
                } elseif ($categoryCode === 'FST') {
                    $fstItems = array_values(array_filter($data['items'], function ($it) {
                        $code = strtoupper(trim((string) ($it['kode_mk'] ?? '')));
                        $prodi = strtolower(trim((string) ($it['prodi'] ?? '')));

                        return str_starts_with($code, 'FST') || str_contains($prodi, 'fakultas');
                    }));
                    if (! empty($fstItems)) {
                        $data['items'] = $fstItems;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca spreadsheet: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simpan hasil import MKWU atau MKWF dari Excel / verified preview.
     */
    public function import(Request $request, MataKuliahImportService $importer)
    {
        $facultyId = $this->getFacultyId();

        $request->validate([
            'category_code' => 'required|in:UNV,FST',
            'file' => 'nullable|file|max:10240',
            'verified_items' => 'nullable|string',
            'redirect_to' => 'nullable|string',
        ]);

        $categoryCode = $request->category_code;
        $category = CourseCategory::where('code', $categoryCode)->firstOrFail();

        $items = [];
        if ($request->filled('verified_items')) {
            $decoded = json_decode($request->verified_items, true);
            if (is_array($decoded) && ! empty($decoded)) {
                $items = $decoded;
            }
        }

        if (empty($items) && $request->hasFile('file')) {
            $path = $request->file('file')->getRealPath();
            $defaults = [
                'prodi' => $categoryCode === 'UNV' ? 'Universitas' : 'Fakultas',
                'tipe' => 'Wajib',
            ];
            $analysis = $importer->preview($path, $defaults);
            $items = $analysis['items'] ?? [];
        }

        // Filter ketat: Fakultas HANYA mengelola MKWU (UNV) atau MKWF (FST)
        if ($categoryCode === 'UNV') {
            $unvItems = array_values(array_filter($items, function ($it) {
                $code = strtoupper(trim((string) ($it['kode_mk'] ?? '')));
                $prodi = strtolower(trim((string) ($it['prodi'] ?? '')));

                return str_starts_with($code, 'UNV') || str_contains($prodi, 'universitas');
            }));
            if (! empty($unvItems)) {
                $items = $unvItems;
            }
        } elseif ($categoryCode === 'FST') {
            $fstItems = array_values(array_filter($items, function ($it) {
                $code = strtoupper(trim((string) ($it['kode_mk'] ?? '')));
                $prodi = strtolower(trim((string) ($it['prodi'] ?? '')));

                return str_starts_with($code, 'FST') || str_contains($prodi, 'fakultas');
            }));
            if (! empty($fstItems)) {
                $items = $fstItems;
            }
        }

        if (empty($items)) {
            $redirect = $request->input('redirect_to') === 'penjadwalan'
                ? 'fakultas.penjadwalan'
                : ($categoryCode === 'UNV' ? 'fakultas.mkwu' : 'fakultas.mkwf');

            return redirect()->route($redirect)->with('flash_error', 'Tidak ada data mata kuliah yang sesuai dengan kategori ini.');
        }

        $importedCount = 0;
        foreach ($items as $item) {
            $rawKode = trim((string) ($item['kode_mk'] ?? ''));
            $rawNama = trim((string) ($item['nama_mk'] ?? ''));
            if ($rawKode === '' || $rawNama === '') {
                continue;
            }

            $codeUpper = strtoupper($rawKode);
            $sks = max(1, min(6, (int) ($item['sks'] ?? 2)));
            $semester = max(1, min(8, (int) ($item['semester'] ?? 1)));

            $isLab = ! empty($item['butuh_lab']) && in_array(strtolower((string) $item['butuh_lab']), ['1', 'ya', 'true', 'lab'], true);
            $type = ($item['type'] ?? '') === 'PRACTICUM' || $isLab ? 'PRACTICUM' : 'THEORY';

            Course::updateOrCreate(
                [
                    'faculty_id' => $facultyId,
                    'code' => $codeUpper,
                ],
                [
                    'name' => $rawNama,
                    'sks' => $sks,
                    'semester' => $semester,
                    'type' => $type,
                    'course_category_id' => $category->id,
                    'status' => 'ACTIVE',
                ]
            );

            $kategoriLabel = $categoryCode === 'UNV' ? 'MK Universitas' : 'MK Fakultas';
            $pemilikLabel = $categoryCode === 'UNV' ? 'Universitas' : 'Fakultas';

            $masterMk = MataKuliah::firstOrCreate(
                ['kode' => $codeUpper],
                [
                    'nama' => $rawNama,
                    'sks' => $sks,
                    'semester' => $semester,
                    'tipe' => 'Fakultas',
                    'kategori' => $kategoriLabel,
                    'pemilik' => $pemilikLabel,
                    'level_id' => 1,
                ]
            );

            MataKuliahDetail::updateOrCreate(
                ['kode_mk' => $codeUpper],
                [
                    'master_mk_id' => $masterMk->id,
                    'nama_mk' => $rawNama,
                    'sks' => $sks,
                    'semester' => $semester,
                    'tipe' => 'Wajib',
                    'kategori' => $kategoriLabel,
                    'prodi' => $pemilikLabel,
                    'butuh_lab' => $type === 'PRACTICUM',
                    'is_active' => true,
                    'status_tawar' => true,
                    'jumlah_kelas' => max(1, min(10, (int) ($item['jumlah_kelas'] ?? 1))),
                    'kapasitas_per_kelas' => max(10, min(200, (int) ($item['kapasitas_per_kelas'] ?? 40))),
                ]
            );

            $importedCount++;
        }

        $label = $categoryCode === 'UNV' ? 'MKWU' : 'MKWF';
        $redirectTo = $request->input('redirect_to');
        if ($redirectTo === 'penjadwalan') {
            return redirect()->route('fakultas.penjadwalan')->with('flash_success', "Berhasil mengimpor {$importedCount} data {$label}!");
        }

        $redirectRoute = $categoryCode === 'UNV' ? 'fakultas.mkwu' : 'fakultas.mkwf';

        return redirect()->route($redirectRoute)->with('flash_success', "Berhasil mengimpor {$importedCount} data {$label}!");
    }

    /**
     * Tampilan Penjadwalan Kuliah Fakultas (MKWU & MKWF).
     */
    public function penjadwalanIndex(Request $request)
    {
        $facultyId = $this->getFacultyId();
        $faculty = Fakultas::find($facultyId);

        // Ambil data ruangan aktif Fakultas
        $rooms = Room::where('faculty_id', $facultyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        if ($rooms->isEmpty()) {
            $rooms = Ruangan::where('is_active', true)->orderBy('nama')->get();
        }

        // Ambil jadwal khusus MKWU (UNV) dan MKWF (FST)
        $jadwalQuery = Jadwal::with(['rombel.mataKuliah', 'ruangan', 'dosen'])
            ->where('is_active', true)
            ->whereHas('rombel.mataKuliah', function ($q) {
                $q->whereIn('kategori', ['MK Universitas', 'MK Fakultas'])
                    ->orWhere('kode_mk', 'like', 'UNV%')
                    ->orWhere('kode_mk', 'like', 'FST%')
                    ->orWhere('prodi', 'like', '%Universitas%')
                    ->orWhere('prodi', 'like', '%Fakultas%');
            });

        if ($request->filled('hari')) {
            $jadwalQuery->where('hari', $request->hari);
        }

        if ($request->filled('ruangan_id')) {
            $jadwalQuery->where('ruangan_id', $request->ruangan_id);
        }

        if ($request->filled('kategori')) {
            if ($request->kategori === 'UNV') {
                $jadwalQuery->whereHas('rombel.mataKuliah', function ($q) {
                    $q->where('kategori', 'MK Universitas')
                        ->orWhere('kode_mk', 'like', 'UNV%')
                        ->orWhere('prodi', 'like', '%Universitas%');
                });
            } elseif ($request->kategori === 'FST') {
                $jadwalQuery->whereHas('rombel.mataKuliah', function ($q) {
                    $q->where('kategori', 'MK Fakultas')
                        ->orWhere('kode_mk', 'like', 'FST%')
                        ->orWhere('prodi', 'like', '%Fakultas%');
                });
            }
        }

        $jadwals = $jadwalQuery->orderByRaw("CASE 
            WHEN hari = 'Senin' THEN 1
            WHEN hari = 'Selasa' THEN 2
            WHEN hari = 'Rabu' THEN 3
            WHEN hari = 'Kamis' THEN 4
            WHEN hari = 'Jumat' THEN 5
            WHEN hari = 'Sabtu' THEN 6
            ELSE 7 END")
            ->orderBy('jam_mulai')
            ->get();

        // Hitung statistik
        $totalJadwal = $jadwals->count();
        $totalSks = $jadwals->sum(fn ($j) => $j->rombel->mataKuliah->sks ?? 0);
        $totalMkwu = $jadwals->filter(fn ($j) => str_starts_with($j->rombel->mataKuliah->kode_mk ?? '', 'UNV') || ($j->rombel->mataKuliah->kategori ?? '') === 'MK Universitas')->count();
        $totalMkwf = $jadwals->filter(fn ($j) => str_starts_with($j->rombel->mataKuliah->kode_mk ?? '', 'FST') || ($j->rombel->mataKuliah->kategori ?? '') === 'MK Fakultas')->count();

        // Ambil info total MK aktif Fakultas
        $mkwuCount = Course::where('faculty_id', $facultyId)->whereHas('category', fn ($q) => $q->where('code', 'UNV'))->where('status', 'ACTIVE')->count();
        $mkwfCount = Course::where('faculty_id', $facultyId)->whereHas('category', fn ($q) => $q->where('code', 'FST'))->where('status', 'ACTIVE')->count();

        return view('fakultas.penjadwalan', compact(
            'faculty',
            'rooms',
            'jadwals',
            'totalJadwal',
            'totalSks',
            'totalMkwu',
            'totalMkwf',
            'mkwuCount',
            'mkwfCount'
        ));
    }

    /**
     * Generate Jadwal Otomatis untuk MKWU dan MKWF.
     */
    public function generateJadwal(Request $request, SchedulerService $scheduler)
    {
        $request->validate([
            'semester_mode' => 'required|in:ganjil,genap,all,1,2,3,4,5,6,7,8',
            'target_mk' => 'required|in:all,UNV,FST',
            'hari' => 'nullable|array',
            'hari.*' => 'string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_mulai' => 'nullable|array',
            'jam_mulai.*' => 'string',
        ]);

        $mode = $request->semester_mode;
        $semesters = match ($mode) {
            'ganjil' => [1, 3, 5, 7],
            'genap' => [2, 4, 6, 8],
            'all' => [1, 2, 3, 4, 5, 6, 7, 8],
            default => [(int) $mode],
        };

        $target = $request->target_mk;
        $prodiFilter = match ($target) {
            'UNV' => 'universitas',
            'FST' => 'fakultas',
            default => 'fakultas_all',
        };

        $options = [
            'hari' => $request->hari ?: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'jam_mulai' => $request->jam_mulai ?: ['07:30', '10:00', '13:00', '14:50', '15:30'],
        ];

        $totalTerjadwal = 0;
        $messages = [];

        foreach ($semesters as $sem) {
            $hasil = $scheduler->generate($sem, $prodiFilter, $options);
            $totalTerjadwal += $hasil['terjadwal'] ?? 0;
            if (! empty($hasil['pesan'])) {
                $messages[] = "Semester {$sem}: {$hasil['pesan']}";
            }
        }

        $labelTarget = match ($target) {
            'UNV' => 'MKWU (Universitas)',
            'FST' => 'MKWF (Fakultas)',
            default => 'MKWU & MKWF',
        };

        $flashMessage = "Penjadwalan {$labelTarget} berhasil di-generate! {$totalTerjadwal} kelas berhasil ditempatkan.";
        if (! empty($messages)) {
            $flashMessage .= ' Catatan: '.implode('; ', array_slice($messages, 0, 3));
        }

        return redirect()->route('fakultas.penjadwalan')->with('flash_success', $flashMessage);
    }

    /**
     * Hapus semua jadwal MK Fakultas dan MK Universitas.
     */
    public function destroyJadwalFakultas()
    {
        $idsMk = MataKuliahDetail::where(function ($q) {
            $q->whereIn('kategori', ['MK Universitas', 'MK Fakultas'])
                ->orWhere('kode_mk', 'like', 'UNV%')
                ->orWhere('kode_mk', 'like', 'FST%')
                ->orWhere('prodi', 'like', '%Universitas%')
                ->orWhere('prodi', 'like', '%Fakultas%');
        })->pluck('id');

        $count = 0;
        if ($idsMk->isNotEmpty()) {
            $idsRombel = Rombel::whereIn('mata_kuliah_detail_id', $idsMk)->pluck('id');
            $count = Jadwal::whereIn('rombel_id', $idsRombel)->count();
            Jadwal::whereIn('rombel_id', $idsRombel)->delete();
            Rombel::whereIn('mata_kuliah_detail_id', $idsMk)->delete();
        }

        return redirect()->route('fakultas.penjadwalan')->with('flash_success', "Berhasil menghapus {$count} jadwal MK Fakultas & Universitas.");
    }
=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
}
