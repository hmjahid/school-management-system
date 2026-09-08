<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteDocument extends Model
{
    protected $fillable = [
        'title',
        'category',
        'file_path',
        'mime_type',
        'file_size',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'file_size' => 'integer',
    ];
}
