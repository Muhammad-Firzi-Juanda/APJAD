<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Fakultas;
use App\Models\Laboratory;
use App\Models\Prodi;
use App\Models\Room;
use App\Models\StudyProgramLaboratory;
use App\Models\StudyProgramRoom;
use Illuminate\Database\Seeder;

class FakultasModuleSeeder extends Seeder
{
    /**
     * Run the database seeds for Fakultas module (FTTK).
     */
    public function run(): void
    {
        $fttk = Fakultas::where('kode', 'FTTK')->first()
            ?? Fakultas::firstOrCreate(
                ['kode' => 'FTTK'],
                [
                    'nama' => 'Fakultas Teknik dan Teknologi Kemaritiman',
                    'status' => Fakultas::STATUS_ACTIVE,
                ]
            );

        $prodiTI = Prodi::where('kode', 'TI')->first();
        $prodiTE = Prodi::where('kode', 'TE')->first();

        // 1. Course Categories (MKWU / UNV & MKWF / FST)
        $catUNV = CourseCategory::updateOrCreate(
            ['code' => 'UNV'],
            [
                'name' => 'Mata Kuliah Wajib Universitas',
                'scope' => 'UNIVERSITY',
                'faculty_id' => null,
            ]
        );

        $catFST = CourseCategory::updateOrCreate(
            ['code' => 'FST'],
            [
                'name' => 'Mata Kuliah Wajib Fakultas',
                'scope' => 'FACULTY',
                'faculty_id' => $fttk->id,
            ]
        );

        // 2. Initial Courses (MKWU & MKWF)
        $mkwuCourses = [
            ['code' => 'UNV101', 'name' => 'Pendidikan Agama', 'sks' => 2, 'semester' => 1, 'type' => 'THEORY'],
            ['code' => 'UNV102', 'name' => 'Pancasila', 'sks' => 2, 'semester' => 1, 'type' => 'THEORY'],
            ['code' => 'UNV103', 'name' => 'Bahasa Indonesia', 'sks' => 2, 'semester' => 2, 'type' => 'THEORY'],
            ['code' => 'UNV104', 'name' => 'Kewarganegaraan', 'sks' => 2, 'semester' => 2, 'type' => 'THEORY'],
        ];

        foreach ($mkwuCourses as $c) {
            Course::updateOrCreate(
                [
                    'faculty_id' => $fttk->id,
                    'course_category_id' => $catUNV->id,
                    'code' => $c['code'],
                ],
                [
                    'name' => $c['name'],
                    'sks' => $c['sks'],
                    'semester' => $c['semester'],
                    'type' => $c['type'],
                    'status' => 'ACTIVE',
                ]
            );
        }

        $mkwfCourses = [
            ['code' => 'FST201', 'name' => 'Kalkulus I', 'sks' => 3, 'semester' => 1, 'type' => 'THEORY'],
            ['code' => 'FST202', 'name' => 'Fisika Dasar', 'sks' => 3, 'semester' => 1, 'type' => 'THEORY'],
            ['code' => 'FST203', 'name' => 'Praktikum Fisika Dasar', 'sks' => 1, 'semester' => 1, 'type' => 'PRACTICUM'],
            ['code' => 'FST204', 'name' => 'Matematika Diskrit', 'sks' => 3, 'semester' => 2, 'type' => 'THEORY'],
        ];

        foreach ($mkwfCourses as $c) {
            Course::updateOrCreate(
                [
                    'faculty_id' => $fttk->id,
                    'course_category_id' => $catFST->id,
                    'code' => $c['code'],
                ],
                [
                    'name' => $c['name'],
                    'sks' => $c['sks'],
                    'semester' => $c['semester'],
                    'type' => $c['type'],
                    'status' => 'ACTIVE',
                ]
            );
        }

        // 3. Rooms (11 Ruang FTTK - Prompt4 Section 5)
        $roomsData = [
            ['code' => 'R-01', 'name' => 'Room 1', 'capacity' => 35],
            ['code' => 'R-02', 'name' => 'Room 2', 'capacity' => 42],
            ['code' => 'R-03', 'name' => 'Room 3', 'capacity' => 42],
            ['code' => 'R-04', 'name' => 'Room 4', 'capacity' => 60],
            ['code' => 'R-05', 'name' => 'Room 5', 'capacity' => 42],
            ['code' => 'R-06', 'name' => 'Room 6', 'capacity' => 60],
            ['code' => 'R-07', 'name' => 'Room 7', 'capacity' => 50],
            ['code' => 'R-08', 'name' => 'Room 8', 'capacity' => 42],
            ['code' => 'R-09', 'name' => 'Room 9', 'capacity' => 42],
            ['code' => 'R-10', 'name' => 'Room 10', 'capacity' => 42],
            ['code' => 'R-11', 'name' => 'Room 11', 'capacity' => 42],
        ];

        $createdRooms = [];
        foreach ($roomsData as $r) {
            $createdRooms[$r['name']] = Room::updateOrCreate(
                [
                    'faculty_id' => $fttk->id,
                    'name' => $r['name'],
                ],
                [
                    'code' => $r['code'],
                    'capacity' => $r['capacity'],
                    'type' => 'CLASSROOM',
                    'status' => 'ACTIVE',
                ]
            );
        }

        // 4. Laboratories (Lab TI, Lab Simulasi, Lab Energi, Lab Elektro - Prompt4 Section 6)
        $labsData = [
            ['name' => 'Lab TI', 'capacity' => 35, 'lab_type' => 'Komputer'],
            ['name' => 'Lab Simulasi', 'capacity' => 35, 'lab_type' => 'Simulasi & Jaringan'],
            ['name' => 'Lab Energi', 'capacity' => 30, 'lab_type' => 'Energi & Sistem Tenaga'],
            ['name' => 'Lab Elektro', 'capacity' => 30, 'lab_type' => 'Elektronika & Kendali'],
        ];

        $createdLabs = [];
        foreach ($labsData as $l) {
            $createdLabs[$l['name']] = Laboratory::updateOrCreate(
                [
                    'faculty_id' => $fttk->id,
                    'name' => $l['name'],
                ],
                [
                    'capacity' => $l['capacity'],
                    'lab_type' => $l['lab_type'],
                    'status' => 'ACTIVE',
                ]
            );
        }

        // 5. Initial Allocations (Junctions - Prompt4 Section 7 & 8)
        if ($prodiTI && $prodiTE) {
            // Room 4 -> TI & TE
            if (isset($createdRooms['Room 4'])) {
                StudyProgramRoom::firstOrCreate(['room_id' => $createdRooms['Room 4']->id, 'study_program_id' => $prodiTI->id]);
                StudyProgramRoom::firstOrCreate(['room_id' => $createdRooms['Room 4']->id, 'study_program_id' => $prodiTE->id]);
            }
            // Room 5 -> TI
            if (isset($createdRooms['Room 5'])) {
                StudyProgramRoom::firstOrCreate(['room_id' => $createdRooms['Room 5']->id, 'study_program_id' => $prodiTI->id]);
            }
            // Room 6 -> TI
            if (isset($createdRooms['Room 6'])) {
                StudyProgramRoom::firstOrCreate(['room_id' => $createdRooms['Room 6']->id, 'study_program_id' => $prodiTI->id]);
            }

            // Lab TI -> TI
            if (isset($createdLabs['Lab TI'])) {
                StudyProgramLaboratory::firstOrCreate(['laboratory_id' => $createdLabs['Lab TI']->id, 'study_program_id' => $prodiTI->id]);
            }
            // Lab Simulasi -> TI
            if (isset($createdLabs['Lab Simulasi'])) {
                StudyProgramLaboratory::firstOrCreate(['laboratory_id' => $createdLabs['Lab Simulasi']->id, 'study_program_id' => $prodiTI->id]);
            }
            // Lab Energi -> TE
            if (isset($createdLabs['Lab Energi'])) {
                StudyProgramLaboratory::firstOrCreate(['laboratory_id' => $createdLabs['Lab Energi']->id, 'study_program_id' => $prodiTE->id]);
            }
            // Lab Elektro -> TE
            if (isset($createdLabs['Lab Elektro'])) {
                StudyProgramLaboratory::firstOrCreate(['laboratory_id' => $createdLabs['Lab Elektro']->id, 'study_program_id' => $prodiTE->id]);
            }
        }
    }
}
