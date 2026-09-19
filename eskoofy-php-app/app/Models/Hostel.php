<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Hostel extends Model
{
    protected static string $table = 'hostels';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'address', 'description', 'total_rooms', 'warden_name',
        'warden_phone', 'status',
    ];

    public function rooms()
    {
        return $this->hasMany(HostelRoom::class);
    }
}
