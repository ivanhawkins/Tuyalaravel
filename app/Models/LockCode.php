<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LockCode extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'lock_id',
        'tuya_password_id',
        'name',
        'pin',
        'start_date',
        'end_date',
    ];

    public function lock()
    {
        return $this->belongsTo(Lock::class);
    }
}
