<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class TransportStop extends Model
{
    protected static string $table = 'transport_stops';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'route_id', 'name', 'pickup_time', 'drop_time', 'sort',
    ];

    protected array $casts = [
        'sort' => 'integer',
    ];

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }
}
