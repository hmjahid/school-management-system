<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationStreamController extends Controller
{
    /**
     * Stream notifications to the client using Server-Sent Events (SSE).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream(Request $request)
    {
        $user = $request->user();
        
        $response = new StreamedResponse(function () use ($user) {
            $lastId = $request->header('Last-Event-ID');
            
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable buffering for Nginx
            
            // Send a comment to keep the connection alive
            $this->sendEvent('ping', null, 'ping', time());
            
            // Get any unread notifications
            if ($lastId) {
                $notifications = $user->unreadNotifications()
                    ->where('id', '>', $lastId)
                    ->orderBy('created_at', 'desc')
                    ->get();
                    
                foreach ($notifications as $notification) {
                    $this->sendNotificationEvent($notification);
                }
            }
            
            // Listen for new notifications
            $listener = function ($event) use (&$listener, $user) {
                $notification = $event->notification;
                
                // Only send if the notification is for this user
                if ($notification->notifiable_id === $user->id && 
                    $notification->notifiable_type === get_class($user)) {
                    $this->sendNotificationEvent($notification);
                }
            };
            
            // Register the event listener
            event(new \Illuminate\Broadcasting\PendingBroadcast(
                event(new \App\Events\NotificationStreamEvent($user->id))
            ));
            
            // Keep the connection open
            while (true) {
                // Send a comment every 30 seconds to keep the connection alive
                $this->sendEvent('ping', null, 'ping', time());
                
                if (connection_aborted()) {
                    // Clean up the event listener when the connection is closed
                    event(new \Illuminate\Broadcasting\PendingBroadcast(
                        event(new \App\Events\NotificationStreamClosed($user->id))
                    ));
                    break;
                }
                
                // Sleep for a while before sending the next ping
                sleep(30);
            }
        });
        
        return $response->withHeaders([
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
    
    /**
     * Send a notification event to the client.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    protected function sendNotificationEvent($notification)
    {
        $data = [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => $notification->data,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at->toIso8601String(),
            'time_ago' => $notification->created_at->diffForHumans(),
        ];
        
        $this->sendEvent('notification', $data, 'new_notification', $notification->id);
    }
    
    /**
     * Send an SSE event.
     *
     * @param  string  $event
     * @param  mixed  $data
     * @param  string|null  $id
     * @param  string|int|null  $retry
     * @return void
     */
    protected function sendEvent($event, $data, $id = null, $retry = null)
    {
        echo "event: {$event}\n";
        
        if (!is_null($id)) {
            echo "id: {$id}\n";
        }
        
        if (!is_null($retry)) {
            echo "retry: {$retry}\n";
        }
        
        echo 'data: ' . json_encode($data) . "\n\n";
        
        // Flush the output buffer
        if (ob_get_level() > 0) {
            ob_flush();
        }
        
        flush();
    }
}
