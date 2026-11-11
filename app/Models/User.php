<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'name',
        'email',
        'nuptk',
        'no_hp',
        'password',
        'role',
        'jabatan',
        'fakultas_id',
        'faculty_id',
        'jurusan_id',
        'department_id',
        'prodi_id',
        'study_program_id',
        'status',
        'must_change_password',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Accessor for name to map to nama attribute.
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['nama'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(Fakultas::class, 'fakultas_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->fakultas();
    }

    /**
     * Accessor for faculty_id to map to fakultas_id attribute.
     */
    public function getFacultyIdAttribute(): ?int
    {
        return isset($this->attributes['fakultas_id']) && $this->attributes['fakultas_id'] !== null
            ? (int) $this->attributes['fakultas_id']
            : null;
    }

    public function setFacultyIdAttribute(int|string|null $value): void
    {
        $this->attributes['fakultas_id'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    public function department(): BelongsTo
    {
        return $this->jurusan();
    }

    public function getDepartmentIdAttribute(): ?int
    {
        return isset($this->attributes['jurusan_id']) && $this->attributes['jurusan_id'] !== null
            ? (int) $this->attributes['jurusan_id']
            : null;
    }

    public function setDepartmentIdAttribute(int|string|null $value): void
    {
        $this->attributes['jurusan_id'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    /**
     * Get the prodi associated with the user.
     */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->prodi();
    }

    public function getStudyProgramIdAttribute(): ?int
    {
        return isset($this->attributes['prodi_id']) && $this->attributes['prodi_id'] !== null
            ? (int) $this->attributes['prodi_id']
            : null;
    }

    public function setStudyProgramIdAttribute(int|string|null $value): void
    {
        $this->attributes['prodi_id'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function isActive(): bool
    {
        return in_array(strtolower($this->status ?? 'aktif'), ['aktif', 'active'], true);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['aktif', 'ACTIVE', 'active']);
    }
}
