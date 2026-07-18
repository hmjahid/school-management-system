<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $this->type ? ucfirst(str_replace('_', ' ', $this->type)) : null,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d H:i:s') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d H:i:s') : null,
            'duration' => $this->duration,
            'duration_formatted' => $this->duration_formatted,
            'total_marks' => (float) $this->total_marks,
            'passing_marks' => (float) $this->passing_marks,
            'grading_type' => $this->grading_type,
            'grading_type_label' => $this->grading_type ? ucfirst(str_replace('_', ' ', $this->grading_type)) : null,
            'grading_scale' => $this->grading_scale,
            'weightage' => (float) $this->weightage,
            'is_published' => (bool) $this->is_published,
            'publish_date' => $this->publish_date ? $this->publish_date->format('Y-m-d H:i:s') : null,
            'publish_remarks' => $this->publish_remarks,
            'is_upcoming' => $this->is_upcoming,
            'is_ongoing' => $this->is_ongoing,
            'is_completed' => $this->is_completed,
            'academic_session_id' => $this->academic_session_id,
            'academic_session_name' => $this->whenLoaded('academicSession', function () {
                return $this->academicSession ? $this->academicSession->name : null;
            }),
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
            'created_by' => $this->created_by,
            'created_by_name' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy ? $this->createdBy->name : null;
            }),
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->whenLoaded('updatedBy', function () {
                return $this->updatedBy ? $this->updatedBy->name : null;
            }),
            'teachers' => $this->whenLoaded('teachers', function () {
                return $this->teachers->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->user ? $teacher->user->name : null,
                        'employee_id' => $teacher->employee_id,
                        'is_chief_examiner' => (bool) $teacher->pivot->is_chief_examiner,
                        'is_observer' => (bool) $teacher->pivot->is_observer,
                        'responsibilities' => $teacher->pivot->responsibilities,
                    ];
                });
            }),
            'questions_count' => $this->when(isset($this->questions_count), $this->questions_count),
            'results_count' => $this->when(isset($this->results_count), $this->results_count),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at->format('Y-m-d H:i:s');
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
                'can' => [
                    'view' => $request->user() ? $request->user()->can('view', $this->resource) : false,
                    'update' => $request->user() ? $request->user()->can('update', $this->resource) : false,
                    'delete' => $request->user() ? $request->user()->can('delete', $this->resource) : false,
                    'publish' => $request->user() ? $request->user()->can('publish', $this->resource) : false,
                    'unpublish' => $request->user() ? $request->user()->can('unpublish', $this->resource) : false,
                    'manage_questions' => $request->user() ? $request->user()->can('manageQuestions', $this->resource) : false,
                    'manage_results' => $request->user() ? $request->user()->can('manageResults', $this->resource) : false,
                    'view_results' => $request->user() ? $request->user()->can('viewResults', $this->resource) : false,
                    'view_statistics' => $request->user() ? $request->user()->can('viewStatistics', $this->resource) : false,
                ],
                'statuses' => collect(\App\Models\Exam::getStatuses())->map(function($label, $value) {
                    return [
                        'value' => $value,
                        'label' => $label,
                    ];
                })->values(),
                'types' => collect(\App\Models\Exam::getTypes())->map(function($label, $value) {
                    return [
                        'value' => $value,
                        'label' => $label,
                    ];
                })->values(),
                'grading_types' => collect(\App\Models\Exam::getGradingTypes())->map(function($label, $value) {
                    return [
                        'value' => $value,
                        'label' => $label,
                    ];
                })->values(),
                'default_grading_scale' => \App\Models\Exam::getDefaultGradingScale(),
            ],
        ];
    }
}
