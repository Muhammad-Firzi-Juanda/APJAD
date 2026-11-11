<?php

namespace Database\Seeders;

use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Master Data Organisasi (Fix.md Bagian 2)
        $fakultas = Fakultas::updateOrCreate(
            ['kode' => 'FTTK'],
            [
                'nama' => 'Fakultas Teknik dan Teknologi Kemaritiman',
                'status' => Fakultas::STATUS_ACTIVE,
            ]
        );

        // 1. Jurusan Teknik Elektro dan Teknik Informatika
        $jurusanTEI = Jurusan::updateOrCreate(
            ['kode' => 'TEI'],
            [
                'nama' => 'Teknik Elektro dan Teknik Informatika',
                'fakultas_id' => $fakultas->id,
                'kajur' => 'Ketua Jurusan TEI',
                'status' => Jurusan::STATUS_ACTIVE,
            ]
        );

        $prodiTI = Prodi::updateOrCreate(
            ['kode' => 'TI'],
            [
                'nama' => 'S1 Teknik Informatika',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTEI->id,
                'kaprodi' => 'Kaprodi TI',
                'status' => Prodi::STATUS_ACTIVE,
            ]
        );

        $prodiTE = Prodi::updateOrCreate(
            ['kode' => 'TE'],
            [
                'nama' => 'S1 Teknik Elektro',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTEI->id,
                'kaprodi' => 'Kaprodi TE',
                'status' => Prodi::STATUS_ACTIVE,
            ]
        );

        // 2. Jurusan Teknik Industri Maritim
        $jurusanTIM = Jurusan::updateOrCreate(
            ['kode' => 'TIM'],
            [
                'nama' => 'Teknik Industri Maritim',
                'fakultas_id' => $fakultas->id,
                'kajur' => 'Ketua Jurusan TIM',
                'status' => Jurusan::STATUS_ACTIVE,
            ]
        );

        Prodi::updateOrCreate(
            ['kode' => 'TP'],
            [
                'nama' => 'S1 Teknik Perkapalan',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTIM->id,
                'kaprodi' => 'Kaprodi TP',
                'status' => Prodi::STATUS_ACTIVE,
            ]
        );

        Prodi::updateOrCreate(
            ['kode' => 'TIND'],
            [
                'nama' => 'S1 Teknik Industri',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTIM->id,
                'kaprodi' => 'Kaprodi TIND',
                'status' => Prodi::STATUS_ACTIVE,
            ]
        );

        Prodi::updateOrCreate(
            ['kode' => 'KIM'],
            [
                'nama' => 'S1 Kimia',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTIM->id,
                'kaprodi' => 'Kaprodi KIM',
                'status' => Prodi::STATUS_ACTIVE,
            ]
        );

        // 3. Jurusan Teknik Sipil dan Perencanaan
        $jurusanTSP = Jurusan::updateOrCreate(
            ['kode' => 'TSP'],
            [
                'nama' => 'Teknik Sipil dan Perencanaan',
                'fakultas_id' => $fakultas->id,
                'kajur' => 'Ketua Jurusan TSP',
                'status' => Jurusan::STATUS_ACTIVE,
            ]
        );

        Prodi::updateOrCreate(
            ['kode' => 'TS'],
            [
                'nama' => 'S1 Teknik Sipil',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTSP->id,
                'kaprodi' => 'Kaprodi TS',
                'status' => Prodi::STATUS_ACTIVE,
            ]
        );

        Prodi::updateOrCreate(
            ['kode' => 'PWK'],
            [
                'nama' => 'S1 Perencanaan Wilayah dan Kota',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTSP->id,
                'kaprodi' => 'Kaprodi PWK',
                'status' => Prodi::STATUS_ACTIVE,
            ]
        );

        Prodi::updateOrCreate(
            ['kode' => 'ARS'],
            [
                'nama' => 'S1 Arsitektur',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTSP->id,
                'kaprodi' => 'Kaprodi ARS',
                'status' => Prodi::STATUS_ACTIVE,
            ]
        );

        $superAdminEmail = (string) config('apjad.super_admin.email', 'admin@siwalan.com');
        $superAdminName = (string) config('apjad.super_admin.name', 'Super Admin');
        $superAdminPassword = (string) config('apjad.super_admin.password', 'admin123');

        $adminEmail = (string) config('apjad.admin.email', 'admin@apjad.com');
        $adminName = (string) config('apjad.admin.name', 'Admin Akun');
        $adminPassword = (string) config('apjad.admin.password', 'admin123');

        // Hanya boleh ada 1 akun Super Admin dan 1 akun Admin (Fix.md Bagian 1)
        User::where('role', 'Super Admin')
            ->where('email', '!=', $superAdminEmail)
            ->delete();

        User::where('role', 'Admin')
            ->where('email', '!=', $adminEmail)
            ->delete();

        $users = [
            [
                'nama' => $superAdminName,
                'email' => $superAdminEmail,
                'password' => Hash::make($superAdminPassword),
                'role' => 'Super Admin',
                'fakultas_id' => null,
                'jurusan_id' => null,
                'prodi_id' => null,
                'status' => 'aktif',
            ],
            [
                'nama' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'role' => 'Admin',
                'fakultas_id' => null,
                'jurusan_id' => null,
                'prodi_id' => null,
                'status' => 'aktif',
            ],
            [
                'nama' => 'Revi Aedrian',
                'email' => 'randtian@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'Jurusan',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTEI->id,
                'prodi_id' => null,
                'status' => 'aktif',
            ],
            [
                'nama' => 'Brian Darell',
                'email' => 'bdarell@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'Fakultas',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => null,
                'prodi_id' => null,
                'status' => 'aktif',
            ],
            [
                'nama' => 'Admin Prodi TI',
                'email' => 'prodi@apjad.com',
                'password' => Hash::make('password123'),
                'role' => 'Prodi',
                'fakultas_id' => $fakultas->id,
                'jurusan_id' => $jurusanTEI->id,
                'prodi_id' => $prodiTI->id,
                'status' => 'aktif',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        // Pastikan ruangan bawaan resmi kampus (12 ruang kelas + 2 lab + 1 auditorium) selalu tersedia
        $this->call(RuanganSeeder::class);

        // Seed data modul Fakultas (MKWU, MKWF, Rooms, Labs, Allocations)
        $this->call(FakultasModuleSeeder::class);
    }
}
