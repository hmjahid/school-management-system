<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'start_date',
        'end_date',
        'description',
        'is_active',
        'is_current',
        'status',
        'metadata'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_current' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // When creating a new academic session, ensure only one is marked as current
        static::saving(function ($model) {
            if ($model->is_current) {
                static::where('is_current', true)->update(['is_current' => false]);
            }
        });
    }

    /**
     * Get the current academic session.
     *
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public static function current()
    {
        return static::where('is_current', true)->first();
    }

    /**
     * Scope a query to only include active academic sessions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
