<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Memperbaiki data scope akun existing sesuai ketentuan Fix.md Bagian 8:
     * - SUPER ADMIN & ADMIN : fakultas_id = NULL, jurusan_id = NULL, prodi_id = NULL
     * - FAKULTAS            : fakultas_id = Wajib, jurusan_id = NULL, prodi_id = NULL
     * - JURUSAN             : fakultas_id = Wajib, jurusan_id = Wajib, prodi_id = NULL
     * - PRODI               : fakultas_id = Wajib, jurusan_id = Wajib, prodi_id = Wajib
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // 1. SUPER ADMIN & ADMIN tidak boleh memiliki scope organisasi
        DB::table('users')
            ->whereIn('role', [UserRole::SUPER_ADMIN, UserRole::ADMIN])
            ->update([
                'fakultas_id' => null,
                'jurusan_id' => null,
                'prodi_id' => null,
            ]);

        // 2. FAKULTAS hanya boleh memiliki fakultas_id (jurusan_id = NULL, prodi_id = NULL)
        $fakultasUsers = DB::table('users')
            ->where('role', UserRole::FAKULTAS)
            ->get();

        foreach ($fakultasUsers as $u) {
            $fakultasId = $u->fakultas_id;

            if (empty($fakultasId) && ! empty($u->jurusan_id)) {
                $fakultasId = DB::table('jurusan')->where('id', $u->jurusan_id)->value('fakultas_id');
            }
            if (empty($fakultasId) && ! empty($u->prodi_id)) {
                $fakultasId = DB::table('prodi')->where('id', $u->prodi_id)->value('fakultas_id');
            }
            if (empty($fakultasId)) {
                $fakultasId = DB::table('fakultas')->orderBy('id')->value('id');
            }

            DB::table('users')
                ->where('id', $u->id)
                ->update([
                    'fakultas_id' => $fakultasId,
                    'jurusan_id' => null,
                    'prodi_id' => null,
                ]);
        }

        // 3. JURUSAN hanya boleh memiliki fakultas_id dan jurusan_id (prodi_id = NULL)
        $jurusanUsers = DB::table('users')
            ->where('role', UserRole::JURUSAN)
            ->get();

        foreach ($jurusanUsers as $u) {
            $jurusanId = $u->jurusan_id;
            $fakultasId = $u->fakultas_id;

            if (empty($jurusanId) && ! empty($u->prodi_id)) {
                $jurusanId = DB::table('prodi')->where('id', $u->prodi_id)->value('jurusan_id');
            }
            if (! empty($jurusanId)) {
                $jurusanFakultasId = DB::table('jurusan')->where('id', $jurusanId)->value('fakultas_id');
                if (! empty($jurusanFakultasId)) {
                    $fakultasId = $jurusanFakultasId;
                }
            }

            DB::table('users')
                ->where('id', $u->id)
                ->update([
                    'fakultas_id' => $fakultasId,
                    'jurusan_id' => $jurusanId,
                    'prodi_id' => null,
                ]);
        }

        // 4. PRODI wajib memiliki fakultas_id, jurusan_id, dan prodi_id yang konsisten
        $prodiUsers = DB::table('users')
            ->where('role', UserRole::PRODI)
            ->get();

        foreach ($prodiUsers as $u) {
            if (! empty($u->prodi_id)) {
                $prodiRow = DB::table('prodi')->where('id', $u->prodi_id)->first();
                if ($prodiRow) {
                    DB::table('users')
                        ->where('id', $u->id)
                        ->update([
                            'fakultas_id' => $prodiRow->fakultas_id,
                            'jurusan_id' => $prodiRow->jurusan_id,
                            'prodi_id' => $prodiRow->id,
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data normalization is irreversible and safe to keep
    }
};
<<<<<<< HEAD
=======

>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
