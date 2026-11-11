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
        if (Schema::hasTable('fakultas') && ! Schema::hasColumn('fakultas', 'status')) {
            Schema::table('fakultas', function (Blueprint $table) {
                $table->string('status', 20)->default('ACTIVE')->after('dekan')->index();
            });
        }

        if (Schema::hasTable('jurusan') && ! Schema::hasColumn('jurusan', 'status')) {
            Schema::table('jurusan', function (Blueprint $table) {
                $table->string('status', 20)->default('ACTIVE')->after('kajur')->index();
            });
        }

        if (Schema::hasTable('prodi') && ! Schema::hasColumn('prodi', 'status')) {
            Schema::table('prodi', function (Blueprint $table) {
                $table->string('status', 20)->default('ACTIVE')->after('kaprodi')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('prodi') && Schema::hasColumn('prodi', 'status')) {
            Schema::table('prodi', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasTable('jurusan') && Schema::hasColumn('jurusan', 'status')) {
            Schema::table('jurusan', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasTable('fakultas') && Schema::hasColumn('fakultas', 'status')) {
            Schema::table('fakultas', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
<<<<<<< HEAD
=======

>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
