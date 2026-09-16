<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Course extends Model
{
    protected static string $table = 'courses';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'code', 'description', 'is_active',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
    ];

    public function batches()
    {
        return $this->hasMany(Batch::class, 'course_id');
    }
}