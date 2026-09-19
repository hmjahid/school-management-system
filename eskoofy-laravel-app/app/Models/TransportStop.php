<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportStop extends Model
{
    protected $table = 'transport_stops';

    protected $fillable = ['route_id', 'name', 'pickup_time', 'drop_time', 'sort'];

    protected $casts = ['pickup_time' => 'datetime:H:i', 'drop_time' => 'datetime:H:i', 'sort' => 'integer'];

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }
}
