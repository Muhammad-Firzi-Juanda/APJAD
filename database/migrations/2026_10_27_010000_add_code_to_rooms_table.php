<?php

use App\Models\Laboratory;
use App\Models\Room;
use App\Models\StudyProgramLaboratory;
use App\Models\StudyProgramRoom;
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
        if (Schema::hasTable('rooms') && ! Schema::hasColumn('rooms', 'code')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->after('faculty_id');
            });
        }

        // Berikan code default untuk room yang sudah ada
        $rooms = Room::all();
        foreach ($rooms as $room) {
            if (empty($room->code)) {
                $room->code = 'R-'.str_pad($room->id, 2, '0', STR_PAD_LEFT);
                // Sesuaikan tipe menjadi 'Kelas' jika defaultnya 'CLASSROOM'
                if ($room->type === 'CLASSROOM' || empty($room->type)) {
                    $room->type = 'Kelas';
                }
                $room->save();
            }
        }

        // Sinkronkan data laboratorium yang ada ke tabel rooms dengan tipe 'Labor'
        if (Schema::hasTable('laboratories')) {
            $labs = Laboratory::all();
            foreach ($labs as $lab) {
                $existing = Room::where('faculty_id', $lab->faculty_id)
                    ->where('name', $lab->name)
                    ->first();
                if (! $existing) {
                    $newRoom = Room::create([
                        'faculty_id' => $lab->faculty_id,
                        'code' => 'LAB-'.str_pad($lab->id, 2, '0', STR_PAD_LEFT),
                        'name' => $lab->name,
                        'capacity' => $lab->capacity,
                        'type' => 'Labor',
                        'status' => $lab->status ?? 'ACTIVE',
                    ]);

                    // Salin alokasi prodi jika ada di study_program_laboratories
                    $labAllocations = StudyProgramLaboratory::where('laboratory_id', $lab->id)->get();
                    foreach ($labAllocations as $alloc) {
                        StudyProgramRoom::firstOrCreate([
                            'room_id' => $newRoom->id,
                            'study_program_id' => $alloc->study_program_id,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'code')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
