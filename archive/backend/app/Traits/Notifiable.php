<?php

namespace App\Traits;

use App\Models\User;
use App\Services\NotificationDeliveryService;
use Illuminate\Support\Collection;

/**
 * Trait for models that can send notifications.
 */
trait Notifiable
{
    /**
     * Send a notification to a user.
     *
     * @param  \App\Models\User|array|int  $users
     * @param  string  $type
     * @param  array  $data
     * @param  array  $channels
     * @return array
     */
    public function notify($users, string $type, array $data = [], array $channels = []): array
    {
        $notificationService = app(NotificationDeliveryService::class);
        $results = [];

        // Handle different types of user parameters
        if ($users instanceof User) {
            $results = $notificationService->send($users, $type, $this->prepareNotificationData($data), $channels);
        } elseif (is_array($users) || $users instanceof Collection) {
            $results = $notificationService->sendToMany($users, $type, $this->prepareNotificationData($data), $channels);
        } elseif (is_numeric($users)) {
            $user = User::find($users);
            if ($user) {
                $results = $notificationService->send($user, $type, $this->prepareNotificationData($data), $channels);
            }
        }

        return $results;
    }

    /**
     * Notify the model's owner.
     *
     * @param  string  $type
     * @param  array  $data
     * @param  array  $channels
     * @return array
     */
    public function notifyOwner(string $type, array $data = [], array $channels = []): array
    {
        if (method_exists($this, 'owner')) {
            $owner = $this->owner;
            if ($owner instanceof User) {
                return $this->notify($owner, $type, $data, $channels);
            }
        }

        return [];
    }

    /**
     * Prepare notification data with model information.
     *
     * @param  array  $data
     * @return array
     */
    protected function prepareNotificationData(array $data = []): array
    {
        // Add model information to the notification data
        $modelData = [
            'model_type' => class_basename($this),
            'model_id' => $this->getKey(),
            'model' => $this->toArray(),
        ];

        // Add URL to the model if it exists
        if (method_exists($this, 'getUrlAttribute')) {
            $modelData['url'] = $this->url;
        }

        return array_merge($modelData, $data);
    }

    /**
     * Get the notification message for a given type.
     *
     * @param  string  $type
     * @param  string  $channel
     * @param  array  $data
     * @return string|null
     */
    public function getNotificationMessage(string $type, string $channel = 'database', array $data = []): ?string
    {
        $template = \App\Models\NotificationTemplate::where('type', $type)
            ->where('channel', $channel)
            ->first();

        if (!$template) {
            return null;
        }

        return $this->renderTemplate($template->content, $data);
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
        
        // Add model attributes to the data
        $modelData = $this->toArray();
        
        foreach ($modelData as $key => $value) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $placeholders[] = '{{' . $key . '}}';
                $replacements[] = (string) $value;
            }
        }
        
        // Add custom data
        foreach ($data as $key => $value) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $placeholders[] = '{{' . $key . '}}';
                $replacements[] = (string) $value;
            }
        }
        
        return str_replace($placeholders, $replacements, $template);
    }
}
