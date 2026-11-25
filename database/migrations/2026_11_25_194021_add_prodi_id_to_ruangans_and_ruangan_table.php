<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ruangans')) {
            Schema::table('ruangans', function (Blueprint $table) {
                if (! Schema::hasColumn('ruangans', 'prodi_id')) {
                    $table->foreignId('prodi_id')->nullable()->after('tipe')->constrained('prodi')->onDelete('set null');
                }
            });
        }

        if (Schema::hasTable('ruangan')) {
            Schema::table('ruangan', function (Blueprint $table) {
                if (! Schema::hasColumn('ruangan', 'prodi_id')) {
                    $table->foreignId('prodi_id')->nullable()->after('faculty_id')->constrained('prodi')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ruangan') && Schema::hasColumn('ruangan', 'prodi_id')) {
            Schema::table('ruangan', function (Blueprint $table) {
                $table->dropForeign(['prodi_id']);
                $table->dropColumn('prodi_id');
            });
        }

        if (Schema::hasTable('ruangans') && Schema::hasColumn('ruangans', 'prodi_id')) {
            Schema::table('ruangans', function (Blueprint $table) {
                $table->dropForeign(['prodi_id']);
                $table->dropColumn('prodi_id');
            });
        }
    }
};
