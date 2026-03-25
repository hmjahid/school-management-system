<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
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
            'class_id' => $this->class_id,
            'class_name' => $this->whenLoaded('class', $this->class?->name),
            'class_code' => $this->whenLoaded('class', $this->class?->code),
            'teacher_id' => $this->teacher_id,
            'teacher_name' => $this->whenLoaded('teacher', $this->teacher?->user?->name),
            'teacher_employee_id' => $this->whenLoaded('teacher', $this->teacher?->employee_id),
            'academic_session_id' => $this->academic_session_id,
            'academic_session_name' => $this->whenLoaded('academicSession', $this->academicSession?->name),
            'capacity' => (int) $this->capacity,
            'room_number' => $this->room_number,
            'is_active' => (bool) $this->is_active,
            'status' => $this->is_active ? 'active' : 'inactive',
            'status_badge' => $this->status_badge,
            'notes' => $this->notes,
            'student_count' => $this->when(isset($this->students_count), $this->students_count),
            'available_seats' => $this->when(isset($this->available_seats), $this->available_seats),
            'has_available_seats' => $this->when(isset($this->has_available_seats), $this->has_available_seats),
            'full_name' => $this->full_name,
            'class_teacher_name' => $this->class_teacher_name,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at->format('Y-m-d H:i:s');
            }),
            'subjects' => $this->whenLoaded('subjects', function () {
                return $this->subjects->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'code' => $subject->code,
                        'type' => $subject->type,
                        'teacher' => $subject->pivot->teacher ? [
                            'id' => $subject->pivot->teacher->id,
                            'name' => $subject->pivot->teacher->user->name,
                            'employee_id' => $subject->pivot->teacher->employee_id,
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
                        'subjects' => $teacher->subjects->map(function ($subject) {
                            return [
                                'id' => $subject->id,
                                'name' => $subject->name,
                                'code' => $subject->code,
                            ];
                        }),
                    ];
                });
            }),
            'links' => [
                'show' => route('api.sections.show', $this->id),
                'edit' => route('admin.sections.edit', $this->id),
                'students' => route('api.sections.students', $this->id),
                'subjects' => route('api.sections.subjects.index', $this->id),
                'teachers' => route('api.sections.teachers.index', $this->id),
                'attendance' => route('api.sections.attendance.index', $this->id),
                'timetable' => route('api.sections.timetable.index', $this->id),
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
                    'manage_subjects' => $request->user() ? $request->user()->can('manageSubjects', $this->resource) : false,
                    'manage_teachers' => $request->user() ? $request->user()->can('manageTeachers', $this->resource) : false,
                    'manage_attendance' => $request->user() ? $request->user()->can('manageAttendance', $this->resource) : false,
                    'manage_timetable' => $request->user() ? $request->user()->can('manageTimetable', $this->resource) : false,
                    'view_students' => $request->user() ? $request->user()->can('viewStudents', $this->resource) : false,
                ],
            ],
        ];
    }
}
