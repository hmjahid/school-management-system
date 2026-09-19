<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class AssignmentSubmission extends Model
{
    protected static string $table = 'assignment_submissions';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'assignment_id', 'student_id', 'guardian_id', 'file_path', 'notes',
        'guardian_notes', 'guardian_notified_at', 'submitted_at', 'marks',
        'feedback', 'graded_by', 'graded_at', 'status',
    ];

    protected array $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'guardian_notified_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
