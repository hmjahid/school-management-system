<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = 'refund';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'transaction_id' => $this->transaction_id,
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'reason' => $this->reason,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'metadata' => $this->metadata ?? (object) [],
            
            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            
            'processor' => $this->whenLoaded('processor', function () {
                return [
                    'id' => $this->processor->id,
                    'name' => $this->processor->name,
                    'email' => $this->processor->email,
                ];
            }),
            
            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'id' => $this->payment->id,
                    'amount' => (float) $this->payment->amount,
                    'currency' => $this->payment->currency,
                    'payment_method' => $this->payment->payment_method,
                    'payment_status' => $this->payment->payment_status,
                    'created_at' => $this->payment->created_at->toIso8601String(),
                ];
            }),
            
            // Links
            'links' => [
                'self' => route('api.refunds.show', $this->id),
                'payment' => route('api.payments.show', $this->payment_id),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'version' => '1.0',
                'api_version' => 'v1',
            ],
        ];
    }
}
