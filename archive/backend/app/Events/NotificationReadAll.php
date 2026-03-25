<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationReadAll implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user ID.
     *
     * @var int
     */
    public $userId;

    /**
     * The timestamp when notifications were marked as read.
     *
     * @var string
     */
    public $readAt;

    /**
     * Create a new event instance.
     *
     * @param  int  $userId
     * @param  string  $readAt
     * @return void
     */
    public function __construct(int $userId, string $readAt)
    {
        $this->userId = $userId;
        $this->readAt = $readAt;
        
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
        return 'notification.read.all';
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'read_at' => $this->readAt,
            'unread_count' => 0,
        ];
    }
}
