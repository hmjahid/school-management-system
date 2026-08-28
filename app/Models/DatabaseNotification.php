<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable as BaseNotifiable;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class DatabaseNotification extends BaseDatabaseNotification
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Get the notifiable entity that the notification belongs to.
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     *
     * @return void
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * Mark the notification as unread.
     *
     * @return void
     */
    public function markAsUnread()
    {
        if (! is_null($this->read_at)) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    /**
     * Determine if a notification has been read.
     *
     * @return bool
     */
    public function read()
    {
        return $this->read_at !== null;
    }

    /**
     * Determine if a notification has not been read.
     *
     * @return bool
     */
    public function unread()
    {
        return $this->read_at === null;
    }

    /**
     * Get the notification's type.
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Get the notification's data.
     *
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Get the notification's ID.
     *
     * @return string
     */
    public function id()
    {
        return $this->getKey();
    }
}

/**
 * Add notification support to a model.
 */
trait Notifiable
{
    use BaseNotifiable {
        notify as protected baseNotify;
        notifyNow as protected baseNotifyNow;
    }

    /**
     * Get the entity's notifications.
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's read notifications.
     */
    public function readNotifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->whereNotNull('read_at')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $instance
     * @return void
     */
    public function notify($instance)
    {
        // Check user preferences before sending notification
        if (method_exists($this, 'shouldReceiveNotification') && 
            ! $this->shouldReceiveNotification($instance)) {
            return;
        }

        $this->baseNotify($instance);
    }

    /**
     * Send the given notification immediately.
     *
     * @param  mixed  $instance
     * @return void
     */
    public function notifyNow($instance, array $channels = null)
    {
        // Check user preferences before sending notification
        if (method_exists($this, 'shouldReceiveNotification') && 
            ! $this->shouldReceiveNotification($instance, $channels)) {
            return;
        }

        $this->baseNotifyNow($instance, $channels);
    }
}
