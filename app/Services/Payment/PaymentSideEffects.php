<?php

namespace App\Services\Payment;

use App\Models\FeePayment;
use App\Models\Payment;

trait PaymentSideEffects
{
    /**
     * Apply payment completion to domain records (FeePayment, etc.).
     *
     * @param  array<string, mixed>  $context
     */
    protected function applyPaymentSideEffects(Payment $payment, array $context = []): void
    {
        $feePaymentId = $payment->metadata['fee_payment_id'] ?? null;
        if (! $feePaymentId) {
            return;
        }

        $feePayment = FeePayment::query()->find($feePaymentId);
        if (! $feePayment) {
            return;
        }

        if ($feePayment->status === FeePayment::STATUS_PAID) {
            return;
        }

        $feePayment->update([
            'status' => FeePayment::STATUS_PAID,
            'paid_amount' => $feePayment->amount,
            'balance' => 0,
            'transaction_id' => $context['transaction_id'] ?? $feePayment->transaction_id,
            'payment_method' => FeePayment::METHOD_ONLINE_PAYMENT,
            'metadata' => array_merge($feePayment->metadata ?? [], [
                'gateway' => $context['gateway'] ?? $payment->payment_method,
                'payment_id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
            ]),
        ]);
    }
}
