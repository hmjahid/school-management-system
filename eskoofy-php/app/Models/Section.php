<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Section extends Model
{
    protected static string $table = 'sections';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'slug', 'capacity', 'description', 'is_active',
        'class_teacher_id', 'academic_year_id', 'class_id',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
