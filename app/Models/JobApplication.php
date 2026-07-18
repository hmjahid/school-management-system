<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'career_id',
        'name',
        'email',
        'phone',
        'resume_path',
        'cover_letter',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'applied_at' => 'datetime',
    ];

    /**
     * Get the career that the application belongs to.
     */
    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class);
    }
}
