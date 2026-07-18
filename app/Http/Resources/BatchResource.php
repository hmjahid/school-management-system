<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
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
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'duration_weeks' => $this->duration_weeks,
            'academic_session_id' => $this->academic_session_id,
            'academic_session_name' => $this->whenLoaded('academicSession', $this->academicSession?->name),
            'course_id' => $this->course_id,
            'course_name' => $this->whenLoaded('course', $this->course?->name),
            'course_code' => $this->whenLoaded('course', $this->course?->code),
            'max_students' => $this->max_students,
            'fee_amount' => (float) $this->fee_amount,
            'is_active' => (bool) $this->is_active,
            'is_featured' => (bool) $this->is_featured,
            'registration_starts_at' => $this->registration_starts_at?->format('Y-m-d H:i:s'),
            'registration_ends_at' => $this->registration_ends_at?->format('Y-m-d H:i:s'),
            'is_registration_open' => $this->is_registration_open,
            'class_days' => $this->class_days,
            'class_timing' => $this->class_timing,
            'schedule_string' => $this->getScheduleString(),
            'venue' => $this->venue,
            'teacher_id' => $this->teacher_id,
            'teacher_name' => $this->whenLoaded('teacher', $this->teacher?->user?->name),
            'teacher_employee_id' => $this->whenLoaded('teacher', $this->teacher?->employee_id),
            'assistant_teacher_id' => $this->assistant_teacher_id,
            'assistant_teacher_name' => $this->whenLoaded('assistantTeacher', $this->assistantTeacher?->user?->name),
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'notes' => $this->notes,
            'student_count' => $this->when(isset($this->students_count), $this->students_count),
            'available_seats' => $this->available_seats,
            'has_available_seats' => $this->has_available_seats,
            'progress_percentage' => $this->progress_percentage,
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
            'students' => $this->whenLoaded('students', function () {
                return $this->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->user->name,
                        'admission_number' => $student->admission_number,
                        'enrollment_date' => $student->pivot->enrollment_date,
                        'status' => $student->pivot->status,
                        'fee_amount' => (float) $student->pivot->fee_amount,
                        'discount_amount' => (float) $student->pivot->discount_amount,
                        'final_fee_amount' => (float) $student->pivot->final_fee_amount,
                        'payment_status' => $student->pivot->payment_status,
                        'attendance_percentage' => (float) $student->pivot->attendance_percentage,
                    ];
                });
            }),
            'links' => [
                'show' => route('api.batches.show', $this->id),
                'edit' => route('admin.batches.edit', $this->id),
                'students' => route('api.batches.students', $this->id),
                'subjects' => route('api.batches.subjects.index', $this->id),
                'teachers' => route('api.batches.teachers.index', $this->id),
                'attendance' => route('api.batches.attendance.index', $this->id),
                'exams' => route('api.batches.exams.index', $this->id),
                'study_materials' => route('api.batches.study-materials.index', $this->id),
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
                    'enroll_students' => $request->user() ? $request->user()->can('enrollStudents', $this->resource) : false,
                    'manage_students' => $request->user() ? $request->user()->can('manageStudents', $this->resource) : false,
                    'manage_attendance' => $request->user() ? $request->user()->can('manageAttendance', $this->resource) : false,
                    'manage_exams' => $request->user() ? $request->user()->can('manageExams', $this->resource) : false,
                    'manage_study_materials' => $request->user() ? $request->user()->can('manageStudyMaterials', $this->resource) : false,
                    'view_students' => $request->user() ? $request->user()->can('viewStudents', $this->resource) : false,
                ],
            ],
        ];
    }
}
