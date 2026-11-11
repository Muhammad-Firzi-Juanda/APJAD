<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mata_kuliah_details', function (Blueprint $table) {
            // MK seperti Skripsi/KKN yang tidak memerlukan ruang kelas
            $table->boolean('tanpa_ruangan')->default(false)->after('butuh_lab');
        });
    }

    public function down(): void
    {
        Schema::table('mata_kuliah_details', function (Blueprint $table) {
            $table->dropColumn('tanpa_ruangan');
        });
    }
};
