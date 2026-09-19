<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Fee extends Model
{

    public const TYPE_TUITION = 'tuition';
    public const FREQUENCY_MONTHLY = 'monthly';

    protected static string $table = 'fees';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'code', 'description', 'class_id', 'section_id',
        'student_id', 'amount', 'fee_type', 'frequency', 'start_date',
        'end_date', 'fine_amount', 'fine_type', 'fine_grace_days',
        'discount_amount', 'discount_type', 'status', 'metadata', 'created_by',
    ];

    protected array $casts = [
        'amount' => 'float',
        'fine_amount' => 'float',
        'discount_amount' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'json',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function payments()
    {
        return $this->hasMany(FeePayment::class);
    }
}
