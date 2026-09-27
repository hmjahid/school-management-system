<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * A saved, reusable design for one exportable document type. The row flagged
 * `is_default` for its `document_type` drives print + PDF rendering; the other
 * rows are alternates the school can switch back to.
 */
class DocumentDesign extends Model
{
    protected static string $table = 'document_designs';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;
    protected static bool $timestamps = true;

    protected array $fillable = [
        'document_type', 'name', 'template', 'is_default',
        'settings', 'watermark', 'custom_css', 'is_active',
    ];

    protected array $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function setAttribute(string $key, mixed $value): void
    {
        if ($key === 'settings' || $key === 'watermark') {
            $value = is_array($value) ? $value : [];
            $this->attributes[$key] = json_encode($value);
        } else {
            parent::setAttribute($key, $value);
        }
    }

    public function getAttribute(string $key): mixed
    {
        if (($key === 'settings' || $key === 'watermark') && array_key_exists($key, $this->attributes)) {
            $decoded = json_decode((string) $this->attributes[$key], true);

            return is_array($decoded) ? $decoded : [];
        }

        return parent::getAttribute($key);
    }

    public function isFor(string $type): bool
    {
        return $this->document_type === $type;
    }

    /**
     * `fill()` writes attributes directly, bypassing the JSON encoding in
     * setAttribute(). Route everything through it so the write path never
     * stores a PHP array in a JSON column.
     */
    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }
}