<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Laboratory;
use App\Models\MataKuliah;
use App\Models\MataKuliahDetail;
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

        // 2. Pastikan master dosen resmi kampus tersedia terlebih dahulu
        foreach (Dosen::MAPPING_INISIAL as $inisial => $nama) {
            Dosen::updateOrCreate(
                ['nama' => $nama],
                [
                    'inisial' => $inisial,
                    'jabatan' => 'Ketua & Anggota',
                    'prodi' => 'Fakultas',
                    'is_active' => true,
                ]
            );
        }

        $dosenByInisial = Dosen::pluck('id', 'inisial')->toArray();

        // 3. Initial Courses (MKWU & MKWF)
        $mkwuCourses = [
            ['code' => 'UNV101', 'name' => 'Pendidikan Agama', 'sks' => 2, 'semester' => 1, 'type' => 'THEORY', 'ketua' => 'AS', 'anggota' => 'SK'],
            ['code' => 'UNV102', 'name' => 'Pancasila', 'sks' => 2, 'semester' => 1, 'type' => 'THEORY', 'ketua' => 'RBP', 'anggota' => 'BAV'],
            ['code' => 'UNV103', 'name' => 'Bahasa Indonesia', 'sks' => 2, 'semester' => 2, 'type' => 'THEORY', 'ketua' => 'PA', 'anggota' => 'BES'],
            ['code' => 'UNV104', 'name' => 'Kewarganegaraan', 'sks' => 2, 'semester' => 2, 'type' => 'THEORY', 'ketua' => 'RBP', 'anggota' => 'BAV'],
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

            $masterMk = MataKuliah::firstOrCreate(
                ['kode' => $c['code']],
                [
                    'nama' => $c['name'],
                    'sks' => $c['sks'],
                    'semester' => $c['semester'],
                    'tipe' => 'Fakultas',
                    'kategori' => 'MK Universitas',
                    'pemilik' => 'Universitas',
                    'level_id' => 1,
                ]
            );

            MataKuliahDetail::updateOrCreate(
                ['kode_mk' => $c['code']],
                [
                    'master_mk_id' => $masterMk->id,
                    'nama_mk' => $c['name'],
                    'sks' => $c['sks'],
                    'semester' => $c['semester'],
                    'tipe' => 'Wajib',
                    'kategori' => 'MK Universitas',
                    'prodi' => 'Universitas',
                    'dosen_ketua_id' => $dosenByInisial[$c['ketua']] ?? null,
                    'dosen_anggota_id' => $dosenByInisial[$c['anggota']] ?? null,
                    'dosen_anggota_2_id' => null,
                    'dosen_anggota_3_id' => null,
                    'jumlah_kelas' => 1,
                    'kapasitas_per_kelas' => 40,
                    'butuh_lab' => false,
                    'is_active' => true,
                    'status_tawar' => true,
                ]
            );
        }

        $mkwfCourses = [
            ['code' => 'FST201', 'name' => 'Kalkulus I', 'sks' => 3, 'semester' => 1, 'type' => 'THEORY', 'ketua' => 'MB', 'anggota' => 'NN'],
            ['code' => 'FST202', 'name' => 'Fisika Dasar', 'sks' => 3, 'semester' => 1, 'type' => 'THEORY', 'ketua' => 'TS', 'anggota' => 'RP'],
            ['code' => 'FST203', 'name' => 'Praktikum Fisika Dasar', 'sks' => 1, 'semester' => 1, 'type' => 'PRACTICUM', 'ketua' => 'TS', 'anggota' => 'RP'],
            ['code' => 'FST204', 'name' => 'Matematika Diskrit', 'sks' => 3, 'semester' => 2, 'type' => 'THEORY', 'ketua' => 'NR', 'anggota' => 'MRR'],
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

            $masterFst = MataKuliah::firstOrCreate(
                ['kode' => $c['code']],
                [
                    'nama' => $c['name'],
                    'sks' => $c['sks'],
                    'semester' => $c['semester'],
                    'tipe' => 'Fakultas',
                    'kategori' => 'MK Fakultas',
                    'pemilik' => 'Fakultas',
                    'level_id' => 1,
                ]
            );

            MataKuliahDetail::updateOrCreate(
                ['kode_mk' => $c['code']],
                [
                    'master_mk_id' => $masterFst->id,
                    'nama_mk' => $c['name'],
                    'sks' => $c['sks'],
                    'semester' => $c['semester'],
                    'tipe' => 'Wajib',
                    'kategori' => 'MK Fakultas',
                    'prodi' => 'Fakultas',
                    'dosen_ketua_id' => $dosenByInisial[$c['ketua']] ?? null,
                    'dosen_anggota_id' => $dosenByInisial[$c['anggota']] ?? null,
                    'dosen_anggota_2_id' => null,
                    'dosen_anggota_3_id' => null,
                    'jumlah_kelas' => 1,
                    'kapasitas_per_kelas' => 40,
                    'butuh_lab' => $c['type'] === 'PRACTICUM',
                    'is_active' => true,
                    'status_tawar' => true,
                ]
            );
        }

        // 4. Tetapkan dosen default (1 Ketua, 1 Anggota) ke semua MK Universitas yang belum memiliki dosen
        $allUnv = MataKuliahDetail::where(function ($q) {
            $q->whereIn('kategori', ['MK Universitas', 'MK Fakultas'])
                ->orWhere('prodi', 'Universitas')
                ->orWhere('prodi', 'Fakultas')
                ->orWhere('kode_mk', 'like', 'UNV%')
                ->orWhere('kode_mk', 'like', 'FST%');
        })->get();

        foreach ($allUnv as $mk) {
            if ($mk->dosen_ketua_id && $mk->dosen_anggota_id) {
                continue;
            }

            $namaUpper = strtoupper($mk->nama_mk);
            $kodeUpper = strtoupper($mk->kode_mk);

            $ketuaInisial = 'AS';
            $anggotaInisial = 'SK';

            if (str_contains($namaUpper, 'AGAMA') || str_contains($kodeUpper, '12001') || str_contains($kodeUpper, '12101')) {
                $ketuaInisial = 'AS';
                $anggotaInisial = 'SK';
            } elseif (str_contains($namaUpper, 'WARGA') || str_contains($namaUpper, 'PANCASILA') || str_contains($kodeUpper, '12103')) {
                $ketuaInisial = 'RBP';
                $anggotaInisial = 'BAV';
            } elseif (str_contains($namaUpper, 'MARITIM') || str_contains($kodeUpper, '12106')) {
                $ketuaInisial = 'MZ';
                $anggotaInisial = 'RF';
            } elseif (str_contains($namaUpper, 'MELAYU') || str_contains($namaUpper, 'TAMADUN') || str_contains($namaUpper, 'BAHASA') || str_contains($kodeUpper, '12107')) {
                $ketuaInisial = 'PA';
                $anggotaInisial = 'BES';
            } elseif (str_contains($namaUpper, 'KUKERTA') || str_contains($namaUpper, 'KKN') || str_contains($kodeUpper, '12008') || str_contains($kodeUpper, '1200-A')) {
                $ketuaInisial = 'DB';
                $anggotaInisial = 'SR';
            } elseif (str_contains($namaUpper, 'SKRIPSI') || str_contains($namaUpper, 'TUGAS AKHIR') || str_contains($kodeUpper, '12009')) {
                $ketuaInisial = 'MB';
                $anggotaInisial = 'NN';
            } elseif (str_contains($namaUpper, 'KALKULUS')) {
                $ketuaInisial = 'MB';
                $anggotaInisial = 'NN';
            } elseif (str_contains($namaUpper, 'FISIKA')) {
                $ketuaInisial = 'TS';
                $anggotaInisial = 'RP';
            } elseif (str_contains($namaUpper, 'DISKRIT')) {
                $ketuaInisial = 'NR';
                $anggotaInisial = 'MRR';
            }

            $mk->update([
                'dosen_ketua_id' => $mk->dosen_ketua_id ?: ($dosenByInisial[$ketuaInisial] ?? null),
                'dosen_anggota_id' => $mk->dosen_anggota_id ?: ($dosenByInisial[$anggotaInisial] ?? null),
            ]);
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
