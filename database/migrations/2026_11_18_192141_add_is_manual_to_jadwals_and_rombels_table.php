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
        if (Schema::hasTable('rombels') && ! Schema::hasColumn('rombels', 'is_manual')) {
            Schema::table('rombels', function (Blueprint $table) {
                $table->boolean('is_manual')->default(false)->after('is_active');
            });
        }

        if (Schema::hasTable('jadwals') && ! Schema::hasColumn('jadwals', 'is_manual')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->boolean('is_manual')->default(false)->after('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jadwals') && Schema::hasColumn('jadwals', 'is_manual')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->dropColumn('is_manual');
            });
        }

        if (Schema::hasTable('rombels') && Schema::hasColumn('rombels', 'is_manual')) {
            Schema::table('rombels', function (Blueprint $table) {
                $table->dropColumn('is_manual');
            });
        }
    }
};
