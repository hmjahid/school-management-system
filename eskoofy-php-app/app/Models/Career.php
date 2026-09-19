<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Career extends Model
{
    protected static string $table = 'careers';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'title', 'description', 'requirements', 'type', 'location',
        'salary_min', 'salary_max', 'deadline', 'is_published',
    ];

    protected array $casts = [
        'deadline' => 'date',
        'is_published' => 'boolean',
        'salary_min' => 'float',
        'salary_max' => 'float',
    ];

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }
}
