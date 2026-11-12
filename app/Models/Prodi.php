<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prodi extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'prodi';

    protected $fillable = [
        'nama',
        'kode',
        'fakultas_id',
        'jurusan_id',
        'kaprodi',
        'status',
        'name',
        'code',
        'faculty_id',
        'department_id',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected static function booted(): void
    {
        static::saving(function (Prodi $prodi): void {
            if (! empty($prodi->jurusan_id)) {
                $jurusan = Jurusan::find($prodi->jurusan_id);
                if ($jurusan) {
                    $prodi->fakultas_id = $jurusan->fakultas_id;
                }
            }
        });
    }

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

    public function getDepartmentIdAttribute(): ?int
    {
        return isset($this->attributes['jurusan_id']) ? (int) $this->attributes['jurusan_id'] : null;
    }

    public function setDepartmentIdAttribute(int|string|null $value): void
    {
        $this->attributes['jurusan_id'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(Fakultas::class, 'fakultas_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->fakultas();
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    public function department(): BelongsTo
    {
        return $this->jurusan();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'prodi_id');
    }

    public function catatanEvaluasi(): HasMany
    {
        return $this->hasMany(CatatanEvaluasiJadwal::class, 'prodi_id');
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'study_program_rooms', 'study_program_id', 'room_id')
            ->withTimestamps();
    }

    public function laboratories(): BelongsToMany
    {
        return $this->belongsToMany(Laboratory::class, 'study_program_laboratories', 'study_program_id', 'laboratory_id')
            ->withTimestamps();
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
        $shortName = trim(preg_replace('/^(S1|D3|D4|S2)\s+/i', '', (string) $this->nama));

        return $this->users()->exists()
            || $this->rooms()->exists()
            || $this->laboratories()->exists()
            || MataKuliahDetail::whereIn('prodi', array_filter([$this->nama, $shortName]))->exists();
    }
}
