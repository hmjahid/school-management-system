<?php

namespace App\Http\Controllers;

use App\Http\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    /**
     * Display a listing of the classes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', SchoolClass::class);

        $query = SchoolClass::with(['classTeacher', 'academicSession']);

        // Apply filters
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('academic_session_id')) {
            $query->where('academic_session_id', $request->academic_session_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('grade_level', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'grade_level');
        $sortOrder = $request->input('sort_order', 'asc');
        
        if (in_array($sortField, ['name', 'code', 'grade_level', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        }

        $classes = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => SchoolClassResource::collection($classes),
            'meta' => [
                'total' => $classes->total(),
                'per_page' => $classes->perPage(),
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created class in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', SchoolClass::class);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:school_classes,code',
            'description' => 'nullable|string',
            'grade_level' => 'required|string|max:20',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'class_teacher_id' => 'nullable|exists:teachers,id',
            'max_students' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'monthly_fee' => 'required|numeric|min:0',
            'admission_fee' => 'required|numeric|min:0',
            'exam_fee' => 'required|numeric|min:0',
            'other_fees' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'subjects' => 'sometimes|array',
            'subjects.*' => 'exists:subjects,id',
            'teachers' => 'sometimes|array',
            'teachers.*.teacher_id' => 'exists:teachers,id',
            'teachers.*.is_class_teacher' => 'boolean',
        ]);

        $class = DB::transaction(function () use ($validated) {
            // Create the class
            $class = SchoolClass::create($validated);

            // Attach subjects if provided
            if (!empty($validated['subjects'])) {
                $class->subjects()->attach($validated['subjects'], [
                    'academic_session_id' => $validated['academic_session_id']
                ]);
            }

            // Attach teachers if provided
            if (!empty($validated['teachers'])) {
                $teacherData = [];
                foreach ($validated['teachers'] as $teacher) {
                    $teacherData[$teacher['teacher_id']] = [
                        'is_class_teacher' => $teacher['is_class_teacher'] ?? false,
                        'academic_session_id' => $validated['academic_session_id']
                    ];
                }
                $class->teachers()->attach($teacherData);
            }

            return $class->load(['classTeacher', 'academicSession', 'subjects', 'teachers']);
        });

        return response()->json([
            'message' => 'Class created successfully',
            'data' => new SchoolClassResource($class)
        ], 201);
    }

    /**
     * Display the specified class.
     *
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(SchoolClass $class)
    {
        $this->authorize('view', $class);
        
        return response()->json([
            'data' => new SchoolClassResource($class->load([
                'classTeacher', 
                'academicSession', 
                'subjects', 
                'teachers',
                'sections'
            ]))
        ]);
    }

    /**
     * Update the specified class in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, SchoolClass $class)
    {
        $this->authorize('update', $class);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('school_classes', 'code')->ignore($class->id)
            ],
            'description' => 'nullable|string',
            'grade_level' => 'sometimes|required|string|max:20',
            'academic_session_id' => 'sometimes|required|exists:academic_sessions,id',
            'class_teacher_id' => 'nullable|exists:teachers,id',
            'max_students' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'monthly_fee' => 'sometimes|required|numeric|min:0',
            'admission_fee' => 'sometimes|required|numeric|min:0',
            'exam_fee' => 'sometimes|required|numeric|min:0',
            'other_fees' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'subjects' => 'sometimes|array',
            'subjects.*' => 'exists:subjects,id',
            'teachers' => 'sometimes|array',
            'teachers.*.teacher_id' => 'exists:teachers,id',
            'teachers.*.is_class_teacher' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $class) {
            // Update the class
            $class->update($validated);

            // Sync subjects if provided
            if (isset($validated['subjects'])) {
                $class->subjects()->syncWithPivotValues(
                    $validated['subjects'],
                    ['academic_session_id' => $validated['academic_session_id'] ?? $class->academic_session_id]
                );
            }

            // Sync teachers if provided
            if (isset($validated['teachers'])) {
                $teacherData = [];
                foreach ($validated['teachers'] as $teacher) {
                    $teacherData[$teacher['teacher_id']] = [
                        'is_class_teacher' => $teacher['is_class_teacher'] ?? false,
                        'academic_session_id' => $validated['academic_session_id'] ?? $class->academic_session_id
                    ];
                }
                $class->teachers()->sync($teacherData);
            }
        });

        return response()->json([
            'message' => 'Class updated successfully',
            'data' => new SchoolClassResource($class->fresh()->load([
                'classTeacher', 
                'academicSession', 
                'subjects', 
                'teachers',
                'sections'
            ]))
        ]);
    }

    /**
     * Remove the specified class from storage.
     *
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SchoolClass $class)
    {
        $this->authorize('delete', $class);

        // Check if class has students
        if ($class->students()->exists()) {
            return response()->json([
                'message' => 'Cannot delete class with students. Please remove all students first.'
            ], 422);
        }

        // Check if class has sections
        if ($class->sections()->exists()) {
            return response()->json([
                'message' => 'Cannot delete class with sections. Please remove all sections first.'
            ], 422);
        }

        // Delete related records
        $class->subjects()->detach();
        $class->teachers()->detach();
        
        // Soft delete the class
        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully'
        ]);
    }

    /**
     * Get options for class filters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $this->authorize('viewAny', SchoolClass::class);

        return response()->json([
            'academic_sessions' => AcademicSession::select('id', 'name', 'start_date', 'end_date')
                ->orderBy('start_date', 'desc')
                ->get(),
            'teachers' => Teacher::with('user:id,name')
                ->select('id', 'user_id', 'employee_id')
                ->get()
                ->map(function($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->user->name,
                        'employee_id' => $teacher->employee_id
                    ];
                }),
            'subjects' => Subject::select('id', 'name', 'code')->get(),
            'statuses' => [
                ['value' => '1', 'label' => 'Active'],
                ['value' => '0', 'label' => 'Inactive'],
            ]
        ]);
    }

    /**
     * Get class statistics.
     *
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(SchoolClass $class)
    {
        $this->authorize('view', $class);

        $stats = [
            'total_students' => $class->students()->count(),
            'total_teachers' => $class->teachers()->count(),
            'total_subjects' => $class->subjects()->count(),
            'total_sections' => $class->sections()->count(),
            'male_students' => $class->students()->whereHas('user', function($q) {
                $q->where('gender', 'male');
            })->count(),
            'female_students' => $class->students()->whereHas('user', function($q) {
                $q->where('gender', 'female');
            })->count(),
            'attendance_rate' => 0, // TODO: Implement attendance calculation
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get class timetable.
     *
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTimetable(SchoolClass $class)
    {
        $this->authorize('view', $class);

        // TODO: Implement timetable logic
        $timetable = [];

        return response()->json([
            'data' => $timetable
        ]);
    }

    /**
     * Get class students with their details.
     *
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudents(SchoolClass $class)
    {
        $this->authorize('viewStudents', $class);

        $students = $class->students()
            ->with(['user', 'section'])
            ->orderBy('roll_number')
            ->get();

        return response()->json([
            'data' => $students->map(function($student) {
                return [
                    'id' => $student->id,
                    'user_id' => $student->user_id,
                    'name' => $student->user->name,
                    'roll_number' => $student->roll_number,
                    'admission_number' => $student->admission_number,
                    'section' => $student->section ? [
                        'id' => $student->section->id,
                        'name' => $student->section->name
                    ] : null,
                    'attendance_rate' => 0, // TODO: Implement attendance calculation
                    'last_attendance' => null, // TODO: Implement last attendance
                ];
            })
        ]);
    }
}
