<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolClassResource extends JsonResource
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
            'name_with_code' => $this->name_with_code,
            'name_with_grade' => $this->name_with_grade,
            'description' => $this->description,
            'grade_level' => $this->grade_level,
            'academic_session_id' => $this->academic_session_id,
            'academic_session' => $this->whenLoaded('academicSession', function () {
                return $this->academicSession ? [
                    'id' => $this->academicSession->id,
                    'name' => $this->academicSession->name,
                    'start_date' => $this->academicSession->start_date->format('Y-m-d'),
                    'end_date' => $this->academicSession->end_date->format('Y-m-d'),
                ] : null;
            }),
            'class_teacher_id' => $this->class_teacher_id,
            'class_teacher' => $this->whenLoaded('classTeacher', function () {
                return $this->classTeacher ? [
                    'id' => $this->classTeacher->id,
                    'name' => $this->classTeacher->user->name,
                    'employee_id' => $this->classTeacher->employee_id,
                ] : null;
            }),
            'max_students' => $this->max_students,
            'is_active' => $this->is_active,
            'status_badge' => $this->status_badge,
            'monthly_fee' => (float) $this->monthly_fee,
            'admission_fee' => (float) $this->admission_fee,
            'exam_fee' => (float) $this->exam_fee,
            'other_fees' => (float) $this->other_fees,
            'total_fee' => (float) ($this->monthly_fee + $this->admission_fee + $this->exam_fee + $this->other_fees),
            'notes' => $this->notes,
            'total_students' => $this->when(isset($this->total_students), $this->total_students),
            'total_sections' => $this->when(isset($this->total_sections), $this->total_sections),
            'total_teachers' => $this->when(isset($this->total_teachers), $this->total_teachers),
            'total_subjects' => $this->when(isset($this->total_subjects), $this->total_subjects),
            'sections' => $this->whenLoaded('sections', function () {
                return $this->sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'name' => $section->name,
                        'capacity' => $section->capacity,
                        'teacher' => $section->teacher ? [
                            'id' => $section->teacher->id,
                            'name' => $section->teacher->user->name,
                        ] : null,
                    ];
                });
            }),
            'subjects' => $this->whenLoaded('subjects', function () {
                return $this->subjects->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'code' => $subject->code,
                        'type' => $subject->type,
                        'teacher' => $subject->pivot->teacher_id ? [
                            'id' => $subject->teacher->id,
                            'name' => $subject->teacher->user->name,
                        ] : null,
                    ];
                });
            }),
            'teachers' => $this->whenLoaded('teachers', function () {
                return $this->teachers->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->user->name,
                        'employee_id' => $teacher->employee_id,
                        'is_class_teacher' => (bool) $teacher->pivot->is_class_teacher,
                    ];
                });
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at->format('Y-m-d H:i:s');
            }),
            'links' => [
                'show' => route('api.classes.show', $this->id),
                'edit' => route('admin.classes.edit', $this->id),
                'students' => route('api.classes.students', $this->id),
                'timetable' => route('api.classes.timetable', $this->id),
                'attendance' => route('api.classes.attendance.index', $this->id),
                'exams' => route('api.classes.exams.index', $this->id),
                'fees' => route('api.classes.fees.index', $this->id),
                'notices' => route('api.classes.notices.index', $this->id),
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
                    'view_students' => $request->user() ? $request->user()->can('viewStudents', $this->resource) : false,
                    'view_timetable' => $request->user() ? $request->user()->can('viewTimetable', $this->resource) : false,
                    'manage_subjects' => $request->user() ? $request->user()->can('manageSubjects', $this->resource) : false,
                    'manage_teachers' => $request->user() ? $request->user()->can('manageTeachers', $this->resource) : false,
                ],
            ],
        ];
    }
}
