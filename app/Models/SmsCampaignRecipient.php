<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCampaignRecipient extends Model
{
    protected $fillable = ['sms_campaign_id', 'phone', 'user_type', 'user_id', 'status', 'error'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class);
    }
}