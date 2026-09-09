<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class AcademicSession extends Model
{
    protected static string $table = 'academic_sessions';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'code', 'start_date', 'end_date', 'description',
        'is_active', 'is_current', 'status', 'metadata',
    ];

    protected array $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_current' => 'boolean',
        'metadata' => 'json',
    ];
}
