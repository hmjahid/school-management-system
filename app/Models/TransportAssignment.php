<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportAssignment extends Model
{
    protected $fillable = ['student_id', 'route_id', 'stop_id', 'effective_from', 'effective_to'];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(TransportStop::class, 'stop_id');
    }

    public function isActive(): bool
    {
        $today = now()->toDateString();
        if ($this->effective_from->gt($today)) {
            return false;
        }
        if ($this->effective_to && $this->effective_to->lt($today)) {
            return false;
        }
        return true;
    }
}