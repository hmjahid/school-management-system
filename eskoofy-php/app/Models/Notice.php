<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Notice extends Model
{
    protected static string $table = 'notices';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'title', 'title_bn', 'content', 'content_bn', 'attachments',
        'pinned', 'audience', 'created_by',
    ];

    protected array $casts = [
        'attachments' => 'json',
        'audience' => 'json',
        'pinned' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
