<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'notification_type',
        'email',
        'sms',
        'push',
        'in_app',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email' => 'boolean',
        'sms' => 'boolean',
        'push' => 'boolean',
        'in_app' => 'boolean',
    ];

    /**
     * Get the user that owns the notification preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the default notification preferences.
     *
     * @return array
     */
    public static function getDefaultPreferences(): array
    {
        return [
            'refund_created' => [
                'email' => true,
                'sms' => false,
                'push' => true,
                'in_app' => true,
            ],
            'refund_status_updated' => [
                'email' => true,
                'sms' => true,
                'push' => true,
                'in_app' => true,
            ],
            'refund_processed' => [
                'email' => true,
                'sms' => true,
                'push' => true,
                'in_app' => true,
            ],
            'refund_failed' => [
                'email' => true,
                'sms' => true,
                'push' => true,
                'in_app' => true,
            ],
            'payment_received' => [
                'email' => true,
                'sms' => false,
                'push' => true,
                'in_app' => true,
            ],
            'payment_failed' => [
                'email' => true,
                'sms' => true,
                'push' => true,
                'in_app' => true,
            ],
        ];
    }

    /**
     * Get the available notification types.
     *
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        return array_keys(self::getDefaultPreferences());
    }

    /**
     * Get the available notification channels.
     *
     * @return array
     */
    public static function getAvailableChannels(): array
    {
        return ['email', 'sms', 'push', 'in_app'];
    }

    /**
     * Get the default preferences for a specific notification type.
     *
     * @param  string  $type
     * @return array|null
     */
    public static function getDefaultPreferenceForType(string $type): ?array
    {
        return self::getDefaultPreferences()[$type] ?? null;
    }

    /**
     * Check if a notification type is valid.
     *
     * @param  string  $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getAvailableTypes());
    }

    /**
     * Check if a channel is valid.
     *
     * @param  string  $channel
     * @return bool
     */
    public static function isValidChannel(string $channel): bool
    {
        return in_array($channel, self::getAvailableChannels());
    }

    /**
     * Get the user's preference for a specific notification type and channel.
     *
     * @param  int  $userId
     * @param  string  $type
     * @param  string  $channel
     * @param  bool  $default
     * @return bool
     */
    public static function getUserPreference(int $userId, string $type, string $channel, bool $default = false): bool
    {
        if (!self::isValidType($type) || !self::isValidChannel($channel)) {
            return $default;
        }

        $preference = self::where('user_id', $userId)
            ->where('notification_type', $type)
            ->first();

        if (!$preference) {
            $defaults = self::getDefaultPreferenceForType($type);
            return $defaults[$channel] ?? $default;
        }

        return (bool) $preference->{$channel};
    }

    /**
     * Set the user's preference for a specific notification type and channel.
     *
     * @param  int  $userId
     * @param  string  $type
     * @param  string  $channel
     * @param  bool  $value
     * @return bool
     */
    public static function setUserPreference(int $userId, string $type, string $channel, bool $value): bool
    {
        if (!self::isValidType($type) || !self::isValidChannel($channel)) {
            return false;
        }

        $preference = self::firstOrNew([
            'user_id' => $userId,
            'notification_type' => $type,
        ]);

        $preference->{$channel} = $value;
        return $preference->save();
    }

    /**
     * Get all preferences for a user.
     *
     * @param  int  $userId
     * @return array
     */
    public static function getUserPreferences(int $userId): array
    {
        $preferences = [];
        $userPreferences = self::where('user_id', $userId)->get();
        $defaults = self::getDefaultPreferences();

        foreach ($defaults as $type => $channels) {
            $userPref = $userPreferences->where('notification_type', $type)->first();
            
            $preferences[$type] = [];
            foreach ($channels as $channel => $defaultValue) {
                $preferences[$type][$channel] = $userPref ? (bool) $userPref->{$channel} : $defaultValue;
            }
        }

        return $preferences;
    }

    /**
     * Set multiple preferences for a user at once.
     *
     * @param  int  $userId
     * @param  array  $preferences
     * @return bool
     */
    public static function setUserPreferences(int $userId, array $preferences): bool
    {
        $success = true;
        
        foreach ($preferences as $type => $channels) {
            if (!self::isValidType($type)) {
                continue;
            }
            
            $pref = self::firstOrNew([
                'user_id' => $userId,
                'notification_type' => $type,
            ]);
            
            foreach ($channels as $channel => $value) {
                if (self::isValidChannel($channel)) {
                    $pref->{$channel} = (bool) $value;
                }
            }
            
            if (!$pref->save()) {
                $success = false;
            }
        }
        
        return $success;
    }
}
