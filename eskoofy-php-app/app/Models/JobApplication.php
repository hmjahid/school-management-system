<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class JobApplication extends Model
{
    protected static string $table = 'job_applications';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'career_id', 'name', 'email', 'phone', 'resume_path', 'cover_letter',
        'status',
    ];

    protected array $casts = [
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function career()
    {
        return $this->belongsTo(Career::class);
    }
}
