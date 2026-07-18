<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'content',
        'channel',
        'status',
        'error_message',
        'sent_at',
        'delivered_at',
        'opened_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The possible statuses for a notification log.
     *
     * @var array
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_OPENED = 'opened';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_COMPLAINED = 'complained';

    /**
     * Get all the available statuses.
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SENT => 'Sent',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_OPENED => 'Opened',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_BOUNCED => 'Bounced',
            self::STATUS_COMPLAINED => 'Complained',
        ];
    }

    /**
     * Get the notifiable entity that the notification belongs to.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include pending notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include sent notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope a query to only include delivered notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    /**
     * Scope a query to only include failed notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope a query to only include opened notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpened($query)
    {
        return $query->where('status', self::STATUS_OPENED);
    }

    /**
     * Scope a query to only include notifications for a specific channel.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $channel
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope a query to only include notifications of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark the notification as sent.
     *
     * @param  array  $metadata
     * @return bool
     */
    public function markAsSent(array $metadata = []): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => $this->freshTimestamp(),
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);
    }

    /**
     * Mark the notification as delivered.
     *
     * @param  array  $metadata
     * @return bool
     */
    public function markAsDelivered(array $metadata = []): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => $this->freshTimestamp(),
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);
    }

    /**
     * Mark the notification as failed.
     *
     * @param  string  $errorMessage
     * @param  array  $metadata
     * @return bool
     */
    public function markAsFailed(string $errorMessage, array $metadata = []): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);
    }

    /**
     * Mark the notification as opened.
     *
     * @param  array  $metadata
     * @return bool
     */
    public function markAsOpened(array $metadata = []): bool
    {
        return $this->update([
            'status' => self::STATUS_OPENED,
            'opened_at' => $this->freshTimestamp(),
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);
    }

    /**
     * Get the delivery time in seconds.
     *
     * @return float|null
     */
    public function getDeliveryTime(): ?float
    {
        if (!$this->sent_at || !$this->delivered_at) {
            return null;
        }

        return $this->sent_at->diffInSeconds($this->delivered_at, true);
    }

    /**
     * Get the open rate in percentage.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $type
     * @param  string|null  $channel
     * @return float
     */
    public static function getOpenRate($query = null, ?string $type = null, ?string $channel = null): float
    {
        $query = $query ?: self::query();
        
        if ($type) {
            $query->ofType($type);
        }
        
        if ($channel) {
            $query->forChannel($channel);
        }
        
        $total = $query->count();
        $opened = (clone $query)->opened()->count();
        
        return $total > 0 ? round(($opened / $total) * 100, 2) : 0;
    }

    /**
     * Get the delivery rate in percentage.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $type
     * @param  string|null  $channel
     * @return float
     */
    public static function getDeliveryRate($query = null, ?string $type = null, ?string $channel = null): float
    {
        $query = $query ?: self::query();
        
        if ($type) {
            $query->ofType($type);
        }
        
        if ($channel) {
            $query->forChannel($channel);
        }
        
        $total = $query->count();
        $delivered = (clone $query)->whereIn('status', [self::STATUS_DELIVERED, self::STATUS_OPENED])->count();
        
        return $total > 0 ? round(($delivered / $total) * 100, 2) : 0;
    }

    /**
     * Get the failure rate in percentage.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $type
     * @param  string|null  $channel
     * @return float
     */
    public static function getFailureRate($query = null, ?string $type = null, ?string $channel = null): float
    {
        $query = $query ?: self::query();
        
        if ($type) {
            $query->ofType($type);
        }
        
        if ($channel) {
            $query->forChannel($channel);
        }
        
        $total = $query->count();
        $failed = (clone $query)->where('status', self::STATUS_FAILED)->count();
        
        return $total > 0 ? round(($failed / $total) * 100, 2) : 0;
    }

    /**
     * Get the average delivery time in seconds.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $type
     * @param  string|null  $channel
     * @return float
     */
    public static function getAverageDeliveryTime($query = null, ?string $type = null, ?string $channel = null): float
    {
        $query = $query ?: self::query()
            ->whereNotNull('sent_at')
            ->whereNotNull('delivered_at');
        
        if ($type) {
            $query->ofType($type);
        }
        
        if ($channel) {
            $query->forChannel($channel);
        }
        
        $logs = $query->get();
        
        if ($logs->isEmpty()) {
            return 0;
        }
        
        $totalTime = $logs->sum(function ($log) {
            return $log->getDeliveryTime() ?? 0;
        });
        
        return round($totalTime / $logs->count(), 2);
    }

    /**
     * Get the notification statistics.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string|null  $type
     * @param  string|null  $channel
     * @return array
     */
    public static function getStats($query = null, ?string $type = null, ?string $channel = null): array
    {
        $query = $query ?: self::query();
        
        if ($type) {
            $query->ofType($type);
        }
        
        if ($channel) {
            $query->forChannel($channel);
        }
        
        $total = $query->count();
        $pending = (clone $query)->pending()->count();
        $sent = (clone $query)->sent()->count();
        $delivered = (clone $query)->delivered()->count();
        $opened = (clone $query)->opened()->count();
        $failed = (clone $query)->failed()->count();
        
        return [
            'total' => $total,
            'pending' => $pending,
            'sent' => $sent,
            'delivered' => $delivered,
            'opened' => $opened,
            'failed' => $failed,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'open_rate' => $delivered > 0 ? round(($opened / $delivered) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
            'average_delivery_time' => self::getAverageDeliveryTime($query, $type, $channel),
        ];
    }

    /**
     * Get the notification statistics grouped by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string|null  $channel
     * @return \Illuminate\Support\Collection
     */
    public static function getStatsByType($query = null, ?string $channel = null)
    {
        $query = $query ?: self::query();
        
        if ($channel) {
            $query->forChannel($channel);
        }
        
        $types = $query->select('type')->distinct()->pluck('type');
        
        return $types->mapWithKeys(function ($type) use ($query) {
            return [$type => self::getStats((clone $query)->ofType($type))];
        });
    }

    /**
     * Get the notification statistics grouped by channel.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string|null  $type
     * @return \Illuminate\Support\Collection
     */
    public static function getStatsByChannel($query = null, ?string $type = null)
    {
        $query = $query ?: self::query();
        
        if ($type) {
            $query->ofType($type);
        }
        
        $channels = $query->select('channel')->distinct()->pluck('channel');
        
        return $channels->mapWithKeys(function ($channel) use ($query) {
            return [$channel => self::getStats((clone $query)->forChannel($channel))];
        });
    }
}
