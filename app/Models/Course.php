<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Course extends Model
{
    use HasFactory;

    protected $table = 'courses';

    protected $fillable = [
        'code',
        'name',
        'sks',
        'semester',
        'type', // THEORY, PRACTICUM
        'faculty_id',
        'course_category_id',
        'status', // ACTIVE, INACTIVE
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Fakultas::class, 'faculty_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeMkwu($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('code', 'UNV');
        });
    }

    public function scopeMkwf($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('code', 'FST');
        });
    }
}
