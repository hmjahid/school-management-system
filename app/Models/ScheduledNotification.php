<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledNotification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'channels',
        'recipients',
        'data',
        'schedule',
        'scheduled_at',
        'sent_at',
        'status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'channels' => 'array',
        'recipients' => 'array',
        'data' => 'array',
        'schedule' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * The creator of this scheduled notification.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include pending notifications that are due.
     */
    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope a query to only include active (not cancelled or deleted) notifications.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'cancelled')
                    ->whereNull('deleted_at');
    }

    /**
     * Mark the notification as processing.
     */
    public function markAsProcessing(): bool
    {
        return $this->update(['status' => 'processing']);
    }

    /**
     * Mark the notification as sent.
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark the notification as failed.
     */
    public function markAsFailed(string $error = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Cancel the scheduled notification.
     */
    public function cancel(): bool
    {
        if (in_array($this->status, ['sent', 'processing'])) {
            return false;
        }

        return $this->update(['status' => 'cancelled']);
    }

    /**
     * Get the next scheduled time for recurring notifications.
     */
    public function getNextSchedule(): ?\Carbon\Carbon
    {
        if (empty($this->schedule) || $this->schedule['type'] === 'once') {
            return null;
        }

        $now = now();
        $next = $this->scheduled_at->copy();

        while ($next <= $now) {
            switch ($this->schedule['type']) {
                case 'daily':
                    $next->addDay();
                    break;
                case 'weekly':
                    $next->addWeek();
                    break;
                case 'monthly':
                    $next->addMonth();
                    break;
                case 'custom':
                    $next->add(
                        $this->schedule['interval'] ?? 1,
                        $this->schedule['unit'] ?? 'day'
                    );
                    break;
                default:
                    return null;
            }
        }

        return $next;
    }

    /**
     * Reschedule the notification for the next occurrence.
     */
    public function rescheduleForNextOccurrence(): bool
    {
        $nextSchedule = $this->getNextSchedule();
        
        if (!$nextSchedule) {
            return false;
        }

        // Create a new scheduled notification for the next occurrence
        $newNotification = $this->replicate();
        $newNotification->scheduled_at = $nextSchedule;
        $newNotification->status = 'pending';
        $newNotification->sent_at = null;
        $newNotification->error_message = null;
        
        return $newNotification->save();
    }
}
