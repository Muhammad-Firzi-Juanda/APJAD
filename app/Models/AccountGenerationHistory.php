<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountGenerationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'actor_name',
        'actor_role',
        'file_name',
        'idempotency_key',
        'password_method',
        'total_processed',
        'total_created',
        'total_failed',
        'count_fakultas',
        'count_jurusan',
        'count_prodi',
        'error_summary',
        'created_accounts_summary',
    ];

    protected function casts(): array
    {
        return [
            'error_summary' => 'array',
            'created_accounts_summary' => 'array',
            'total_processed' => 'integer',
            'total_created' => 'integer',
            'total_failed' => 'integer',
            'count_fakultas' => 'integer',
            'count_jurusan' => 'integer',
            'count_prodi' => 'integer',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
<<<<<<< HEAD
=======

>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
