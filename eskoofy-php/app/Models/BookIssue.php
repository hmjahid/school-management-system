<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class BookIssue extends Model
{
    protected static string $table = 'book_issues';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'book_id', 'student_id', 'teacher_id', 'issue_date', 'due_date',
        'return_date', 'status', 'late_fee', 'fine_paid', 'notes', 'issued_by',
    ];

    protected array $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'late_fee' => 'float',
        'fine_paid' => 'boolean',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
