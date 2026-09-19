<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelRoom extends Model
{
    protected $fillable = [
        'hostel_id',
        'room_number',
        'room_type',
        'capacity',
        'occupied',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'occupied' => 'integer',
    ];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(HostelAssignment::class, 'room_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'hostel_assignments', 'room_id', 'student_id')
            ->withPivot(['check_in_date', 'check_out_date', 'status', 'notes']);
    }
}
