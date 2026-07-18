<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWidgetPreference extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'widget_id',
        'enabled',
        'position',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'position' => 'integer',
        'settings' => 'array',
    ];

    /**
     * Get the user that owns the widget preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the default widget configuration.
     *
     * @return array
     */
    public static function getDefaultWidgets(): array
    {
        return [
            'quick_stats' => [
                'enabled' => true,
                'position' => 1,
                'settings' => ['show_revenue' => true, 'show_students' => true, 'show_attendance' => true]
            ],
            'revenue_chart' => [
                'enabled' => true,
                'position' => 2,
                'settings' => ['timeframe' => 'monthly', 'show_average' => true]
            ],
            'recent_activity' => [
                'enabled' => true,
                'position' => 3,
                'settings' => ['limit' => 10, 'show_timestamps' => true]
            ],
            'upcoming_events' => [
                'enabled' => true,
                'position' => 4,
                'settings' => ['limit' => 5, 'show_location' => true]
            ],
            'class_distribution' => [
                'enabled' => true,
                'position' => 5,
                'settings' => ['show_legend' => true, 'max_items' => 10]
            ],
            'performance_metrics' => [
                'enabled' => true,
                'position' => 6,
                'settings' => ['show_growth' => true, 'compare_period' => 'previous_period']
            ],
        ];
    }

    /**
     * Get widget configuration for a user.
     *
     * @param int $userId
     * @return array
     */
    public static function getForUser(int $userId): array
    {
        $defaults = static::getDefaultWidgets();
        $preferences = static::where('user_id', $userId)
            ->get()
            ->keyBy('widget_id');

        return collect($defaults)
            ->map(function ($default, $widgetId) use ($preferences) {
                $preference = $preferences->get($widgetId);
                
                return [
                    'id' => $widgetId,
                    'enabled' => $preference->enabled ?? $default['enabled'],
                    'position' => $preference->position ?? $default['position'],
                    'settings' => array_merge(
                        $default['settings'] ?? [],
                        $preference->settings ?? []
                    )
                ];
            })
            ->sortBy('position')
            ->values()
            ->toArray();
    }

    /**
     * Save widget configuration for a user.
     *
     * @param int $userId
     * @param array $widgets
     * @return void
     */
    public static function saveForUser(int $userId, array $widgets): void
    {
        foreach ($widgets as $widget) {
            static::updateOrCreate(
                [
                    'user_id' => $userId,
                    'widget_id' => $widget['id'],
                ],
                [
                    'enabled' => $widget['enabled'],
                    'position' => $widget['position'],
                    'settings' => $widget['settings'] ?? [],
                ]
            );
        }
    }
}
