<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = ['number', 'type', 'capacity', 'driver_name', 'driver_phone', 'is_active'];

    protected $casts = ['capacity' => 'integer', 'is_active' => 'boolean'];

    public function routes(): HasMany
    {
        return $this->hasMany(TransportRoute::class);
    }
}