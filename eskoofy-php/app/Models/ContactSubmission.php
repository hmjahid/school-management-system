<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class ContactSubmission extends Model
{
    protected static string $table = 'contact_submissions';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'type', 'name', 'email', 'phone', 'subject', 'message', 'meta',
    ];

    protected array $casts = [
        'meta' => 'json',
    ];
}
