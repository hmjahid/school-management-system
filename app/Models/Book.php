<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'author', 'publisher', 'isbn', 'category_id', 'shelf_location',
        'quantity', 'available_quantity', 'purchase_date', 'price', 'description',
        'cover_image', 'status', 'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'available_quantity' => 'integer',
        'status' => 'boolean',
    ];

    protected $appends = ['cover_url'];

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_image ? Storage::url($this->cover_image) : null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(BookIssue::class);
    }

    public function currentIssues(): HasMany
    {
        return $this->hasMany(BookIssue::class)->where('status', 'issued');
    }

    public function isAvailable(): bool
    {
        return $this->status && $this->available_quantity > 0;
    }
}
