<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WebsiteMedia extends Model
{
    protected $fillable = [
        'title',
        'category',
        'file_path',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function url(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return url('storage/' . ltrim($this->file_path, '/'));
    }

    public function isImage(): bool
    {
        return $this->mime_type !== null && str_starts_with($this->mime_type, 'image/');
    }
}
