<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmitCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_id',
        'student_id',
        'admit_card_number',
        'issue_date',
        'details',
        'status',
        'generated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'details' => 'array',
    ];

    const STATUS_ISSUED = 'issued';
    const STATUS_REVOKED = 'revoked';

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public static function generateNumber(Exam $exam, Student $student): string
    {
        return sprintf('ADMIT-%s-%s-%04d', $exam->id, $student->id, static::count() + 1);
    }
}
