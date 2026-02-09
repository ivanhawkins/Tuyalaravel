<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnlockLog extends Model
{
    protected $fillable = [
        'lock_id',
        'temp_password_id',
        'unlock_method',
        'unlock_value',
        'nick_name',
        'unlocked_at',
        'raw_data',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function lock(): BelongsTo
    {
        return $this->belongsTo(Lock::class);
    }

    public function tempPassword(): BelongsTo
    {
        return $this->belongsTo(TempPassword::class);
    }

    public function scopeForLock($query, int $lockId)
    {
        return $query->where('lock_id', $lockId);
    }

    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('unlocked_at', [$startDate, $endDate]);
    }
}
