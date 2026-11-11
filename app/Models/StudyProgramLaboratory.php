<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyProgramLaboratory extends Model
{
    use HasFactory;

    protected $table = 'study_program_laboratories';

    protected $fillable = [
        'laboratory_id',
        'study_program_id',
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class, 'laboratory_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'study_program_id');
    }
}
