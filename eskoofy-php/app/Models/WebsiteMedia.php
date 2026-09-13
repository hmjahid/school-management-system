<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class WebsiteMedia extends Model
{
    protected static string $table = 'website_media';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'title', 'category', 'file_path', 'mime_type', 'file_size',
    ];

    protected array $casts = [
        'file_size' => 'integer',
    ];

    public function url(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return url('storage/' . ltrim($this->file_path, '/'));
    }

    public function isImage(): bool
    {
        return $this->mime_type !== null && str_starts_with($this->mime_type, 'image/');
    }
}
