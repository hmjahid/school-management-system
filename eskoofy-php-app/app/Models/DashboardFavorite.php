<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class DashboardFavorite extends Model
{
    protected static string $table = 'dashboard_favorites';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'user_id', 'url', 'label',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
