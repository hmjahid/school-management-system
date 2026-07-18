<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'created_by',
        'title',
        'description',
        'location',
        'start_date',
        'end_date',
        'registration_deadline',
        'max_attendees',
        'is_virtual',
        'meeting_url',
        'status',
        'image',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'is_virtual' => 'boolean',
        'max_attendees' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * The event creator.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The users who are attending the event.
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_attendees')
            ->withPivot(['status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())
            ->where('status', 'published')
            ->orderBy('start_date');
    }

    /**
     * Check if registration is open for the event.
     */
    public function isRegistrationOpen(): bool
    {
        if (!$this->registration_deadline) {
            return true;
        }

        return now()->lte($this->registration_deadline);
    }

    /**
     * Check if the event is full.
     */
    public function isFull(): bool
    {
        if (!$this->max_attendees) {
            return false;
        }

        return $this->attendees()->wherePivot('status', '!=', 'cancelled')->count() >= $this->max_attendees;
    }
}
