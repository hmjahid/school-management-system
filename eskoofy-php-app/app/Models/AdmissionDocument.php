<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class AdmissionDocument extends Model
{
    protected static string $table = 'admission_documents';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'admission_id', 'type', 'name', 'file_path', 'file_size',
        'file_type', 'description', 'is_approved', 'review_notes',
        'reviewed_by', 'reviewed_at',
    ];

    protected array $casts = [
        'is_approved' => 'boolean',
        'file_size' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }
}
