<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
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
            'type' => $this->type,
            'type_badge' => $this->type_badge,
            'short_name' => $this->short_name,
            'credit_hours' => (float) $this->credit_hours,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'is_elective' => (bool) $this->is_elective,
            'has_lab' => (bool) $this->has_lab,
            'theory_marks' => (float) $this->theory_marks,
            'practical_marks' => (float) $this->practical_marks,
            'total_marks' => $this->total_marks,
            'passing_marks' => (float) $this->passing_marks,
            'max_class_per_week' => $this->max_class_per_week,
            'priority' => $this->priority,
            'notes' => $this->notes,
            'status' => $this->is_active ? 'active' : 'inactive',
            'status_badge' => $this->status_badge,
            'class_count' => $this->when(isset($this->class_count), $this->class_count),
            'teacher_count' => $this->when(isset($this->teacher_count), $this->teacher_count),
            'student_count' => $this->when(isset($this->student_count), $this->student_count),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at->format('Y-m-d H:i:s');
            }),
            'classes' => $this->whenLoaded('classes', function () {
                return $this->classes->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'code' => $class->code,
                        'is_compulsory' => (bool) $class->pivot->is_compulsory,
                        'max_weekly_classes' => $class->pivot->max_weekly_classes,
                        'teacher' => $class->pivot->teacher_id ? [
                            'id' => $class->pivot->teacher->id,
                            'name' => $class->pivot->teacher->user->name,
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
                        'is_primary' => (bool) $teacher->pivot->is_primary,
                    ];
                });
            }),
            'syllabus' => $this->whenLoaded('syllabus', function () {
                return $this->syllabus->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'description' => $item->description,
                        'file_path' => $item->file_path,
                        'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    ];
                });
            }),
            'links' => [
                'show' => route('api.subjects.show', $this->id),
                'edit' => route('admin.subjects.edit', $this->id),
                'classes' => route('api.subjects.classes.index', $this->id),
                'teachers' => route('api.subjects.teachers.index', $this->id),
                'syllabus' => route('api.subjects.syllabus.index', $this->id),
                'study_materials' => route('api.subjects.study-materials.index', $this->id),
                'assignments' => route('api.subjects.assignments.index', $this->id),
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
                    'manage_classes' => $request->user() ? $request->user()->can('manageClasses', $this->resource) : false,
                    'manage_teachers' => $request->user() ? $request->user()->can('manageTeachers', $this->resource) : false,
                    'manage_syllabus' => $request->user() ? $request->user()->can('manageSyllabus', $this->resource) : false,
                    'manage_study_materials' => $request->user() ? $request->user()->can('manageStudyMaterials', $this->resource) : false,
                    'manage_assignments' => $request->user() ? $request->user()->can('manageAssignments', $this->resource) : false,
                ],
            ],
        ];
    }
}
