<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class UserWidgetPreference extends Model
{
    protected static string $table = 'user_widget_preferences';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'user_id', 'widget_id', 'enabled', 'position', 'settings',
    ];

    protected array $casts = [
        'enabled'  => 'boolean',
        'position' => 'integer',
        'settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a user's widget preferences keyed by widget_id, with defaults.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function preferencesFor(int $userId): array
    {
        $rows = static::query()->where('user_id', $userId)->get();
        $prefs = [];
        foreach ($rows as $row) {
            $prefs[$row->widget_id] = [
                'enabled'  => (bool) $row->enabled,
                'position' => (int) $row->position,
                'settings' => $row->settings ? json_decode((string) $row->settings, true) : [],
            ];
        }
        return $prefs;
    }
}