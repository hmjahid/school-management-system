<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'paymentable_type' => $this->paymentable_type,
            'paymentable_id' => $this->paymentable_id,
            'paymentable' => $this->whenLoaded('paymentable'),
            'amount' => (float) $this->amount,
            'paid_amount' => (float) $this->paid_amount,
            'due_amount' => (float) $this->due_amount,
            'discount_amount' => (float) $this->discount_amount,
            'fine_amount' => (float) $this->fine_amount,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status_label' => $this->status_label,
            'payment_date' => $this->payment_date?->toDateTimeString(),
            'reference_number' => $this->reference_number,
            'transaction_id' => $this->transaction_id,
            'currency' => $this->currency ?? 'BDT',
            'is_fully_paid' => $this->is_fully_paid,
            'is_overdue' => $this->is_overdue,
            'notes' => $this->notes,
            'metadata' => $this->metadata ?? [],
            'payment_details' => $this->payment_details ?? [],
            'created_by' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy ? [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ] : null;
            }),
            'updated_by' => $this->whenLoaded('updatedBy', function () {
                return $this->updatedBy ? [
                    'id' => $this->updatedBy->id,
                    'name' => $this->updatedBy->name,
                    'email' => $this->updatedBy->email,
                ] : null;
            }),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at?->toDateTimeString();
            }),
            // Additional calculated fields
            'payment_method_label' => $this->method_label,
            'formatted_amount' => number_format($this->total_amount, 2) . ' ' . ($this->currency ?? 'BDT'),
            'can_edit' => $request->user()?->can('update', $this->resource) ?? false,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
        ];
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'statuses' => [
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'refunded' => 'Refunded',
                    'cancelled' => 'Cancelled',
                    'expired' => 'Expired',
                ],
                'methods' => [
                    'cash' => 'Cash',
                    'bank_transfer' => 'Bank Transfer',
                    'cheque' => 'Cheque',
                    'bkash' => 'bKash',
                    'nagad' => 'Nagad',
                    'rocket' => 'Rocket',
                    'stripe' => 'Stripe',
                    'paypal' => 'PayPal',
                    'other' => 'Other',
                ],
                'currencies' => [
                    'BDT' => 'Bangladeshi Taka',
                    'USD' => 'US Dollar',
                    'EUR' => 'Euro',
                    'GBP' => 'British Pound',
                ],
            ],
        ];
    }
}
