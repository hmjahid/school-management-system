<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use App\Core\Schema;

class LibrarySetting extends Model
{
    protected static string $table = 'library_settings';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'late_fee_per_day', 'max_books_per_student', 'max_books_per_teacher', 'issue_duration_days',
    ];

    protected array $casts = [
        'late_fee_per_day' => 'float',
        'max_books_per_student' => 'integer',
        'max_books_per_teacher' => 'integer',
        'issue_duration_days' => 'integer',
    ];

    public static function getSettings(): self
    {
        $defaults = [
            'late_fee_per_day' => 5.00,
            'max_books_per_student' => 3,
            'max_books_per_teacher' => 10,
            'issue_duration_days' => 14,
        ];

        if (!Schema::hasTable(static::$table)) {
            return new static($defaults);
        }

        try {
            $existing = static::query()->first();
            if ($existing !== null) {
                return $existing;
            }
            return static::create($defaults);
        } catch (\Throwable) {
            return new static($defaults);
        }
    }
}