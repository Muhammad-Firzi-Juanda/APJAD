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
        if (Schema::hasTable('rombels') && ! Schema::hasColumn('rombels', 'laboratory_id')) {
            Schema::table('rombels', function (Blueprint $table) {
                $table->foreignId('laboratory_id')->nullable()->after('ruangan_id')->constrained('laboratories')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rombels') && Schema::hasColumn('rombels', 'laboratory_id')) {
            Schema::table('rombels', function (Blueprint $table) {
                $table->dropForeign(['laboratory_id']);
                $table->dropColumn('laboratory_id');
            });
        }
    }
};
