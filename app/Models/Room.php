<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';

    protected $fillable = [
        'faculty_id',
        'code',
        'name',
        'capacity',
        'type',
        'status', // ACTIVE, INACTIVE
    ];

    protected static function booted(): void
    {
        static::saved(function (Room $room) {
            if (Schema::hasTable('ruangan')) {
                // If it's a lab, ensure it is represented in laboratories, otherwise in ruangan
                if (in_array(strtolower(trim($room->type ?? '')), ['lab', 'labor', 'laboratory'], true)) {
                    if (Schema::hasTable('laboratories')) {
                        DB::table('laboratories')->updateOrInsert(
                            ['name' => $room->name],
                            [
                                'faculty_id' => $room->faculty_id,
                                'code' => $room->code,
                                'capacity' => $room->capacity,
                                'lab_type' => 'Praktikum',
                                'status' => $room->status ?? 'ACTIVE',
                                'updated_at' => now(),
                            ]
                        );
                    }
                } else {
                    DB::table('ruangan')->updateOrInsert(
                        ['kode' => $room->code],
                        [
                            'faculty_id' => $room->faculty_id,
                            'nama' => $room->name,
                            'tipe' => $room->type ?: 'Ruang Kelas',
                            'kapasitas' => $room->capacity,
                            'gedung' => 'Teknik',
                            'lantai' => '1',
                            'is_active' => ($room->status ?? 'ACTIVE') === 'ACTIVE',
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        });

        static::deleted(function (Room $room) {
            if (Schema::hasTable('ruangan')) {
                DB::table('ruangan')->where('kode', $room->code)->delete();
            }
        });
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Fakultas::class, 'faculty_id');
    }

    public function studyPrograms(): BelongsToMany
    {
        return $this->belongsToMany(Prodi::class, 'study_program_rooms', 'room_id', 'study_program_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }
}
