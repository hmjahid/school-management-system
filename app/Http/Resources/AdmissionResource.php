<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionResource extends JsonResource
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
            // Basic Information
            'id' => $this->id,
            'application_number' => $this->application_number,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_badge' => $this->status_badge,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'photo_url' => $this->photo ? asset('storage/' . $this->photo) : null,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->date_of_birth ? $this->date_of_birth->age : null,
            'gender' => $this->gender,
            'gender_formatted' => ucfirst($this->gender),
            'blood_group' => $this->blood_group,
            'religion' => $this->religion,
            'nationality' => $this->nationality,
            
            // Contact Information
            'address' => [
                'address_line' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
            ],
            
            // Parent/Guardian Information
            'father' => [
                'name' => $this->father_name,
                'phone' => $this->father_phone,
                'occupation' => $this->father_occupation,
            ],
            'mother' => [
                'name' => $this->mother_name,
                'phone' => $this->mother_phone,
                'occupation' => $this->mother_occupation,
            ],
            'guardian' => $this->guardian_name ? [
                'name' => $this->guardian_name,
                'relation' => $this->guardian_relation,
                'phone' => $this->guardian_phone,
            ] : null,
            
            // Previous Education
            'previous_education' => [
                'school' => $this->previous_school,
                'class' => $this->previous_class,
                'grade' => $this->previous_grade,
            ],
            
            // Academic Information
            'academic_session' => [
                'id' => $this->academic_session_id,
                'name' => $this->whenLoaded('academicSession', $this->academicSession?->name),
            ],
            'batch' => [
                'id' => $this->batch_id,
                'name' => $this->whenLoaded('batch', $this->batch?->name),
                'course_name' => $this->whenLoaded('batch', $this->batch?->course?->name),
            ],
            
            // Documents
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'type' => $document->type,
                        'type_label' => $document->type_label,
                        'name' => $document->name,
                        'file_url' => asset('storage/' . $document->file_path),
                        'file_type' => $document->file_type,
                        'file_size' => $document->file_size,
                        'file_size_formatted' => $document->file_size_formatted,
                        'file_extension' => $document->file_extension,
                        'is_approved' => $document->is_approved,
                        'review_notes' => $document->review_notes,
                        'reviewed_by' => $document->reviewed_by,
                        'reviewed_at' => $document->reviewed_at?->format('Y-m-d H:i:s'),
                        'created_at' => $document->created_at?->format('Y-m-d H:i:s'),
                        'updated_at' => $document->updated_at?->format('Y-m-d H:i:s'),
                    ];
                });
            }),
            
            // Status Timestamps
            'timestamps' => [
                'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
                'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
                'rejected_at' => $this->rejected_at?->format('Y-m-d H:i:s'),
                'enrolled_at' => $this->enrolled_at?->format('Y-m-d H:i:s'),
                'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],
            
            // User References
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->created_by,
                    'name' => $this->createdBy?->name,
                    'email' => $this->createdBy?->email,
                ];
            }),
            'updated_by' => $this->whenLoaded('updatedBy', function () {
                return $this->updated_by ? [
                    'id' => $this->updated_by,
                    'name' => $this->updatedBy?->name,
                    'email' => $this->updatedBy?->email,
                ] : null;
            }),
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return $this->approved_by ? [
                    'id' => $this->approved_by,
                    'name' => $this->approvedBy?->name,
                    'email' => $this->approvedBy?->email,
                ] : null;
            }),
            'rejected_by' => $this->whenLoaded('rejectedBy', function () {
                return $this->rejected_by ? [
                    'id' => $this->rejected_by,
                    'name' => $this->rejectedBy?->name,
                    'email' => $this->rejectedBy?->email,
                ] : null;
            }),
            
            // Additional Information
            'admission_notes' => $this->admission_notes,
            'rejection_reason' => $this->rejection_reason,
            'metadata' => $this->metadata,
            
            // Student Information (if enrolled)
            'student' => $this->whenLoaded('student', function () {
                return $this->student ? [
                    'id' => $this->student->id,
                    'roll_number' => $this->student->roll_number,
                    'admission_date' => $this->student->admission_date?->format('Y-m-d'),
                ] : null;
            }),
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
                'statuses' => [
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'submitted', 'label' => 'Submitted'],
                    ['value' => 'under_review', 'label' => 'Under Review'],
                    ['value' => 'approved', 'label' => 'Approved'],
                    ['value' => 'rejected', 'label' => 'Rejected'],
                    ['value' => 'waitlisted', 'label' => 'Waitlisted'],
                    ['value' => 'enrolled', 'label' => 'Enrolled'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                ],
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
