<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Fakultas;
use App\Models\Jadwal;
use App\Models\Laboratory;
use App\Models\Room;
use App\Models\StudyProgramLaboratory;
use App\Models\StudyProgramRoom;
use Illuminate\Support\Facades\Auth;

class FakultasDashboardController extends Controller
{
    /**
     * Resolve the logged-in user's faculty_id strictly.
     */
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

    public function index()
    {
        $facultyId = $this->getFacultyId();
        $faculty = Fakultas::findOrFail($facultyId);

        // KPI / Ringkasan sesuai Prompt4 Section 11
        $stats = [
            'total_mkwu' => Course::where('faculty_id', $facultyId)->mkwu()->count(),
            'total_mkwf' => Course::where('faculty_id', $facultyId)->mkwf()->count(),
            'total_rooms' => Room::where('faculty_id', $facultyId)->count(),
            'total_laboratories' => Laboratory::where('faculty_id', $facultyId)->count(),
            'total_room_allocations' => StudyProgramRoom::whereHas('room', fn ($q) => $q->where('faculty_id', $facultyId))->count(),
            'total_lab_allocations' => StudyProgramLaboratory::whereHas('laboratory', fn ($q) => $q->where('faculty_id', $facultyId))->count(),
        ];

        // Daftar ringkas untuk preview di Dashboard
        $recentMkwu = Course::with('category')
            ->where('faculty_id', $facultyId)
            ->mkwu()
            ->orderBy('semester')
            ->orderBy('code')
            ->limit(5)
            ->get();

        $recentMkwf = Course::with('category')
            ->where('faculty_id', $facultyId)
            ->mkwf()
            ->orderBy('semester')
            ->orderBy('code')
            ->limit(5)
            ->get();

        $rooms = Room::with('studyPrograms')
            ->where('faculty_id', $facultyId)
            ->orderBy('id')
            ->get();

        $laboratories = Laboratory::with('studyPrograms')
            ->where('faculty_id', $facultyId)
            ->orderBy('id')
            ->get();

        $jadwals = Jadwal::with(['rombel.mataKuliah', 'dosen', 'ruangan'])
            ->where('is_active', true)
            ->orderByRaw("CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 ELSE 6 END")
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy('hari');

        return view('fakultas.dashboard', compact(
            'faculty',
            'stats',
            'recentMkwu',
            'recentMkwf',
            'rooms',
            'laboratories',
            'jadwals'
        ));
    }
}
