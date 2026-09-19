<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use App\Core\Support\Carbon;

class BookIssue extends Model
{
    protected static string $table = 'book_issues';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    public const STATUS_ISSUED = 'issued';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_LOST = 'lost';
    public const STATUS_DAMAGED = 'damaged';

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopeOverdue($query)
    {
        return $query
            ->where('status', self::STATUS_ISSUED)
            ->whereRaw('due_date < ?', [date('Y-m-d')]);
    }

    public function scopeReturned($query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    public function isOverdue(): bool
    {
        if ($this->getAttribute('status') !== self::STATUS_ISSUED) {
            return false;
        }
        $due = $this->getAttribute('due_date');
        return $due !== null && $due->isPast();
    }

    public function calculateLateFee(float $lateFeePerDay): float
    {
        $dueDate = $this->getAttribute('due_date');
        $returnDate = $this->getAttribute('return_date');
        if ($returnDate !== null && $dueDate !== null && $returnDate->gt($dueDate)) {
            return (float) round($dueDate->diffInDays($returnDate) * $lateFeePerDay, 2);
        }
        if ($this->isOverdue()) {
            return (float) round($dueDate->diffInDays(new Carbon()) * $lateFeePerDay, 2);
        }
        return 0.0;
    }

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
