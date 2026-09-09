<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class CommitteeMember extends Model
{
    protected static string $table = 'committee_members';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'name_bn', 'designation', 'designation_bn', 'photo', 'phone',
        'email', 'bio', 'bio_bn', 'sort_order', 'is_active',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
