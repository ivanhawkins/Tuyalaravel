<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Apartment extends Model
{
    protected $fillable = [
        'building_id',
        'number',
        'floor',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function lock(): HasOne
    {
        return $this->hasOne(Lock::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->building->name . ' - ' . $this->number;
    }
}
