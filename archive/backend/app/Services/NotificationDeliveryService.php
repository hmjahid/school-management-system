<?php

namespace App\Services;

use App\Contracts\PushNotificationService;
use App\Contracts\SmsService;
use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationDeliveryService
{
    /**
     * The SMS service instance.
     *
     * @var \App\Contracts\SmsService
     */
    protected $smsService;

    /**
     * The push notification service instance.
     *
     * @var \App\Contracts\PushNotificationService
     */
    protected $pushService;

    /**
     * Create a new notification delivery service instance.
     *
     * @param  \App\Contracts\SmsService  $smsService
     * @param  \App\Contracts\PushNotificationService  $pushService
     * @return void
     */
    public function __construct(SmsService $smsService, PushNotificationService $pushService)
    {
        $this->smsService = $smsService;
        $this->pushService = $pushService;
    }

    /**
     * Send a notification to a user through their preferred channels.
     *
     * @param  \App\Models\User  $user
     * @param  string  $type
     * @param  array  $data
     * @param  array  $channels
     * @return array
     */
    public function send(User $user, string $type, array $data = [], array $channels = []): array
    {
        // Get user's notification preferences
        $preferences = $this->getUserPreferences($user, $type);
        
        // If no channels are specified, use all enabled channels from preferences
        if (empty($channels)) {
            $channels = array_keys(array_filter($preferences));
        }

        $results = [];
        
        // Process each channel
        foreach ($channels as $channel) {
            if (empty($preferences[$channel] ?? false)) {
                continue; // Skip if channel is disabled in preferences
            }

            $method = 'send' . Str::studly($channel) . 'Notification';
            
            if (method_exists($this, $method)) {
                try {
                    $result = $this->$method($user, $type, $data);
                    $results[$channel] = [
                        'success' => true,
                        'data' => $result,
                    ];
                } catch (\Exception $e) {
                    $results[$channel] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ];
                }
            }
        }

        // Log the notification delivery
        $this->logNotification($user, $type, $data, $channels, $results);

        return $results;
    }

    /**
     * Send a notification to multiple users.
     *
     * @param  \Illuminate\Support\Collection|array  $users
     * @param  string  $type
     * @param  array  $data
     * @param  array  $channels
     * @return array
     */
    public function sendToMany($users, string $type, array $data = [], array $channels = []): array
    {
        $results = [];
        
        foreach ($users as $user) {
            if ($user instanceof User) {
                $results[$user->id] = $this->send($user, $type, $data, $channels);
            }
        }

        return $results;
    }

    /**
     * Send an in-app notification.
     *
     * @param  \App\Models\User  $user
     * @param  string  $type
     * @param  array  $data
     * @return \App\Models\Notification
     */
    protected function sendDatabaseNotification(User $user, string $type, array $data = [])
    {
        $notification = $user->notifications()->create([
            'type' => $type,
            'data' => $data,
            'read_at' => null,
        ]);

        // Dispatch event for real-time updates
        event(new NotificationSent($user, $notification));

        return $notification;
    }

    /**
     * Send an email notification.
     *
     * @param  \App\Models\User  $user
     * @param  string  $type
     * @param  array  $data
     * @return void
     */
    protected function sendMailNotification(User $user, string $type, array $data = [])
    {
        $email = $user->email;
        $template = $this->getNotificationTemplate($type, 'email');
        
        if (!$template) {
            throw new \RuntimeException("Email template not found for type: {$type}");
        }

        $subject = $this->renderTemplate($template->subject, $data);
        $content = $this->renderTemplate($template->content, $data);

        Mail::to($email)->send(new \App\Mail\NotificationEmail([
            'subject' => $subject,
            'content' => $content,
            'data' => $data,
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
        ]));
    }

    /**
     * Send an SMS notification.
     *
     * @param  \App\Models\User  $user
     * @param  string  $type
     * @param  array  $data
     * @return array
     */
    protected function sendSmsNotification(User $user, string $type, array $data = []): array
    {
        $phoneNumber = $user->phone_number;
        
        if (empty($phoneNumber)) {
            throw new \RuntimeException('User does not have a phone number');
        }

        $template = $this->getNotificationTemplate($type, 'sms');
        
        if (!$template) {
            throw new \RuntimeException("SMS template not found for type: {$type}");
        }

        $message = $this->renderTemplate($template->content, $data);
        
        return $this->smsService->send($phoneNumber, $message, [
            'from' => config('sms.from'),
        ]);
    }

    /**
     * Send a push notification.
     *
     * @param  \App\Models\User  $user
     * @param  string  $type
     * @param  array  $data
     * @return array
     */
    protected function sendPushNotification(User $user, string $type, array $data = []): array
    {
        $deviceTokens = $user->deviceTokens()->pluck('token')->toArray();
        
        if (empty($deviceTokens)) {
            throw new \RuntimeException('User does not have any registered devices');
        }

        $template = $this->getNotificationTemplate($type, 'push');
        
        if (!$template) {
            throw new \RuntimeException("Push notification template not found for type: {$type}");
        }

        $title = $this->renderTemplate($template->subject, $data);
        $body = $this->renderTemplate($template->content, $data);

        return $this->pushService->sendToDevices($deviceTokens, [
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /**
     * Get the user's notification preferences for a specific type.
     *
     * @param  \App\Models\User  $user
     * @param  string  $type
     * @return array
     */
    protected function getUserPreferences(User $user, string $type): array
    {
        // Get user-specific preferences if they exist
        $preferences = $user->notificationPreferences()
            ->where('type', $type)
            ->first();

        // Fall back to default preferences if no user-specific preferences exist
        if (!$preferences) {
            $preferences = NotificationPreference::where('type', $type)
                ->whereNull('user_id')
                ->first();
        }

        // If no preferences exist at all, use default channels
        if (!$preferences) {
            return [
                'database' => true,
                'mail' => true,
                'sms' => false,
                'push' => true,
            ];
        }

        return [
            'database' => (bool) ($preferences->channels['database'] ?? true),
            'mail' => (bool) ($preferences->channels['mail'] ?? true),
            'sms' => (bool) ($preferences->channels['sms'] ?? false),
            'push' => (bool) ($preferences->channels['push'] ?? true),
        ];
    }

    /**
     * Get a notification template.
     *
     * @param  string  $type
     * @param  string  $channel
     * @return \App\Models\NotificationTemplate|null
     */
    protected function getNotificationTemplate(string $type, string $channel)
    {
        return \App\Models\NotificationTemplate::where('type', $type)
            ->where('channel', $channel)
            ->first();
    }

    /**
     * Render a template with the given data.
     *
     * @param  string  $template
     * @param  array  $data
     * @return string
     */
    protected function renderTemplate(string $template, array $data): string
    {
        $placeholders = [];
        $replacements = [];
        
        foreach ($data as $key => $value) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $placeholders[] = '{{' . $key . '}}';
                $replacements[] = (string) $value;
            }
        }
        
        return str_replace($placeholders, $replacements, $template);
    }

    /**
     * Log the notification delivery.
     *
     * @param  \App\Models\User  $user
     * @param  string  $type
     * @param  array  $data
     * @param  array  $channels
     * @param  array  $results
     * @return void
     */
    protected function logNotification(User $user, string $type, array $data, array $channels, array $results): void
    {
        try {
            NotificationLog::create([
                'user_id' => $user->id,
                'type' => $type,
                'data' => $data,
                'channels' => $channels,
                'results' => $results,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the notification
            \Illuminate\Support\Facades\Log::error('Failed to log notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'type' => $type,
            ]);
        }
    }
}
