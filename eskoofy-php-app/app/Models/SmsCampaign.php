<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class SmsCampaign extends Model
{
    protected static string $table = 'sms_campaigns';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'audience_type', 'school_class_id', 'section_id', 'message',
        'scheduled_at', 'status', 'sent_at', 'created_by',
    ];

    protected array $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(SmsCampaignRecipient::class);
    }
}
