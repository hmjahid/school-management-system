<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class HostelRoom extends Model
{
    protected static string $table = 'hostel_rooms';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'hostel_id', 'room_number', 'room_type', 'capacity', 'occupied', 'status',
    ];

    protected array $casts = [
        'capacity' => 'integer',
        'occupied' => 'integer',
    ];

    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    public function assignments()
    {
        return $this->hasMany(HostelAssignment::class, 'room_id');
    }
}
