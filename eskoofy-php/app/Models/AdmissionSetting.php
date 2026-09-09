<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class AdmissionSetting extends Model
{
    protected static string $table = 'admission_settings';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'is_open', 'closed_message_en', 'closed_message_bn', 'admission_fee',
        'payment_number', 'payment_instructions_en', 'payment_instructions_bn',
        'notice_en', 'notice_bn', 'display_year', 'bar_title_en', 'bar_title_bn',
    ];

    protected array $casts = [
        'is_open' => 'boolean',
        'admission_fee' => 'float',
    ];
}
