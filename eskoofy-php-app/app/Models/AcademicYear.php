<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class AcademicYear extends Model
{
    protected static string $table = 'academic_years';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'session', 'start_date', 'end_date', 'is_current', 'description',
    ];

    protected array $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
