<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A saved, reusable design for one exportable document type. The row flagged
 * `is_default` for its `document_type` drives print + PDF rendering; the
 * other rows are alternates the school can switch back to.
 */
class DocumentDesign extends Model
{
    protected $fillable = [
        'document_type',
        'name',
        'template',
        'is_default',
        'settings',
        'watermark',
        'custom_css',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'watermark' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function isFor(string $type): bool
    {
        return $this->document_type === $type;
    }
}
