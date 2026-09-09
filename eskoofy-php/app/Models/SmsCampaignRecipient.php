<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class SmsCampaignRecipient extends Model
{
    protected static string $table = 'sms_campaign_recipients';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'sms_campaign_id', 'phone', 'user_type', 'user_id', 'status', 'error',
    ];

    public function campaign()
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }
}
