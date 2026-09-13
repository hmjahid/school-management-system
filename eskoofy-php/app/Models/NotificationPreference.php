<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class NotificationPreference extends Model
{
    protected static string $table = 'notification_preferences';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'user_id', 'notification_type', 'email', 'sms', 'push', 'in_app',
    ];

    protected array $casts = [
        'email' => 'boolean',
        'sms' => 'boolean',
        'push' => 'boolean',
        'in_app' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    public static function getAvailableTypes(): array
    {
        return array_keys(self::getDefaultPreferences());
    }

    public static function getAvailableChannels(): array
    {
        return ['email', 'sms', 'push', 'in_app'];
    }

    public static function getDefaultPreferenceForType(string $type): ?array
    {
        return self::getDefaultPreferences()[$type] ?? null;
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getAvailableTypes(), true);
    }

    public static function isValidChannel(string $channel): bool
    {
        return in_array($channel, self::getAvailableChannels(), true);
    }

    public static function getUserPreference(int $userId, string $type, string $channel, bool $default = false): bool
    {
        if (!self::isValidType($type) || !self::isValidChannel($channel)) {
            return $default;
        }

        $preference = self::where('user_id', $userId)->where('notification_type', $type)->first();

        if (!$preference) {
            $defaults = self::getDefaultPreferenceForType($type);

            return (bool) ($defaults[$channel] ?? $default);
        }

        return (bool) $preference->{$channel};
    }

    public static function setUserPreference(int $userId, string $type, string $channel, bool $value): bool
    {
        if (!self::isValidType($type) || !self::isValidChannel($channel)) {
            return false;
        }

        $preference = self::firstOrCreate(
            ['user_id' => $userId, 'notification_type' => $type]
        );

        $preference->{$channel} = $value;

        return (bool) $preference->save();
    }

    public static function getUserPreferences(int $userId): array
    {
        $preferences = [];
        $userPreferences = self::where('user_id', $userId)->get();
        $defaults = self::getDefaultPreferences();

        foreach ($defaults as $type => $channels) {
            $userPref = null;
            foreach ($userPreferences as $row) {
                if ($row->notification_type === $type) {
                    $userPref = $row;
                    break;
                }
            }

            $preferences[$type] = [];
            foreach ($channels as $channel => $defaultValue) {
                $preferences[$type][$channel] = $userPref ? (bool) $userPref->{$channel} : $defaultValue;
            }
        }

        return $preferences;
    }

    public static function setUserPreferences(int $userId, array $preferences): bool
    {
        $success = true;

        foreach ($preferences as $type => $channels) {
            if (!self::isValidType((string) $type)) {
                continue;
            }

            $pref = self::firstOrCreate(
                ['user_id' => $userId, 'notification_type' => (string) $type]
            );

            foreach ($channels as $channel => $value) {
                if (self::isValidChannel((string) $channel)) {
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
