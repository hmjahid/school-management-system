<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'student_id',
        'testimonial_type',
        'testimonial_number',
        'issue_date',
        'status',
        'body',
        'generated_by',
        'author_name',
        'author_designation',
        'content',
        'rating',
        'photo',
        'is_visible',
        'sort_order',
        'details',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'rating' => 'integer',
        'issue_date' => 'date',
        'body' => 'array',
        'details' => 'array',
    ];

    const TYPES = [
        'behavior',
        'academic_excellence',
        'sports',
        'arts',
        'leadership',
        'community_service',
        'attendance',
        'discipline',
        'creativity',
        'overall',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_ISSUED = 'issued';
    const STATUS_REVOKED = 'revoked';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $last = static::whereYear('created_at', $year)->count();
        return sprintf('TEST-%s-%04d', $year, $last + 1);
    }
}
