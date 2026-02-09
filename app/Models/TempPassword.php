<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class TempPassword extends Model
{
    protected $fillable = [
        'lock_id',
        'name',
        'tuya_password_id',
        'tuya_sn',
        'pin',
        'effective_time',
        'invalid_time',
        'status',
        'external_reference',
    ];

    protected $casts = [
        'effective_time' => 'datetime',
        'invalid_time' => 'datetime',
    ];

    public function lock(): BelongsTo
    {
        return $this->belongsTo(Lock::class);
    }

    public function unlockLogs(): HasMany
    {
        return $this->hasMany(UnlockLog::class);
    }

    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->invalid_time);
    }

    public function isActive(): bool
    {
        $now = Carbon::now();
        return $now->isAfter($this->effective_time)
            && $now->isBefore($this->invalid_time)
            && in_array($this->status, ['active', 'syncing']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'deleted')
            ->where('status', '!=', 'expired')
            ->where('invalid_time', '>', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('invalid_time', '<', Carbon::now());
    }
}
