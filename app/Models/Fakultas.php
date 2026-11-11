<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fakultas extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'fakultas';

    protected $fillable = ['nama', 'kode', 'dekan', 'status', 'name', 'code'];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nama'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['kode'] ?? null;
    }

    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['kode'] = $value;
    }

    public function jurusans(): HasMany
    {
        return $this->hasMany(Jurusan::class, 'fakultas_id');
    }

    public function departments(): HasMany
    {
        return $this->jurusans();
    }

    public function prodi(): HasMany
    {
        return $this->hasMany(Prodi::class, 'fakultas_id');
    }

    public function studyPrograms(): HasMany
    {
        return $this->prodi();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'fakultas_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'faculty_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'faculty_id');
    }

    public function laboratories(): HasMany
    {
        return $this->hasMany(Laboratory::class, 'faculty_id');
    }

    public function courseCategories(): HasMany
    {
        return $this->hasMany(CourseCategory::class, 'faculty_id');
    }

    public function isActive(): bool
    {
        return strtoupper($this->status ?? self::STATUS_ACTIVE) === self::STATUS_ACTIVE;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function hasRelatedData(): bool
    {
        return $this->jurusans()->exists()
            || $this->prodi()->exists()
            || $this->users()->exists()
            || $this->courses()->exists()
            || $this->rooms()->exists()
            || $this->laboratories()->exists();
    }
}
