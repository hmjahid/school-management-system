<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class AdmissionTest extends Model
{
    protected static string $table = 'admission_tests';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'admission_id', 'scheduled_at', 'venue', 'status', 'notes',
        'created_by', 'updated_by', 'metadata',
    ];

    protected array $casts = [
        'scheduled_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }
}