<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user ID.
     *
     * @var int
     */
    public $userId;

    /**
     * The notification ID.
     *
     * @var string
     */
    public $notificationId;

    /**
     * Create a new event instance.
     *
     * @param  int  $userId
     * @param  string  $notificationId
     * @return void
     */
    public function __construct(int $userId, string $notificationId)
    {
        $this->userId = $userId;
        $this->notificationId = $notificationId;
        
        $this->dontBroadcastToCurrentUser();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\PrivateChannel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.User.' . $this->userId);
    }
    
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'notification.read';
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'notification_id' => $this->notificationId,
            'read_at' => now()->toIso8601String(),
        ];
    }
}
