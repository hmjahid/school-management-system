<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class TransportRoute extends Model
{
    protected static string $table = 'transport_routes';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'code', 'fare', 'vehicle_id', 'is_active',
    ];

    protected array $casts = [
        'fare' => 'float',
        'is_active' => 'boolean',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function stops()
    {
        return $this->hasMany(TransportStop::class, 'route_id');
    }

    public function assignments()
    {
        return $this->hasMany(TransportAssignment::class, 'route_id');
    }
}
