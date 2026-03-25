<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    public const TYPE_CONTACT = 'contact';

    public const TYPE_FEEDBACK = 'feedback';

    public const TYPE_COMPLAINT = 'complaint';

    public const TYPE_SCHOLARSHIP = 'scholarship';

    public const TYPE_NEWSLETTER = 'newsletter';

    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
