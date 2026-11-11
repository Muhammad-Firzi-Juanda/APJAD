<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyProgramRoom extends Model
{
    use HasFactory;

    protected $table = 'study_program_rooms';

    protected $fillable = [
        'room_id',
        'study_program_id',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'study_program_id');
    }
}
