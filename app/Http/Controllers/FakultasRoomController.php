<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Enums\UserRole;
use App\Models\Fakultas;
=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
use App\Models\Prodi;
use App\Models\Room;
use App\Models\StudyProgramRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FakultasRoomController extends Controller
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

<<<<<<< HEAD
            return (int) (Fakultas::active()->orderBy('id')->value('id')
                ?? Fakultas::orderBy('id')->value('id')
=======
            return (int) (\App\Models\Fakultas::active()->orderBy('id')->value('id')
                ?? \App\Models\Fakultas::orderBy('id')->value('id')
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
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

        $query = Room::with('studyPrograms')->where('faculty_id', $facultyId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type') && in_array($request->type, ['Kelas', 'Labor', 'CLASSROOM', 'LABORATORY'], true)) {
            if ($request->type === 'Kelas' || $request->type === 'CLASSROOM') {
                $query->whereIn('type', ['Kelas', 'CLASSROOM']);
            } else {
                $query->whereIn('type', ['Labor', 'LABORATORY', 'Lab']);
            }
        }

        if ($request->filled('status') && in_array($request->status, ['ACTIVE', 'INACTIVE'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('min_capacity') && is_numeric($request->min_capacity)) {
            $query->where('capacity', '>=', (int) $request->min_capacity);
        }

        $rooms = $query->orderBy('id')->paginate(15)->withQueryString();

        // Data pendukung untuk Alokasi Ruangan ke Prodi (hanya Prodi ACTIVE milik Fakultas yang sama)
        $allRooms = Room::with('studyPrograms')
            ->where('faculty_id', $facultyId)
            ->orderBy('id')
            ->get();

        $prodis = Prodi::where('fakultas_id', $facultyId)
            ->where('status', Prodi::STATUS_ACTIVE)
            ->orderBy('kode')
            ->get();

        $allocations = StudyProgramRoom::with(['room', 'studyProgram'])
            ->whereHas('room', fn ($q) => $q->where('faculty_id', $facultyId))
            ->orderBy('room_id')
            ->paginate(20, ['*'], 'alokasi_page')
            ->withQueryString();

        $activeTab = $request->query('tab', 'ruangan');

        return view('fakultas.resources.rooms', compact('rooms', 'allRooms', 'prodis', 'allocations', 'activeTab'));
    }

    public function store(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $validated = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('rooms', 'code')->where(fn ($q) => $q->where('faculty_id', $facultyId)),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('rooms', 'name')->where(fn ($q) => $q->where('faculty_id', $facultyId)),
            ],
            'capacity' => 'required|integer|min:1|max:500',
            'type' => 'nullable|in:CLASSROOM,Kelas,Labor,LABORATORY',
            'status' => 'nullable|in:ACTIVE,INACTIVE',
            'study_program_id' => 'nullable|exists:prodi,id',
            'study_program_ids' => 'nullable|array',
            'study_program_ids.*' => 'integer|exists:prodi,id',
        ], [
            'code.unique' => 'Kode ruangan sudah terdaftar di fakultas ini.',
            'name.required' => 'Nama ruangan wajib diisi.',
            'name.unique' => 'Nama ruangan sudah terdaftar di fakultas ini.',
            'capacity.required' => 'Kapasitas ruangan wajib diisi.',
            'capacity.min' => 'Kapasitas minimal 1 kursi.',
        ]);

        $type = $validated['type'] ?? 'CLASSROOM';
        $code = ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : null;

        if (! $code) {
            $prefix = in_array($type, ['Labor', 'LABORATORY'], true) ? 'LAB-' : 'R-';
            $count = Room::where('faculty_id', $facultyId)->count() + 1;
            $code = $prefix.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
            while (Room::where('faculty_id', $facultyId)->where('code', $code)->exists()) {
                $count++;
                $code = $prefix.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
            }
        }

        $room = Room::create([
            'faculty_id' => $facultyId,
            'code' => $code,
            'name' => trim($validated['name']),
            'capacity' => $validated['capacity'],
            'type' => $type,
            'status' => $validated['status'] ?? 'ACTIVE',
        ]);

        if (($room->status === 'ACTIVE') && (! empty($validated['study_program_ids']) || ! empty($validated['study_program_id']))) {
            $requestedIds = ! empty($validated['study_program_ids'])
                ? $validated['study_program_ids']
                : [$validated['study_program_id']];

            $validProdiIds = Prodi::where('fakultas_id', $facultyId)
                ->where('status', Prodi::STATUS_ACTIVE)
                ->whereIn('id', $requestedIds)
                ->pluck('id')
                ->all();

            if (! empty($validProdiIds)) {
                $room->studyPrograms()->syncWithoutDetaching($validProdiIds);
            }
        }

        return redirect()->route('fakultas.rooms.index')->with('flash_success', "Data {$room->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Room $room)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $room->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah ruangan ini.');
        }

        $validated = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('rooms', 'code')
                    ->where(fn ($q) => $q->where('faculty_id', $facultyId))
                    ->ignore($room->id),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('rooms', 'name')
                    ->where(fn ($q) => $q->where('faculty_id', $facultyId))
                    ->ignore($room->id),
            ],
            'capacity' => 'required|integer|min:1|max:500',
            'type' => 'nullable|in:CLASSROOM,Kelas,Labor,LABORATORY',
            'status' => 'nullable|in:ACTIVE,INACTIVE',
            'study_program_id' => 'nullable|exists:prodi,id',
            'study_program_ids' => 'nullable|array',
            'study_program_ids.*' => 'integer|exists:prodi,id',
        ], [
            'code.unique' => 'Kode ruangan sudah terdaftar di fakultas ini.',
            'name.required' => 'Nama ruangan wajib diisi.',
            'name.unique' => 'Nama ruangan sudah terdaftar di fakultas ini.',
            'capacity.required' => 'Kapasitas ruangan wajib diisi.',
            'capacity.min' => 'Kapasitas minimal 1 kursi.',
        ]);

        $room->update([
            'code' => ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : $room->code,
            'name' => trim($validated['name']),
            'capacity' => $validated['capacity'],
            'type' => $validated['type'] ?? $room->type,
            'status' => $validated['status'] ?? $room->status,
        ]);

        // Hanya ubah alokasi Prodi jika field alokasi benar-benar dikirim secara eksplisit
        if ($request->has('study_program_ids')) {
            $validProdiIds = Prodi::where('fakultas_id', $facultyId)
                ->where('status', Prodi::STATUS_ACTIVE)
                ->whereIn('id', $validated['study_program_ids'] ?? [])
                ->pluck('id')
                ->all();
            $room->studyPrograms()->sync($validProdiIds);
        } elseif ($request->filled('study_program_id')) {
            $prodi = Prodi::where('id', $validated['study_program_id'])
                ->where('fakultas_id', $facultyId)
                ->where('status', Prodi::STATUS_ACTIVE)
                ->first();
            if ($prodi) {
                $room->studyPrograms()->syncWithoutDetaching([$prodi->id]);
            }
        }

        return redirect()->route('fakultas.rooms.index')->with('flash_success', "Data {$room->name} (Kapasitas: {$room->capacity}) berhasil diperbarui.");
    }

    /**
     * Update cepat Alokasi Prodi dari baris tabel Ruangan.
     */
    public function assignProdi(Request $request, Room $room)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $room->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses pada ruangan ini.');
        }

        $validated = $request->validate([
            'study_program_id' => 'nullable|exists:prodi,id',
        ]);

        if (! empty($validated['study_program_id'])) {
            $prodi = Prodi::where('id', $validated['study_program_id'])
                ->where('fakultas_id', $facultyId)
                ->where('status', Prodi::STATUS_ACTIVE)
                ->firstOrFail();
            $room->studyPrograms()->syncWithoutDetaching([$prodi->id]);
            $msg = "Alokasi {$room->name} ditambahkan ke Prodi {$prodi->nama}.";
        } else {
            $room->studyPrograms()->detach();
            $msg = "Alokasi prodi untuk {$room->name} berhasil dikosongkan.";
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok', 'message' => $msg]);
        }

        return redirect()->route('fakultas.rooms.index')->with('flash_success', $msg);
    }

    public function toggleStatus(Room $room)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $room->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses pada ruangan ini.');
        }

        $room->status = $room->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $room->save();

        return redirect()->route('fakultas.rooms.index')->with('flash_success', "Status {$room->name} diubah menjadi {$room->status}.");
    }

    public function destroy(Room $room)
    {
        $facultyId = $this->getFacultyId();

        if ((int) $room->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus ruangan ini.');
        }

        if ($room->studyPrograms()->exists()) {
            return redirect()
                ->route('fakultas.rooms.index')
                ->with('flash_error', "Ruangan {$room->name} tidak dapat dihapus karena masih memiliki alokasi aktif ke Program Studi. Silakan hapus alokasi ruangan terlebih dahulu.");
        }

        $name = $room->name;
        $room->delete();

        return redirect()->route('fakultas.rooms.index')->with('flash_success', "Data {$name} berhasil dihapus.");
    }

    /**
     * Bulk Delete Ruangan yang dipilih (Select All / Checkbox).
     */
    public function bulkDestroy(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $validated = $request->validate([
            'selected_ids' => 'required|array|min:1',
            'selected_ids.*' => 'integer|exists:rooms,id',
        ]);

        $deletedCount = Room::where('faculty_id', $facultyId)
            ->whereIn('id', $validated['selected_ids'])
            ->delete();

        return redirect()->route('fakultas.rooms.index')->with('flash_success', "{$deletedCount} data ruangan berhasil dihapus.");
    }
}
