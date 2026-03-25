<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResultResource extends JsonResource
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
            'exam_id' => $this->exam_id,
            'student_id' => $this->student_id,
            'student_name' => $this->whenLoaded('student', function () {
                return $this->student->user->name ?? null;
            }),
            'student_roll_number' => $this->whenLoaded('student', function () {
                return $this->student->roll_number ?? null;
            }),
            'obtained_marks' => (float) $this->obtained_marks,
            'grade' => $this->grade,
            'grade_point' => $this->grade_point ? (float) $this->grade_point : null,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_badge' => $this->status_badge,
            'is_published' => (bool) $this->is_published,
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'submitted_by' => $this->whenLoaded('submittedBy', function () {
                return $this->submittedBy ? [
                    'id' => $this->submittedBy->id,
                    'name' => $this->submittedBy->user->name ?? null,
                ] : null;
            }),
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i:s'),
            'reviewed_by' => $this->whenLoaded('reviewedBy', function () {
                return $this->reviewedBy ? [
                    'id' => $this->reviewedBy->id,
                    'name' => $this->reviewedBy->user->name ?? null,
                ] : null;
            }),
            'review_remarks' => $this->review_remarks,
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'published_by' => $this->whenLoaded('publishedBy', function () {
                return $this->publishedBy ? [
                    'id' => $this->publishedBy->id,
                    'name' => $this->publishedBy->user->name ?? null,
                ] : null;
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
