<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lock extends Model
{
    protected $fillable = [
        'apartment_id',
        'building_id',
        'device_id',
        'name',
        'model',
        'active',
        'last_sync',
        'status_data',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_sync' => 'datetime',
        'status_data' => 'array',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function tempPasswords(): HasMany
    {
        return $this->hasMany(TempPassword::class);
    }

    public function unlockLogs(): HasMany
    {
        return $this->hasMany(UnlockLog::class);
    }

    public function alertLogs(): HasMany
    {
        return $this->hasMany(AlertLog::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getLocationNameAttribute(): string
    {
        if ($this->apartment) {
            return "Apartamento {$this->apartment->number} (" . ($this->apartment->building->name ?? 'Unknown') . ")";
        }
        if ($this->building) {
            return "Edificio: {$this->building->name} (Puerta Principal)";
        }
        return 'Sin asignar';
    }

    public function getBatteryLevelAttribute(): ?int
    {
        return $this->status_data['battery_level'] ?? null;
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->status_data['online'] ?? false;
    }
}
