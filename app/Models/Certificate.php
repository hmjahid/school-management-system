<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'student_id',
        'certificate_type',
        'template',
        'issue_date',
        'certificate_number',
        'body',
        'status',
        'created_by',
        'generated_by',
    ];

    protected $casts = [
        'template' => 'array',
        'issue_date' => 'date',
        'body' => 'array',
    ];

    const TYPES = ['transfer', 'character', 'achievement', 'participation', 'completion'];

    const STATUS_DRAFT = 'draft';
    const STATUS_ISSUED = 'issued';
    const STATUS_REVOKED = 'revoked';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $last = static::whereYear('created_at', $year)->count();
        return sprintf('CERT-%s-%04d', $year, $last + 1);
    }
}