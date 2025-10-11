<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'class_id' => $this->class_id,
            'class_name' => $this->whenLoaded('schoolClass', fn() => $this->schoolClass->name),
            'section_id' => $this->section_id,
            'section_name' => $this->whenLoaded('section', fn() => $this->section->name),
            'student_id' => $this->student_id,
            'student_name' => $this->whenLoaded('student', fn() => $this->student->name),
            'amount' => (float) $this->amount,
            'formatted_amount' => number_format($this->amount, 2),
            'fee_type' => $this->fee_type,
            'fee_type_label' => $this->getFeeTypes()[$this->fee_type] ?? $this->fee_type,
            'frequency' => $this->frequency,
            'frequency_label' => $this->getFrequencies()[$this->frequency] ?? $this->frequency,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'fine_amount' => (float) $this->fine_amount,
            'formatted_fine_amount' => number_format($this->fine_amount, 2),
            'fine_type' => $this->fine_type,
            'fine_grace_days' => $this->fine_grace_days,
            'discount_amount' => (float) $this->discount_amount,
            'formatted_discount_amount' => number_format($this->discount_amount, 2),
            'discount_type' => $this->discount_type,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
