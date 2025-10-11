<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'date' => $this->date->format('Y-m-d'),
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'type' => $this->type,
            'type_label' => $this->type ? ucfirst(str_replace('_', ' ', $this->type)) : null,
            'batch_id' => $this->batch_id,
            'batch_name' => $this->whenLoaded('batch', function () {
                return $this->batch ? $this->batch->name : null;
            }),
            'batch_code' => $this->whenLoaded('batch', function () {
                return $this->batch ? $this->batch->code : null;
            }),
            'section_id' => $this->section_id,
            'section_name' => $this->whenLoaded('section', function () {
                return $this->section ? $this->section->name : null;
            }),
            'subject_id' => $this->subject_id,
            'subject_name' => $this->whenLoaded('subject', function () {
                return $this->subject ? $this->subject->name : null;
            }),
            'subject_code' => $this->whenLoaded('subject', function () {
                return $this->subject ? $this->subject->code : null;
            }),
            'student_id' => $this->student_id,
            'student_name' => $this->whenLoaded('student', function () {
                return $this->student ? $this->student->user->name : null;
            }),
            'admission_number' => $this->whenLoaded('student', function () {
                return $this->student ? $this->student->admission_number : null;
            }),
            'roll_number' => $this->whenLoaded('student', function () {
                return $this->student ? $this->student->roll_number : null;
            }),
            'teacher_id' => $this->teacher_id,
            'teacher_name' => $this->whenLoaded('teacher', function () {
                return $this->teacher ? $this->teacher->user->name : null;
            }),
            'employee_id' => $this->whenLoaded('teacher', function () {
                return $this->teacher ? $this->teacher->employee_id : null;
            }),
            'academic_session_id' => $this->academic_session_id,
            'academic_session_name' => $this->whenLoaded('academicSession', function () {
                return $this->academicSession ? $this->academicSession->name : null;
            }),
            'period' => $this->period,
            'remarks' => $this->remarks,
            'recorded_by' => $this->recorded_by,
            'recorded_by_name' => $this->whenLoaded('recordedBy', function () {
                return $this->recordedBy ? $this->recordedBy->name : null;
            }),
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->whenLoaded('updatedBy', function () {
                return $this->updatedBy ? $this->updatedBy->name : null;
            }),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at->format('Y-m-d H:i:s');
            }),
            'links' => [
                'show' => route('api.attendances.show', $this->id),
                'edit' => route('admin.attendances.edit', $this->id),
                'delete' => route('api.attendances.destroy', $this->id),
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
                'can' => [
                    'view' => $request->user() ? $request->user()->can('view', $this->resource) : false,
                    'update' => $request->user() ? $request->user()->can('update', $this->resource) : false,
                    'delete' => $request->user() ? $request->user()->can('delete', $this->resource) : false,
                ],
                'statuses' => collect(\App\Models\Attendance::getStatuses())->map(function($label, $value) {
                    return [
                        'value' => $value,
                        'label' => $label,
                    ];
                })->values(),
                'types' => collect(\App\Models\Attendance::getTypes())->map(function($label, $value) {
                    return [
                        'value' => $value,
                        'label' => $label,
                    ];
                })->values(),
            ],
        ];
    }
}
