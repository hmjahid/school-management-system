<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class LeaveType extends Model
{
    protected static string $table = 'leave_types';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name_en', 'name_bn', 'days_per_year', 'is_paid', 'is_active',
    ];

    protected array $casts = [
        'days_per_year' => 'integer',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function requests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function name(): string
    {
        $name = $this->name_en ?? $this->name_bn ?? ($this->attributes['name'] ?? null);
        return (string) ($name ?? '');
    }
}
