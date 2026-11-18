<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Dosen;
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

class FakultasCourseController extends Controller
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

        $course = Course::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'sks' => $validated['sks'],
            'semester' => $validated['semester'],
            'type' => $validated['type'],
            'faculty_id' => $facultyId,
            'course_category_id' => $category->id,
            'status' => $validated['status'] ?? 'ACTIVE',
        ]);

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

        $oldCode = $course->code;
        $newCode = strtoupper(trim($validated['code']));

        $course->update([
            'code' => $newCode,
            'name' => trim($validated['name']),
            'sks' => $validated['sks'],
            'semester' => $validated['semester'],
            'type' => $validated['type'],
            'status' => $validated['status'] ?? $course->status,
        ]);

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

        $detail = MataKuliahDetail::where('kode_mk', $course->code)->first();
        if ($detail) {
            $detail->update(['is_active' => $course->status === 'ACTIVE']);
        }

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

        MataKuliahDetail::where('kode_mk', $code)->delete();

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

        $codesToDelete = Course::where('faculty_id', $facultyId)
            ->whereIn('id', $validated['selected_ids'])
            ->pluck('code');

        $deletedCount = Course::where('faculty_id', $facultyId)
            ->whereIn('id', $validated['selected_ids'])
            ->delete();

        if ($codesToDelete->isNotEmpty()) {
            MataKuliahDetail::whereIn('kode_mk', $codesToDelete)->delete();
        }

        $categoryCode = $validated['category_code'] ?? 'UNV';
        $redirectRoute = $categoryCode === 'UNV' ? 'fakultas.mkwu' : 'fakultas.mkwf';
        $label = $categoryCode === 'UNV' ? 'MKWU' : 'MKWF';

        return redirect()->route($redirectRoute)->with('flash_success', "{$deletedCount} data {$label} berhasil dihapus.");
    }

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

        $filename = $isMkwu ? 'template-mkwu-universitas.xlsx' : 'template-mkwf-fakultas.xlsx';

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

        // Ambil data ruangan aktif Fakultas (dan seluruh ruangan terdaftar)
        $rooms = Room::where('faculty_id', $facultyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        if ($rooms->isEmpty()) {
            $rooms = Ruangan::where('is_active', true)->orderBy('nama')->get();
        }

        $allRooms = Ruangan::where('is_active', true)->orderBy('nama')->get();
        $dosens = Dosen::where('is_active', true)->orderBy('nama')->get();

        // Ambil mata kuliah MKWU & MKWF yang tersedia untuk input manual
        $availableCourses = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota', 'dosenAnggota2', 'dosenAnggota3'])
            ->where(function ($q) {
                $q->whereIn('kategori', ['MK Universitas', 'MK Fakultas'])
                    ->orWhere('kode_mk', 'like', 'UNV%')
                    ->orWhere('kode_mk', 'like', 'FST%')
                    ->orWhere('prodi', 'like', '%Universitas%')
                    ->orWhere('prodi', 'like', '%Fakultas%');
            })->where('is_active', true)->orderBy('semester')->orderBy('kode_mk')->get();

        // Ambil jadwal khusus MKWU (UNV) dan MKWF (FST)
        $jadwalQuery = Jadwal::with(['rombel.mataKuliah', 'ruangan', 'dosen', 'dosenAnggota1', 'dosenAnggota2', 'dosenAnggota3'])
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

        if ($request->filled('tipe_jadwal')) {
            if ($request->tipe_jadwal === 'manual') {
                $jadwalQuery->where('is_manual', true);
            } elseif ($request->tipe_jadwal === 'otomatis') {
                $jadwalQuery->where('is_manual', false);
            }
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
        $totalManual = $jadwals->filter(fn ($j) => (bool) $j->is_manual)->count();
        $totalOtomatis = $jadwals->filter(fn ($j) => ! $j->is_manual)->count();

        // Ambil info total MK aktif Fakultas
        $mkwuCount = Course::where('faculty_id', $facultyId)->whereHas('category', fn ($q) => $q->where('code', 'UNV'))->where('status', 'ACTIVE')->count();
        $mkwfCount = Course::where('faculty_id', $facultyId)->whereHas('category', fn ($q) => $q->where('code', 'FST'))->where('status', 'ACTIVE')->count();

        return view('fakultas.penjadwalan', compact(
            'faculty',
            'rooms',
            'allRooms',
            'dosens',
            'availableCourses',
            'jadwals',
            'totalJadwal',
            'totalSks',
            'totalMkwu',
            'totalMkwf',
            'totalManual',
            'totalOtomatis',
            'mkwuCount',
            'mkwfCount'
        ));
    }

    /**
     * Helper untuk menyelesaikan ID dosen dari pilihan dropdown atau pembuatan dosen baru langsung.
     */
    private function resolveDosenId(?string $id, ?string $newName): ?int
    {
        if (! empty($newName)) {
            $nama = trim($newName);
            if ($nama !== '') {
                $d = Dosen::firstOrCreate(
                    ['nama' => $nama],
                    [
                        'inisial' => Dosen::generateInisialFromName($nama),
                        'jabatan' => 'Ketua & Anggota',
                        'prodi' => 'Fakultas',
                        'is_active' => true,
                    ]
                );

                return (int) $d->id;
            }
        }

        return ! empty($id) ? (int) $id : null;
    }

    /**
     * Simpan Input Jadwal Manual oleh Akun Fakultas (MKWU / MKWF).
     */
    public function storeManualJadwal(Request $request)
    {
        $validated = $request->validate([
            'mata_kuliah_detail_id' => 'required|exists:mata_kuliah_details,id',
            'kelas' => 'required|string|max:10',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'ruangan_id' => 'nullable|exists:ruangans,id',
            'dosen_id' => 'nullable|exists:dosens,id',
            'nama_dosen_baru' => 'nullable|string|max:150',
            'dosen_anggota_1_id' => 'nullable|exists:dosens,id',
            'nama_dosen_anggota_1_baru' => 'nullable|string|max:150',
            'dosen_anggota_2_id' => 'nullable|exists:dosens,id',
            'nama_dosen_anggota_2_baru' => 'nullable|string|max:150',
            'dosen_anggota_3_id' => 'nullable|exists:dosens,id',
            'nama_dosen_anggota_3_baru' => 'nullable|string|max:150',
            'kapasitas' => 'nullable|integer|min:1|max:300',
        ], [
            'mata_kuliah_detail_id.required' => 'Pilih mata kuliah terlebih dahulu.',
            'mata_kuliah_detail_id.exists' => 'Mata kuliah tidak valid.',
            'kelas.required' => 'Nama/huruf kelas wajib diisi.',
            'hari.required' => 'Pilih hari perkuliahan.',
            'jam_mulai.required' => 'Jam mulai perkuliahan wajib diisi.',
            'jam_selesai.required' => 'Jam selesai perkuliahan wajib diisi.',
            'jam_selesai.after' => 'Jam selesai harus lebih akhir dari jam mulai.',
            'ruangan_id.exists' => 'Ruangan tidak valid.',
            'dosen_id.exists' => 'Dosen Ketua tidak valid.',
        ]);

        $dosenKetuaId = $this->resolveDosenId($validated['dosen_id'] ?? null, $request->input('nama_dosen_baru'));
        $dosenAnggota1Id = $this->resolveDosenId($validated['dosen_anggota_1_id'] ?? null, $request->input('nama_dosen_anggota_1_baru'));
        $dosenAnggota2Id = $this->resolveDosenId($validated['dosen_anggota_2_id'] ?? null, $request->input('nama_dosen_anggota_2_baru'));
        $dosenAnggota3Id = $this->resolveDosenId($validated['dosen_anggota_3_id'] ?? null, $request->input('nama_dosen_anggota_3_baru'));

        // Proteksi Sholat Jumat (11:50 - 12:50)
        $mulaiMenit = $this->timeToMinutes($validated['jam_mulai']);
        $selesaiMenit = $this->timeToMinutes($validated['jam_selesai']);
        if ($validated['hari'] === 'Jumat' && $mulaiMenit < 770 && $selesaiMenit > 710) {
            return back()->withInput()->with('flash_error', 'Jadwal hari Jumat tidak boleh bertabrakan dengan waktu Sholat Jumat (11:50 - 12:50).');
        }

        // Cek bentrok Ruangan
        if (! empty($validated['ruangan_id'])) {
            $conflict = Jadwal::with('rombel.mataKuliah')
                ->where('ruangan_id', $validated['ruangan_id'])
                ->where('hari', $validated['hari'])
                ->where('is_active', true)
                ->where(function ($q) use ($validated) {
                    $q->where('jam_mulai', '<', $validated['jam_selesai'])
                        ->where('jam_selesai', '>', $validated['jam_mulai']);
                })
                ->first();

            if ($conflict) {
                $namaMk = $conflict->rombel?->mataKuliah?->nama_mk ?? 'mata kuliah lain';

                return back()->withInput()->with('flash_error', "Bentrok Ruangan: Ruangan sudah terpakai oleh {$namaMk} pada {$validated['hari']} ({$conflict->jam_mulai} - {$conflict->jam_selesai}).");
            }
        }

        // Cek bentrok Dosen pada seluruh tim dosen (Ketua & Anggota)
        $allDosenToCheck = array_unique(array_filter([
            $dosenKetuaId,
            $dosenAnggota1Id,
            $dosenAnggota2Id,
            $dosenAnggota3Id,
        ]));

        foreach ($allDosenToCheck as $dId) {
            $dosenConflict = Jadwal::with('rombel.mataKuliah')
                ->where(function ($q) use ($dId) {
                    $q->where('dosen_id', $dId)
                        ->orWhere('dosen_anggota_1_id', $dId)
                        ->orWhere('dosen_anggota_2_id', $dId)
                        ->orWhere('dosen_anggota_3_id', $dId);
                })
                ->where('hari', $validated['hari'])
                ->where('is_active', true)
                ->where(function ($q) use ($validated) {
                    $q->where('jam_mulai', '<', $validated['jam_selesai'])
                        ->where('jam_selesai', '>', $validated['jam_mulai']);
                })
                ->first();

            if ($dosenConflict) {
                $dosenObj = Dosen::find($dId);
                $dosenNama = $dosenObj?->nama ?? 'Dosen';
                $namaMk = $dosenConflict->rombel?->mataKuliah?->nama_mk ?? 'mata kuliah lain';

                return back()->withInput()->with('flash_error', "Bentrok Dosen: {$dosenNama} sudah mengajar {$namaMk} pada {$validated['hari']} ({$dosenConflict->jam_mulai} - {$dosenConflict->jam_selesai}).");
            }
        }

        $mk = MataKuliahDetail::findOrFail($validated['mata_kuliah_detail_id']);
        $kelas = strtoupper(trim($validated['kelas']));

        // Cek apakah kelas yang sama untuk MK ini sudah ada jadwalnya
        $existingSameClass = Jadwal::whereHas('rombel', function ($q) use ($mk, $kelas) {
            $q->where('mata_kuliah_detail_id', $mk->id)
                ->where(function ($sub) use ($kelas) {
                    $sub->where('kelas', $kelas)
                        ->orWhere('kode_rombel', 'like', "%-{$kelas}");
                });
        })->where('is_active', true)->first();

        if ($existingSameClass) {
            return back()->withInput()->with('flash_error', "Kelas {$kelas} untuk mata kuliah {$mk->kode_mk} sudah memiliki jadwal terplot!");
        }

        // Tentukan nomor rombel berikutnya yang belum terpakai
        $existingNomors = Rombel::where('mata_kuliah_detail_id', $mk->id)->pluck('nomor_rombel')->toArray();
        $nomorRombel = 1;
        while (in_array($nomorRombel, $existingNomors, true)) {
            $nomorRombel++;
        }

        $kodeRombel = $mk->kode_mk.'-'.$kelas;

        $rombel = Rombel::create([
            'mata_kuliah_detail_id' => $mk->id,
            'kode_rombel' => $kodeRombel,
            'nomor_rombel' => $nomorRombel,
            'kelas' => $kelas,
            'kapasitas' => $validated['kapasitas'] ?? $mk->kapasitas_per_kelas ?? 40,
            'ruangan_id' => $validated['ruangan_id'] ?? null,
            'hari' => $validated['hari'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'dosen_pengampu_id' => $dosenKetuaId,
            'dosen_anggota_1_id' => $dosenAnggota1Id,
            'dosen_anggota_2_id' => $dosenAnggota2Id,
            'dosen_anggota_3_id' => $dosenAnggota3Id,
            'peran_dosen' => 'Ketua',
            'is_active' => true,
            'is_manual' => true,
        ]);

        Jadwal::create([
            'rombel_id' => $rombel->id,
            'ruangan_id' => $validated['ruangan_id'] ?? null,
            'dosen_id' => $dosenKetuaId,
            'dosen_anggota_1_id' => $dosenAnggota1Id,
            'dosen_anggota_2_id' => $dosenAnggota2Id,
            'dosen_anggota_3_id' => $dosenAnggota3Id,
            'hari' => $validated['hari'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'is_active' => true,
            'is_manual' => true,
        ]);

        return redirect()->route('fakultas.penjadwalan')->with('flash_success', "Jadwal manual {$mk->kode_mk} ({$mk->nama_mk} - Kelas {$kelas}) berhasil disimpan dan akan dipertahankan saat generate otomatis!");
    }

    /**
     * Update/Edit Jadwal Kuliah Spesifik (Mengubah jadwal otomatis/manual menjadi manual tersimpan).
     */
    public function updateJadwal(Request $request, Jadwal $jadwal)
    {
        $validated = $request->validate([
            'kelas' => 'required|string|max:10',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'ruangan_id' => 'nullable|exists:ruangans,id',
            'dosen_id' => 'nullable|exists:dosens,id',
            'nama_dosen_baru' => 'nullable|string|max:150',
            'dosen_anggota_1_id' => 'nullable|exists:dosens,id',
            'nama_dosen_anggota_1_baru' => 'nullable|string|max:150',
            'dosen_anggota_2_id' => 'nullable|exists:dosens,id',
            'nama_dosen_anggota_2_baru' => 'nullable|string|max:150',
            'dosen_anggota_3_id' => 'nullable|exists:dosens,id',
            'nama_dosen_anggota_3_baru' => 'nullable|string|max:150',
            'kapasitas' => 'nullable|integer|min:1|max:300',
        ], [
            'kelas.required' => 'Nama/huruf kelas wajib diisi.',
            'hari.required' => 'Pilih hari perkuliahan.',
            'jam_mulai.required' => 'Jam mulai perkuliahan wajib diisi.',
            'jam_selesai.required' => 'Jam selesai perkuliahan wajib diisi.',
            'jam_selesai.after' => 'Jam selesai harus lebih akhir dari jam mulai.',
            'ruangan_id.exists' => 'Ruangan tidak valid.',
            'dosen_id.exists' => 'Dosen Ketua tidak valid.',
        ]);

        $dosenKetuaId = $this->resolveDosenId($validated['dosen_id'] ?? null, $request->input('nama_dosen_baru'));
        $dosenAnggota1Id = $this->resolveDosenId($validated['dosen_anggota_1_id'] ?? null, $request->input('nama_dosen_anggota_1_baru'));
        $dosenAnggota2Id = $this->resolveDosenId($validated['dosen_anggota_2_id'] ?? null, $request->input('nama_dosen_anggota_2_baru'));
        $dosenAnggota3Id = $this->resolveDosenId($validated['dosen_anggota_3_id'] ?? null, $request->input('nama_dosen_anggota_3_baru'));

        // Proteksi Sholat Jumat (11:50 - 12:50)
        $mulaiMenit = $this->timeToMinutes($validated['jam_mulai']);
        $selesaiMenit = $this->timeToMinutes($validated['jam_selesai']);
        if ($validated['hari'] === 'Jumat' && $mulaiMenit < 770 && $selesaiMenit > 710) {
            return back()->withInput()->with('flash_error', 'Jadwal hari Jumat tidak boleh bertabrakan dengan waktu Sholat Jumat (11:50 - 12:50).');
        }

        // Cek bentrok Ruangan (kecuali jadwal saat ini)
        if (! empty($validated['ruangan_id'])) {
            $conflict = Jadwal::with('rombel.mataKuliah')
                ->where('id', '!=', $jadwal->id)
                ->where('ruangan_id', $validated['ruangan_id'])
                ->where('hari', $validated['hari'])
                ->where('is_active', true)
                ->where(function ($q) use ($validated) {
                    $q->where('jam_mulai', '<', $validated['jam_selesai'])
                        ->where('jam_selesai', '>', $validated['jam_mulai']);
                })
                ->first();

            if ($conflict) {
                $namaMk = $conflict->rombel?->mataKuliah?->nama_mk ?? 'mata kuliah lain';

                return back()->withInput()->with('flash_error', "Bentrok Ruangan: Ruangan sudah terpakai oleh {$namaMk} pada {$validated['hari']} ({$conflict->jam_mulai} - {$conflict->jam_selesai}).");
            }
        }

        // Cek bentrok Dosen pada seluruh tim dosen (Ketua & Anggota) kecuali jadwal saat ini
        $allDosenToCheck = array_unique(array_filter([
            $dosenKetuaId,
            $dosenAnggota1Id,
            $dosenAnggota2Id,
            $dosenAnggota3Id,
        ]));

        foreach ($allDosenToCheck as $dId) {
            $dosenConflict = Jadwal::with(['rombel.mataKuliah', 'dosen'])
                ->where('id', '!=', $jadwal->id)
                ->where(function ($q) use ($dId) {
                    $q->where('dosen_id', $dId)
                        ->orWhere('dosen_anggota_1_id', $dId)
                        ->orWhere('dosen_anggota_2_id', $dId)
                        ->orWhere('dosen_anggota_3_id', $dId);
                })
                ->where('hari', $validated['hari'])
                ->where('is_active', true)
                ->where(function ($q) use ($validated) {
                    $q->where('jam_mulai', '<', $validated['jam_selesai'])
                        ->where('jam_selesai', '>', $validated['jam_mulai']);
                })
                ->first();

            if ($dosenConflict) {
                $dosenObj = Dosen::find($dId);
                $dosenNama = $dosenObj?->nama ?? 'Dosen';
                $namaMk = $dosenConflict->rombel?->mataKuliah?->nama_mk ?? 'mata kuliah lain';

                return back()->withInput()->with('flash_error', "Bentrok Dosen: {$dosenNama} sudah mengajar {$namaMk} pada {$validated['hari']} ({$dosenConflict->jam_mulai} - {$dosenConflict->jam_selesai}).");
            }
        }

        $kelas = strtoupper(trim($validated['kelas']));
        $rombel = $jadwal->rombel;

        if ($rombel) {
            $mk = $rombel->mataKuliah;
            $kodeRombel = ($mk?->kode_mk ?? 'MK').'-'.$kelas;

            $rombel->update([
                'kelas' => $kelas,
                'kode_rombel' => $kodeRombel,
                'kapasitas' => $validated['kapasitas'] ?? $rombel->kapasitas ?? 40,
                'ruangan_id' => $validated['ruangan_id'] ?? null,
                'hari' => $validated['hari'],
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'dosen_pengampu_id' => $dosenKetuaId,
                'dosen_anggota_1_id' => $dosenAnggota1Id,
                'dosen_anggota_2_id' => $dosenAnggota2Id,
                'dosen_anggota_3_id' => $dosenAnggota3Id,
                'peran_dosen' => 'Ketua',
                'is_manual' => true, // Ditandai manual agar tidak terhapus saat auto-generate
            ]);
        }

        $jadwal->update([
            'ruangan_id' => $validated['ruangan_id'] ?? null,
            'dosen_id' => $dosenKetuaId,
            'dosen_anggota_1_id' => $dosenAnggota1Id,
            'dosen_anggota_2_id' => $dosenAnggota2Id,
            'dosen_anggota_3_id' => $dosenAnggota3Id,
            'hari' => $validated['hari'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'is_manual' => true, // Menjadi manual agar aman dari reset saat generate otomatis!
        ]);

        $mkKode = $jadwal->rombel?->mataKuliah?->kode_mk ?? 'Mata Kuliah';

        return redirect()->route('fakultas.penjadwalan')->with('flash_success', "Jadwal {$mkKode} (Kelas {$kelas}) berhasil diperbarui dan ditandai sebagai manual (aman dari reset saat generate otomatis)!");
    }

    /**
     * Hapus satu jadwal secara spesifik (manual / otomatis).
     */
    public function destroySingleJadwal(Jadwal $jadwal)
    {
        $mkCode = $jadwal->rombel?->mataKuliah?->kode_mk ?? 'Jadwal';
        $isManual = (bool) $jadwal->is_manual;
        $rombel = $jadwal->rombel;

        $jadwal->delete();
        if ($rombel && $rombel->jadwals()->count() === 0) {
            $rombel->delete();
        }

        $tipeLabel = $isManual ? 'manual' : 'otomatis';

        return redirect()->route('fakultas.penjadwalan')->with('flash_success', "Jadwal {$tipeLabel} {$mkCode} berhasil dihapus.");
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
            $totalTerjadwal += $hasil['jumlah_jadwal'] ?? ($hasil['terjadwal'] ?? 0);
            if (! empty($hasil['catatan'])) {
                $messages[] = "Semester {$sem}: {$hasil['catatan']}";
            }
        }

        $labelTarget = match ($target) {
            'UNV' => 'MKWU (Universitas)',
            'FST' => 'MKWF (Fakultas)',
            default => 'MKWU & MKWF',
        };

        $flashMessage = "Penjadwalan {$labelTarget} berhasil di-generate! {$totalTerjadwal} kelas aktif terjadwal (termasuk jadwal manual yang dipertahankan).";
        if (! empty($messages)) {
            $flashMessage .= ' Catatan: '.implode('; ', array_slice($messages, 0, 3));
        }

        return redirect()->route('fakultas.penjadwalan')->with('flash_success', $flashMessage);
    }

    /**
     * Hapus semua jadwal MK Fakultas dan MK Universitas.
     */
    public function destroyJadwalFakultas(Request $request)
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
            $includeManual = $request->boolean('include_manual', false);

            $queryJadwal = Jadwal::whereIn('rombel_id', $idsRombel);
            if (! $includeManual) {
                $queryJadwal->where('is_manual', false);
            }
            $count = $queryJadwal->count();
            $queryJadwal->delete();

            $queryRombel = Rombel::whereIn('mata_kuliah_detail_id', $idsMk)->whereDoesntHave('jadwals');
            if (! $includeManual) {
                $queryRombel->where('is_manual', false);
            }
            $queryRombel->delete();
        }

        $info = $request->boolean('include_manual', false)
            ? "Berhasil menghapus seluruh ({$count}) jadwal MK Fakultas & Universitas."
            : "Berhasil mereset {$count} jadwal otomatis. Jadwal manual tetap dipertahankan aman.";

        return redirect()->route('fakultas.penjadwalan')->with('flash_success', $info);
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }
}
