<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationEmail;
use App\Events\NotificationSent;
use App\Events\NotificationRead;
use App\Events\NotificationReadAll;
use Carbon\Carbon;

class NotificationService
{
    /**
     * The notification channels.
     *
     * @var array
     */
    protected $channels = ['database', 'mail', 'sms', 'broadcast'];

    /**
     * The notification driver instances.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * The notification template instance.
     *
     * @var \App\Models\NotificationTemplate|null
     */
    protected $template;

    /**
     * The notification data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * The notification recipients.
     *
     * @var array
     */
    protected $recipients = [];

    /**
     * The notification channels to use.
     *
     * @var array
     */
    protected $notificationChannels = [];

    /**
     * The notification type.
     *
     * @var string
     */
    protected $type;

    /**
     * The notification subject.
     *
     * @var string
     */
    protected $subject;

    /**
     * The notification content.
     *
     * @var string
     */
    protected $content;

    /**
     * The notification action URL.
     *
     * @var string|null
     */
    protected $actionUrl;

    /**
     * The notification icon.
     *
     * @var string
     */
    protected $icon = 'bell';

    /**
     * The notification priority.
     *
     * @var int
     */
    protected $priority = 0;

    /**
     * The notification category.
     *
     * @var string
     */
    protected $category = 'system';

    /**
     * The notification tags.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * The notification expiration time.
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    protected $expiresAt;

    /**
     * The notification delivery delay.
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    protected $delay;

    /**
     * The notification options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new notification service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->initializeDrivers();
    }

    /**
     * Initialize the notification drivers.
     *
     * @return void
     */
    protected function initializeDrivers()
    {
        foreach ($this->channels as $channel) {
            $driverMethod = 'create' . Str::studly($channel) . 'Driver';
            if (method_exists($this, $driverMethod)) {
                $this->drivers[$channel] = $this->{$driverMethod}();
            }
        }
    }

    /**
     * Set the notification type.
     *
     * @param  string  $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set the notification subject.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject(string $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set the notification content.
     *
     * @param  string  $content
     * @return $this
     */
    public function content(string $content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Set the notification action URL.
     *
     * @param  string  $url
     * @return $this
     */
    public function actionUrl(string $url)
    {
        $this->actionUrl = $url;
        return $this;
    }

    /**
     * Set the notification icon.
     *
     * @param  string  $icon
     * @return $this
     */
    public function icon(string $icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Set the notification priority.
     *
     * @param  int  $priority
     * @return $this
     */
    public function priority(int $priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Set the notification category.
     *
     * @param  string  $category
     * @return $this
     */
    public function category(string $category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Set the notification tags.
     *
     * @param  array  $tags
     * @return $this
     */
    public function tags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Set the notification expiration time.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return $this
     */
    public function expiresAt($delay)
    {
        $this->expiresAt = $delay;
        return $this;
    }

    /**
     * Set the notification delivery delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * Set the notification options.
     *
     * @param  array  $options
     * @return $this
     */
    public function options(array $options)
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Set the notification data.
     *
     * @param  array  $data
     * @return $this
     */
    public function with(array $data)
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Set the notification template.
     *
     * @param  string  $templateKey
     * @param  array  $data
     * @return $this
     */
    public function template(string $templateKey, array $data = [])
    {
        $this->template = NotificationTemplate::getByKey($templateKey);
        
        if ($this->template) {
            $this->with($data);
            $this->type = $templateKey;
            $this->subject = $this->template->getRenderedSubject($this->data);
            $this->content = $this->template->getRenderedContent('email', $this->data);
        }
        
        return $this;
    }

    /**
     * Set the notification recipients.
     *
     * @param  mixed  $users
     * @return $this
     */
    public function to($users)
    {
        $this->recipients = $users instanceof \Illuminate\Database\Eloquent\Collection
            ? $users->all()
            : (is_array($users) ? $users : func_get_args());
            
        return $this;
    }

    /**
     * Set the notification channels.
     *
     * @param  array|string  $channels
     * @return $this
     */
    public function via($channels)
    {
        $this->notificationChannels = is_array($channels) ? $channels : func_get_args();
        return $this;
    }

    /**
     * Send the notification.
     *
     * @return array
     */
    public function send()
    {
        if (empty($this->recipients)) {
            throw new \InvalidArgumentException('No recipients specified.');
        }

        if (empty($this->type)) {
            throw new \InvalidArgumentException('Notification type is required.');
        }

        if (empty($this->subject)) {
            $this->subject = 'New Notification';
        }

        if (empty($this->content) && !$this->template) {
            throw new \InvalidArgumentException('Notification content or template is required.');
        }

        $responses = [];
        
        foreach ($this->recipients as $recipient) {
            $user = $this->getUserFromRecipient($recipient);
            
            if (!$user) {
                continue;
            }
            
            $channels = $this->getChannelsForUser($user);
            
            foreach ($channels as $channel) {
                if (!isset($this->drivers[$channel])) {
                    Log::warning("Notification channel [{$channel}] is not supported.");
                    continue;
                }
                
                try {
                    $response = $this->sendViaChannel($user, $channel);
                    $responses[] = [
                        'user_id' => $user->id,
                        'channel' => $channel,
                        'success' => true,
                        'response' => $response,
                    ];
                } catch (\Exception $e) {
                    Log::error("Failed to send notification via channel [{$channel}]: " . $e->getMessage());
                    
                    $responses[] = [
                        'user_id' => $user->id,
                        'channel' => $channel,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }
        
        return $responses;
    }

    /**
     * Send the notification via a specific channel.
     *
     * @param  \App\Models\User  $user
     * @param  string  $channel
     * @return mixed
     */
    protected function sendViaChannel($user, string $channel)
    {
        $method = 'send' . Str::studly($channel) . 'Notification';
        
        if (!method_exists($this, $method)) {
            throw new \RuntimeException("Notification channel [{$channel}] is not supported.");
        }
        
        return $this->{$method}($user);
    }

    /**
     * Send a database notification.
     *
     * @param  \App\Models\User  $user
     * @return \App\Models\DatabaseNotification
     */
    protected function sendDatabaseNotification($user)
    {
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $this->type,
            'data' => $this->getNotificationData(),
            'read_at' => null,
        ]);
        
        // Dispatch event for real-time notification
        event(new NotificationSent($user, $notification));
        
        return $notification;
    }

    /**
     * Send an email notification.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function sendMailNotification($user)
    {
        $mailable = new NotificationEmail([
            'subject' => $this->subject,
            'content' => $this->content,
            'actionUrl' => $this->actionUrl,
            'data' => $this->data,
        ]);
        
        Mail::to($user->email)->send($mailable);
        
        // Log the email notification
        $this->logNotification($user, 'mail');
    }

    /**
     * Send an SMS notification.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function sendSmsNotification($user)
    {
        // Implement SMS sending logic here
        // This is a placeholder implementation
        $smsService = app(SmsService::class);
        $smsService->send($user->phone, $this->content);
        
        // Log the SMS notification
        $this->logNotification($user, 'sms');
    }

    /**
     * Send a broadcast notification.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function sendBroadcastNotification($user)
    {
        $notification = [
            'id' => (string) Str::uuid(),
            'type' => $this->type,
            'data' => $this->getNotificationData(),
            'read_at' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
        
        // Dispatch event for real-time notification
        event(new NotificationSent($user, (object) $notification));
    }

    /**
     * Get the notification data.
     *
     * @return array
     */
    protected function getNotificationData(): array
    {
        return [
            'subject' => $this->subject,
            'content' => $this->content,
            'action_url' => $this->actionUrl,
            'icon' => $this->icon,
            'priority' => $this->priority,
            'category' => $this->category,
            'tags' => $this->tags,
            'data' => $this->data,
        ];
    }

    /**
     * Get the user from the given recipient.
     *
     * @param  mixed  $recipient
     * @return \App\Models\User|null
     */
    protected function getUserFromRecipient($recipient)
    {
        if ($recipient instanceof User) {
            return $recipient;
        }
        
        if (is_numeric($recipient)) {
            return User::find($recipient);
        }
        
        if (is_string($recipient)) {
            return User::where('email', $recipient)->first();
        }
        
        return null;
    }

    /**
     * Get the channels for the given user.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    protected function getChannelsForUser($user): array
    {
        if (!empty($this->notificationChannels)) {
            return $this->notificationChannels;
        }
        
        // Get user's notification preferences
        $preferences = NotificationPreference::getUserPreferences($user->id);
        
        $channels = [];
        
        if (isset($preferences[$this->type])) {
            foreach ($preferences[$this->type] as $channel => $enabled) {
                if ($enabled) {
                    $channels[] = $channel;
                }
            }
        }
        
        // If no channels are enabled, use the default channels
        if (empty($channels)) {
            $channels = ['database'];
        }
        
        return $channels;
    }

    /**
     * Log the notification.
     *
     * @param  \App\Models\User  $user
     * @param  string  $channel
     * @param  string  $status
     * @param  string|null  $error
     * @return \App\Models\NotificationLog
     */
    protected function logNotification($user, string $channel, string $status = 'sent', ?string $error = null)
    {
        return NotificationLog::create([
            'type' => $this->type,
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'content' => $this->content,
            'channel' => $channel,
            'status' => $status,
            'error_message' => $error,
            'sent_at' => now(),
            'metadata' => [
                'subject' => $this->subject,
                'action_url' => $this->actionUrl,
                'data' => $this->data,
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param  string  $notificationId
     * @param  int  $userId
     * @return bool
     */
    public function markAsRead(string $notificationId, int $userId): bool
    {
        $user = User::findOrFail($userId);
        $notification = $user->notifications()->findOrFail($notificationId);
        
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
            event(new NotificationRead($user->id, $notification->id));
            return true;
        }
        
        return false;
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param  int  $userId
     * @return int
     */
    public function markAllAsRead(int $userId): int
    {
        $user = User::findOrFail($userId);
        $count = $user->unreadNotifications()->update(['read_at' => now()]);
        
        if ($count > 0) {
            event(new NotificationReadAll($user->id));
        }
        
        return $count;
    }

    /**
     * Get unread notifications count for a user.
     *
     * @param  int  $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return User::findOrFail($userId)->unreadNotifications()->count();
    }

    /**
     * Get notifications for a user.
     *
     * @param  int  $userId
     * @param  int  $limit
     * @param  int  $offset
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotifications(int $userId, int $limit = 10, int $offset = 0)
    {
        return User::findOrFail($userId)
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Create a database driver instance.
     *
     * @return \App\Services\Channels\DatabaseChannel
     */
    protected function createDatabaseDriver()
    {
        return app(\App\Services\Channels\DatabaseChannel::class);
    }

    /**
     * Create a mail driver instance.
     *
     * @return \Illuminate\Mail\Mailer
     */
    protected function createMailDriver()
    {
        return app('mailer');
    }

    /**
     * Create an SMS driver instance.
     *
     * @return \App\Services\Channels\SmsChannel
     */
    protected function createSmsDriver()
    {
        return app(\App\Services\Channels\SmsChannel::class);
    }

    /**
     * Create a broadcast driver instance.
     *
     * @return \Illuminate\Broadcasting\BroadcastManager
     */
    protected function createBroadcastDriver()
    {
        return app('broadcaster');
    }
}
