<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertLog extends Model
{
    protected $fillable = [
        'lock_id',
        'alert_code',
        'alert_time',
        'raw_data',
        'notified',
        'notified_at',
    ];

    protected $casts = [
        'alert_time' => 'datetime',
        'notified' => 'boolean',
        'notified_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function lock(): BelongsTo
    {
        return $this->belongsTo(Lock::class);
    }

    public function scopePending($query)
    {
        return $query->where('notified', false)
            ->orderBy('alert_time', 'desc');
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('alert_code', $code);
    }

    public function markAsNotified(): void
    {
        $this->update([
            'notified' => true,
            'notified_at' => now(),
        ]);
    }
}
