<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurusan extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'jurusan';

    protected $fillable = ['nama', 'kode', 'fakultas_id', 'kajur', 'status', 'name', 'code', 'faculty_id'];

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

    public function getFacultyIdAttribute(): ?int
    {
        return isset($this->attributes['fakultas_id']) ? (int) $this->attributes['fakultas_id'] : null;
    }

    public function setFacultyIdAttribute(int|string|null $value): void
    {
        $this->attributes['fakultas_id'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(Fakultas::class, 'fakultas_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->fakultas();
    }

    public function prodi(): HasMany
    {
        return $this->hasMany(Prodi::class, 'jurusan_id');
    }

    public function studyPrograms(): HasMany
    {
        return $this->prodi();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'jurusan_id');
    }

    public function catatanEvaluasi(): HasMany
    {
        return $this->hasMany(CatatanEvaluasiJadwal::class, 'jurusan_id');
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
        return $this->prodi()->exists() || $this->users()->exists();
    }
}
