<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('users', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        // Pastikan seluruh akun Super Admin dan Admin tidak memiliki scope organisasi
        DB::table('users')
            ->whereIn('role', [UserRole::SUPER_ADMIN, UserRole::ADMIN])
            ->update([
                'fakultas_id' => null,
                'jurusan_id' => null,
                'prodi_id' => null,
            ]);

        // Pastikan hanya ada 1 akun Admin aktif di sistem (Fix.md Bagian 2)
        $adminIds = DB::table('users')
            ->where('role', UserRole::ADMIN)
            ->orderBy('id')
            ->pluck('id');

        if ($adminIds->count() > 1) {
            $primaryAdminId = $adminIds->first();
            DB::table('users')
                ->where('role', UserRole::ADMIN)
                ->where('id', '!=', $primaryAdminId)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};

