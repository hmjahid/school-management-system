<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'label',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
