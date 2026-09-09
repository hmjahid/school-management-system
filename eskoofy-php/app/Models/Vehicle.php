<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Vehicle extends Model
{
    protected static string $table = 'vehicles';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'number', 'type', 'capacity', 'driver_name', 'driver_phone', 'is_active',
    ];

    protected array $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function routes()
    {
        return $this->hasMany(TransportRoute::class);
    }
}
