<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $fillable = [
        'name',
        'address',
        'tuya_client_id',
        'tuya_client_secret',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $hidden = [
        'tuya_client_secret',
    ];

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function locks(): HasMany
    {
        return $this->hasMany(Lock::class);
    }

    // Helper to get locks via apartments if needed, but 'locks' should be the direct relation now?
    // Wait, the dashboard code uses `building->locks` AND `building->apartments->lock`.
    // If I change `locks` to `hasMany`, it will only return locks with `building_id`.
    // The previous code `hasManyThrough` returned locks via apartments.
    // The dashboard iteration:
    // 1. `building->locks` (Main Entrances) -> This assumes locks with `building_id`.
    // 2. `building->apartments` -> `apartment->lock` (Apartment Locks).
    // So yes, `locks()` in Building MUST be `hasMany(Lock)` to fetch Main Entrances.
    // The previous implementation `hasManyThrough` was finding apartment locks, which would duplicate them in the dashboard loop if we used it for "Main Entrances".
    // So changing to `hasMany` is correct for the new "Main Entrances" logic.
}
