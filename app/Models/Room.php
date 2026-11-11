<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
