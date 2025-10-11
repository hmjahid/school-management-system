<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeePaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'student_id' => $this->student_id,
            'student_name' => $this->whenLoaded('student', fn() => $this->student->name),
            'student_admission_number' => $this->whenLoaded('student', fn() => $this->student->admission_number ?? null),
            'class_name' => $this->whenLoaded('student.schoolClass', fn() => $this->student->schoolClass->name ?? null),
            'section_name' => $this->whenLoaded('student.section', fn() => $this->student->section->name ?? null),
            'fee_id' => $this->fee_id,
            'fee_name' => $this->whenLoaded('fee', fn() => $this->fee->name ?? null),
            'fee_type' => $this->whenLoaded('fee', fn() => $this->fee->fee_type ?? null),
            'fee_type_label' => $this->whenLoaded('fee', function() {
                if (!$this->fee) return null;
                $types = \App\Models\Fee::getFeeTypes();
                return $types[$this->fee->fee_type] ?? $this->fee->fee_type;
            }),
            'amount' => (float) $this->amount,
            'formatted_amount' => number_format($this->amount, 2),
            'discount_amount' => (float) $this->discount_amount,
            'formatted_discount_amount' => number_format($this->discount_amount, 2),
            'fine_amount' => (float) $this->fine_amount,
            'formatted_fine_amount' => number_format($this->fine_amount, 2),
            'paid_amount' => (float) $this->paid_amount,
            'formatted_paid_amount' => number_format($this->paid_amount, 2),
            'balance' => (float) $this->balance,
            'formatted_balance' => number_format($this->balance, 2),
            'payment_date' => $this->payment_date->format('Y-m-d'),
            'month' => $this->month,
            'year' => $this->year,
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->payment_method_label,
            'transaction_id' => $this->transaction_id,
            'bank_name' => $this->bank_name,
            'check_number' => $this->check_number,
            'status' => $this->status,
            'status_label' => ucfirst($this->status),
            'status_badge' => $this->status_badge,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'created_by_name' => $this->whenLoaded('creator', fn() => $this->creator->name ?? null),
            'approved_by' => $this->approved_by,
            'approved_by_name' => $this->whenLoaded('approver', fn() => $this->approver->name ?? null),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->format('Y-m-d H:i:s')),
            'receipt_url' => route('api.fee-payments.receipt', $this->id),
        ];
    }
}
