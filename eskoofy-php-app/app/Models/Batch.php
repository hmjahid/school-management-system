<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Batch extends Model
{
    protected static string $table = 'batches';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'code', 'description', 'start_date', 'end_date',
        'academic_session_id', 'course_id', 'max_students', 'fee_amount',
        'is_active', 'is_featured', 'registration_starts_at',
        'registration_ends_at', 'class_days', 'class_timing', 'venue',
        'teacher_id', 'assistant_teacher_id', 'status', 'notes',
    ];

    protected array $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_starts_at' => 'datetime',
        'registration_ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'max_students' => 'integer',
        'fee_amount' => 'float',
        'class_days' => 'json',
        'class_timing' => 'json',
    ];
}
