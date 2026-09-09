<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Invoice extends Model
{
    protected static string $table = 'invoices';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'student_id', 'fee_id', 'amount', 'due_date', 'status',
    ];

    protected array $casts = [
        'amount' => 'float',
        'due_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }
}
