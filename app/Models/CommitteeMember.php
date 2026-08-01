<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CommitteeMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_bn',
        'designation',
        'designation_bn',
        'photo',
        'phone',
        'email',
        'bio',
        'bio_bn',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? url('storage/' . ltrim($this->photo, '/')) : null;
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'bn' && ! empty($this->name_bn) && $this->name_bn !== $this->name) {
            return $this->name_bn;
        }

        return $this->name;
    }

    public function localizedDesignation(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'bn' && ! empty($this->designation_bn) && $this->designation_bn !== $this->designation) {
            return $this->designation_bn;
        }

        return $this->designation;
    }

    public function localizedBio(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'bn' && ! empty($this->bio_bn) && $this->bio_bn !== $this->bio) {
            return $this->bio_bn;
        }

        return $this->bio;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
