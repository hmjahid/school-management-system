<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Append-only log of cloud backup attempts (success / failed / skipped).
 * The "skipped" status is what the interval dispatcher records when the
 * configured interval has not elapsed yet.
 */
class CloudBackupRun extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected static string $table = 'cloud_backup_runs';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;
    protected static bool $timestamps = true;

    protected array $fillable = [
        'provider', 'file_name', 'remote_id', 'size', 'status', 'message',
    ];

    protected array $casts = [
        'size' => 'integer',
    ];
}