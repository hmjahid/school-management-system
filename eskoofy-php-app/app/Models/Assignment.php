<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Assignment extends Model
{
    protected static string $table = 'assignments';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'title', 'description', 'batch_id', 'class_id', 'section_id',
        'subject_id', 'due_date', 'total_marks', 'file_path',
        'allow_guardian_notes', 'created_by',
    ];

    protected array $casts = [
        'due_date' => 'datetime',
        'allow_guardian_notes' => 'boolean',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
