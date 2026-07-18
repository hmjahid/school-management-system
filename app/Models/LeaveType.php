<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = ['name_en', 'name_bn', 'days_per_year', 'is_paid', 'is_active'];

    protected $casts = ['days_per_year' => 'integer', 'is_paid' => 'boolean', 'is_active' => 'boolean'];

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function name(): string
    {
        return app()->getLocale() === 'bn' && $this->name_bn ? $this->name_bn : $this->name_en;
    }
}