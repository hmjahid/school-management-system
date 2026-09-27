<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    protected $fillable = [
        'provider',
        'file_name',
        'remote_id',
        'size',
        'status',
        'message',
    ];

    protected $casts = [
        'size' => 'integer',
    ];
}
