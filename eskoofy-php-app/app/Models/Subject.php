<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Subject extends Model
{
    protected static string $table = 'subjects';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'code', 'type', 'short_name', 'credit_hours',
        'description', 'is_active', 'is_elective', 'has_lab',
        'theory_marks', 'practical_marks', 'passing_marks',
        'max_class_per_week', 'priority', 'notes',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
        'is_elective' => 'boolean',
        'has_lab' => 'boolean',
        'credit_hours' => 'float',
        'theory_marks' => 'float',
        'practical_marks' => 'float',
        'passing_marks' => 'float',
        'max_class_per_week' => 'integer',
        'priority' => 'integer',
    ];
}
