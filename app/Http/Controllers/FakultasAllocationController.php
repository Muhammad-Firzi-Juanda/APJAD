<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Fakultas;
use App\Models\Laboratory;
use App\Models\Prodi;
use App\Models\Room;
use App\Models\StudyProgramLaboratory;
use App\Models\StudyProgramRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FakultasAllocationController extends Controller
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

    // =========================================================================
    // 1. ALOKASI RUANGAN KE PRODI (study_program_rooms)
    // =========================================================================

    public function roomAllocationsIndex(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $rooms = Room::with('studyPrograms')
            ->where('faculty_id', $facultyId)
            ->orderBy('id')
            ->get();

        $activeRooms = Room::where('faculty_id', $facultyId)
            ->where('status', 'ACTIVE')
            ->orderBy('id')
            ->get();

        $prodis = Prodi::where('fakultas_id', $facultyId)
            ->where('status', Prodi::STATUS_ACTIVE)
            ->orderBy('kode')
            ->get();

        $query = StudyProgramRoom::with(['room', 'studyProgram'])
            ->whereHas('room', fn ($q) => $q->where('faculty_id', $facultyId));

        if ($request->filled('room_id')) {
            $query->where('room_id', (int) $request->room_id);
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', (int) $request->study_program_id);
        }

        $allocations = $query->orderBy('room_id')
            ->orderBy('study_program_id')
            ->paginate(20)
            ->withQueryString();

        return view('fakultas.allocations.rooms', compact('rooms', 'activeRooms', 'prodis', 'allocations'));
    }

    public function storeRoomAllocation(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'study_program_id' => 'nullable|required_without:study_program_ids|exists:prodi,id',
            'study_program_ids' => 'nullable|required_without:study_program_id|array|min:1',
            'study_program_ids.*' => 'integer|exists:prodi,id',
            'redirect_to' => 'nullable|string',
        ], [
            'room_id.required' => 'Pilih ruangan yang akan dialokasikan.',
            'study_program_id.required_without' => 'Pilih minimal satu Program Studi.',
            'study_program_ids.required_without' => 'Pilih minimal satu Program Studi.',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        if ((int) $room->faculty_id !== (int) $facultyId) {
            abort(403, 'Ruangan bukan milik Fakultas Anda.');
        }

        if ($room->status !== 'ACTIVE') {
            return redirect()->back()->withErrors([
                'room_id' => "Ruangan {$room->name} berstatus INACTIVE dan tidak dapat dialokasikan ke Program Studi.",
            ])->withInput();
        }

        $prodiIds = ! empty($validated['study_program_ids'])
            ? array_values(array_unique(array_map('intval', $validated['study_program_ids'])))
            : [(int) $validated['study_program_id']];

        $prodis = Prodi::whereIn('id', $prodiIds)->get();

        foreach ($prodis as $prodi) {
            if ((int) $prodi->fakultas_id !== (int) $facultyId) {
                abort(403, "Program Studi {$prodi->nama} tidak berada dalam Fakultas Anda.");
            }

            if ($prodi->status !== Prodi::STATUS_ACTIVE) {
                return redirect()->back()->withErrors([
                    'study_program_id' => "Program Studi {$prodi->nama} berstatus INACTIVE dan tidak dapat menerima alokasi ruangan.",
                ])->withInput();
            }
        }

        $createdNames = [];
        $duplicateNames = [];

        foreach ($prodis as $prodi) {
            $exists = StudyProgramRoom::where('room_id', $room->id)
                ->where('study_program_id', $prodi->id)
                ->exists();

            if ($exists) {
                $duplicateNames[] = $prodi->nama;
            } else {
                StudyProgramRoom::create([
                    'room_id' => $room->id,
                    'study_program_id' => $prodi->id,
                ]);
                $createdNames[] = $prodi->nama;
            }
        }

        if (empty($createdNames)) {
            $dupList = implode(', ', $duplicateNames);

            return redirect()->back()->withErrors([
                'study_program_id' => "Alokasi ruangan {$room->name} untuk Program Studi ({$dupList}) sudah ada (tidak boleh duplikat).",
            ])->withInput();
        }

        $targetRedirect = ($validated['redirect_to'] ?? '') === 'allocations_page'
            ? redirect()->route('fakultas.allocations.rooms')
            : redirect()->route('fakultas.rooms.index', ['tab' => 'alokasi']);

        $prodiLabel = implode(', ', $createdNames);

        return $targetRedirect->with('flash_success', "Ruangan {$room->name} berhasil dialokasikan ke Program Studi: {$prodiLabel}.");
    }

    public function destroyRoomAllocation(Request $request, StudyProgramRoom $allocation)
    {
        $facultyId = $this->getFacultyId();
        $allocation->load(['room', 'studyProgram']);

        if (! $allocation->room || (int) $allocation->room->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus alokasi ini.');
        }

        $roomName = $allocation->room->name;
        $prodiName = $allocation->studyProgram->nama ?? 'Prodi';
        $allocation->delete();

        $targetRedirect = $request->input('redirect_to') === 'allocations_page'
            ? redirect()->route('fakultas.allocations.rooms')
            : redirect()->route('fakultas.rooms.index', ['tab' => 'alokasi']);

        return $targetRedirect->with('flash_success', "Alokasi {$roomName} untuk {$prodiName} berhasil dihapus.");
    }

    // =========================================================================
    // 2. ALOKASI LABORATORIUM KE PRODI (study_program_laboratories)
    // =========================================================================

    public function labAllocationsIndex(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $laboratories = Laboratory::with('studyPrograms')
            ->where('faculty_id', $facultyId)
            ->orderBy('id')
            ->get();

        $activeLabs = Laboratory::where('faculty_id', $facultyId)
            ->where('status', 'ACTIVE')
            ->orderBy('id')
            ->get();

        $prodis = Prodi::where('fakultas_id', $facultyId)
            ->where('status', Prodi::STATUS_ACTIVE)
            ->orderBy('kode')
            ->get();

        $query = StudyProgramLaboratory::with(['laboratory', 'studyProgram'])
            ->whereHas('laboratory', fn ($q) => $q->where('faculty_id', $facultyId));

        if ($request->filled('laboratory_id')) {
            $query->where('laboratory_id', (int) $request->laboratory_id);
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', (int) $request->study_program_id);
        }

        $allocations = $query->orderBy('laboratory_id')
            ->orderBy('study_program_id')
            ->paginate(20)
            ->withQueryString();

        return view('fakultas.allocations.laboratories', compact('laboratories', 'activeLabs', 'prodis', 'allocations'));
    }

    public function storeLabAllocation(Request $request)
    {
        $facultyId = $this->getFacultyId();

        $validated = $request->validate([
            'laboratory_id' => 'required|exists:laboratories,id',
            'study_program_id' => 'nullable|required_without:study_program_ids|exists:prodi,id',
            'study_program_ids' => 'nullable|required_without:study_program_id|array|min:1',
            'study_program_ids.*' => 'integer|exists:prodi,id',
            'redirect_to' => 'nullable|string',
        ], [
            'laboratory_id.required' => 'Pilih laboratorium yang akan dialokasikan.',
            'study_program_id.required_without' => 'Pilih minimal satu Program Studi.',
            'study_program_ids.required_without' => 'Pilih minimal satu Program Studi.',
        ]);

        $laboratory = Laboratory::findOrFail($validated['laboratory_id']);

        if ((int) $laboratory->faculty_id !== (int) $facultyId) {
            abort(403, 'Laboratorium bukan milik Fakultas Anda.');
        }

        if ($laboratory->status !== 'ACTIVE') {
            return redirect()->back()->withErrors([
                'laboratory_id' => "Laboratorium {$laboratory->name} berstatus INACTIVE dan tidak dapat dialokasikan ke Program Studi.",
            ])->withInput();
        }

        $prodiIds = ! empty($validated['study_program_ids'])
            ? array_values(array_unique(array_map('intval', $validated['study_program_ids'])))
            : [(int) $validated['study_program_id']];

        $prodis = Prodi::whereIn('id', $prodiIds)->get();

        foreach ($prodis as $prodi) {
            if ((int) $prodi->fakultas_id !== (int) $facultyId) {
                abort(403, "Program Studi {$prodi->nama} tidak berada dalam Fakultas Anda.");
            }

            if ($prodi->status !== Prodi::STATUS_ACTIVE) {
                return redirect()->back()->withErrors([
                    'study_program_id' => "Program Studi {$prodi->nama} berstatus INACTIVE dan tidak dapat menerima alokasi laboratorium.",
                ])->withInput();
            }
        }

        $createdNames = [];
        $duplicateNames = [];

        foreach ($prodis as $prodi) {
            $exists = StudyProgramLaboratory::where('laboratory_id', $laboratory->id)
                ->where('study_program_id', $prodi->id)
                ->exists();

            if ($exists) {
                $duplicateNames[] = $prodi->nama;
            } else {
                StudyProgramLaboratory::create([
                    'laboratory_id' => $laboratory->id,
                    'study_program_id' => $prodi->id,
                ]);
                $createdNames[] = $prodi->nama;
            }
        }

        if (empty($createdNames)) {
            $dupList = implode(', ', $duplicateNames);

            return redirect()->back()->withErrors([
                'study_program_id' => "Alokasi laboratorium {$laboratory->name} untuk Program Studi ({$dupList}) sudah ada (tidak boleh duplikat).",
            ])->withInput();
        }

        $targetRedirect = ($validated['redirect_to'] ?? '') === 'allocations_page'
            ? redirect()->route('fakultas.allocations.laboratories')
            : redirect()->route('fakultas.laboratories.index', ['tab' => 'alokasi']);

        $prodiLabel = implode(', ', $createdNames);

        return $targetRedirect->with('flash_success', "Laboratorium {$laboratory->name} berhasil dialokasikan ke Program Studi: {$prodiLabel}.");
    }

    public function destroyLabAllocation(Request $request, StudyProgramLaboratory $allocation)
    {
        $facultyId = $this->getFacultyId();
        $allocation->load(['laboratory', 'studyProgram']);

        if (! $allocation->laboratory || (int) $allocation->laboratory->faculty_id !== (int) $facultyId) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus alokasi ini.');
        }

        $labName = $allocation->laboratory->name;
        $prodiName = $allocation->studyProgram->nama ?? 'Prodi';
        $allocation->delete();

        $targetRedirect = $request->input('redirect_to') === 'allocations_page'
            ? redirect()->route('fakultas.allocations.laboratories')
            : redirect()->route('fakultas.laboratories.index', ['tab' => 'alokasi']);

        return $targetRedirect->with('flash_success', "Alokasi {$labName} untuk {$prodiName} berhasil dihapus.");
    }
}
