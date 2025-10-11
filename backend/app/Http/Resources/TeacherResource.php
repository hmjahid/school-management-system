<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'employee_id' => $this->employee_id,
            'qualification' => $this->qualification,
            'gender' => $this->gender,
            'blood_group' => $this->blood_group,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->date_of_birth?->age,
            'religion' => $this->religion,
            'nationality' => $this->nationality,
            'phone' => $this->phone,
            'emergency_contact' => $this->emergency_contact,
            'present_address' => $this->present_address,
            'permanent_address' => $this->permanent_address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'full_address' => $this->full_address,
            'joining_date' => $this->joining_date->format('Y-m-d'),
            'leaving_date' => $this->leaving_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_branch' => $this->bank_branch,
            'salary' => (float) $this->salary,
            'salary_type' => $this->salary_type,
            'nid_number' => $this->nid_number,
            'passport_number' => $this->passport_number,
            'driving_license' => $this->driving_license,
            'notes' => $this->notes,
            'subjects' => $this->whenLoaded('subjects', function () {
                return $this->subjects->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'code' => $subject->code,
                    ];
                });
            }),
            'classes' => $this->whenLoaded('classes', function () {
                return $this->classes->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'code' => $class->code,
                        'is_class_teacher' => (bool) $class->pivot->is_class_teacher,
                        'academic_session_id' => $class->pivot->academic_session_id,
                    ];
                });
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at->format('Y-m-d H:i:s');
            }),
            'links' => [
                'profile' => route('api.teachers.show', $this->id),
                'edit' => route('admin.teachers.edit', $this->id),
                'attendance' => route('api.teachers.attendance.index', $this->id),
                'salary' => route('api.teachers.salaries.index', $this->id),
                'subjects' => route('api.teachers.subjects.index', $this->id),
                'classes' => route('api.teachers.classes.index', $this->id),
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
                    'view_attendance' => $request->user() ? $request->user()->can('viewAttendance', $this->resource) : false,
                    'view_salary' => $request->user() ? $request->user()->can('viewSalary', $this->resource) : false,
                    'manage_subjects' => $request->user() ? $request->user()->can('manageSubjects', $this->resource) : false,
                    'manage_classes' => $request->user() ? $request->user()->can('manageClassAssignments', $this->resource) : false,
                ],
            ],
        ];
    }
}
