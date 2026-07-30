<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibrarySetting extends Model
{
    protected $fillable = [
        'late_fee_per_day', 'max_books_per_student', 'max_books_per_teacher', 'issue_duration_days',
    ];

    protected $casts = [
        'late_fee_per_day' => 'decimal:2',
        'max_books_per_student' => 'integer',
        'max_books_per_teacher' => 'integer',
        'issue_duration_days' => 'integer',
    ];

    public static function getSettings(): self
    {
        return static::first() ?? static::create([
            'late_fee_per_day' => 5.00,
            'max_books_per_student' => 3,
            'max_books_per_teacher' => 10,
            'issue_duration_days' => 14,
        ]);
    }
}
