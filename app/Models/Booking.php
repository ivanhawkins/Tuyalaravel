<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Booking extends Model
{
    protected $fillable = [
        'lock_id',
        'guest_name',
        'pin',
        'tuya_password_id',
        'check_in',
        'check_out',
        'status',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function lock(): BelongsTo
    {
        return $this->belongsTo(Lock::class);
    }

    public function getFormattedCheckInAttribute(): string
    {
        return $this->check_in->format('d/m/Y H:i');
    }

    public function getFormattedCheckOutAttribute(): string
    {
        return $this->check_out->format('d/m/Y H:i');
    }

    // Status accessor helpers
    public function getIsActiveAttribute(): bool
    {
        $now = now();
        return $this->status === 'active' && $this->check_out > $now;
    }

    public function getIsFutureAttribute(): bool
    {
        return $this->status === 'active' && $this->check_in > now();
    }
}
