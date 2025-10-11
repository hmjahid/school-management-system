<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionDocumentResource extends JsonResource
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
            'type' => $this->type,
            'type_label' => $this->type_label,
            'name' => $this->name,
            'file_path' => $this->file_path,
            'file_url' => asset('storage/' . $this->file_path),
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'file_size_formatted' => $this->file_size_formatted,
            'file_extension' => $this->file_extension,
            'description' => $this->description,
            'is_approved' => $this->is_approved,
            'review_notes' => $this->review_notes,
            'reviewed_by' => $this->whenLoaded('reviewedBy', function () {
                return $this->reviewed_by ? [
                    'id' => $this->reviewed_by,
                    'name' => $this->reviewedBy?->name,
                    'email' => $this->reviewedBy?->email,
                ] : null;
            }),
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
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
                'document_types' => [
                    ['value' => 'transfer_certificate', 'label' => 'Transfer Certificate'],
                    ['value' => 'birth_certificate', 'label' => 'Birth Certificate'],
                    ['value' => 'photo', 'label' => 'Photograph'],
                    ['value' => 'mark_sheet', 'label' => 'Mark Sheet'],
                    ['value' => 'character_certificate', 'label' => 'Character Certificate'],
                    ['value' => 'migration_certificate', 'label' => 'Migration Certificate'],
                    ['value' => 'other', 'label' => 'Other Document'],
                ],
            ],
        ];
    }
}
