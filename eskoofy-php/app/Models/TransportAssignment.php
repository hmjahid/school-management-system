<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class TransportAssignment extends Model
{
    protected static string $table = 'transport_assignments';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'student_id', 'route_id', 'stop_id', 'effective_from', 'effective_to',
    ];

    protected array $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function stop()
    {
        return $this->belongsTo(TransportStop::class, 'stop_id');
    }
}
