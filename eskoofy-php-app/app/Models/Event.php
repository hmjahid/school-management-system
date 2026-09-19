<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Event extends Model
{
    protected static string $table = 'events';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'created_by', 'title', 'description', 'location', 'start_date',
        'end_date', 'registration_deadline', 'max_attendees', 'is_virtual',
        'meeting_url', 'status', 'image', 'metadata',
    ];

    protected array $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'is_virtual' => 'boolean',
        'max_attendees' => 'integer',
        'metadata' => 'json',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())
            ->where('status', 'published')
            ->orderBy('start_date');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
