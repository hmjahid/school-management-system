<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    protected $fillable = [
        'title',
        'content',
        'attachments',
        'pinned',
        'audience',
        'created_by',
    ];

    protected $casts = [
        'attachments' => 'array',
        'audience' => 'array',
        'pinned' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
