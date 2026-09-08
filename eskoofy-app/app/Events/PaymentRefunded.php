<?php

namespace App\Events;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Payment $payment, public Refund $refund) {}
}
