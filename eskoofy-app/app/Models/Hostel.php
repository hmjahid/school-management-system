<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Hostel extends Model
{
    protected $fillable = [
        'name',
        'address',
        'description',
        'total_rooms',
        'warden_name',
        'warden_phone',
        'status',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class);
    }

    public function assignments(): HasManyThrough
    {
        return $this->hasManyThrough(HostelAssignment::class, HostelRoom::class, 'hostel_id', 'room_id');
    }
}
