<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * Display a listing of the subjects.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Subject::class);

        $query = Subject::query();

        // Apply filters
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_elective')) {
            $query->where('is_elective', filter_var($request->is_elective, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('has_lab')) {
            $query->where('has_lab', filter_var($request->has_lab, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('class_id')) {
            $query->whereHas('classes', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        if ($request->has('teacher_id')) {
            $query->whereHas('teachers', function($q) use ($request) {
                $q->where('teacher_id', $request->teacher_id);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        
        if (in_array($sortField, ['name', 'code', 'type', 'created_at', 'priority'])) {
            $query->orderBy($sortField, $sortOrder);
        }

        $subjects = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => SubjectResource::collection($subjects),
            'meta' => [
                'total' => $subjects->total(),
                'per_page' => $subjects->perPage(),
                'current_page' => $subjects->currentPage(),
                'last_page' => $subjects->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created subject in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Subject::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code',
            'type' => 'required|in:theory,practical,both',
            'short_name' => 'nullable|string|max:20',
            'credit_hours' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_elective' => 'boolean',
            'has_lab' => 'boolean',
            'theory_marks' => 'nullable|numeric|min:0',
            'practical_marks' => 'nullable|numeric|min:0',
            'passing_marks' => 'nullable|numeric|min:0',
            'max_class_per_week' => 'nullable|integer|min:1',
            'priority' => 'nullable|integer',
            'notes' => 'nullable|string',
            'classes' => 'sometimes|array',
            'classes.*.class_id' => 'exists:school_classes,id',
            'classes.*.is_compulsory' => 'boolean',
            'classes.*.max_weekly_classes' => 'nullable|integer|min:1',
            'teachers' => 'sometimes|array',
            'teachers.*.teacher_id' => 'exists:teachers,id',
            'teachers.*.is_primary' => 'boolean',
        ]);

        $subject = DB::transaction(function () use ($validated) {
            // Create the subject
            $subject = Subject::create(collect($validated)->except(['classes', 'teachers'])->toArray());

            // Attach classes if provided
            if (!empty($validated['classes'])) {
                $classData = [];
                foreach ($validated['classes'] as $class) {
                    $classData[$class['class_id']] = [
                        'is_compulsory' => $class['is_compulsory'] ?? true,
                        'max_weekly_classes' => $class['max_weekly_classes'] ?? null,
                        'academic_session_id' => $this->getCurrentAcademicSessionId(),
                    ];
                }
                $subject->classes()->attach($classData);
            }

            // Attach teachers if provided
            if (!empty($validated['teachers'])) {
                $teacherData = [];
                foreach ($validated['teachers'] as $teacher) {
                    $teacherData[$teacher['teacher_id']] = [
                        'is_primary' => $teacher['is_primary'] ?? false,
                        'academic_session_id' => $this->getCurrentAcademicSessionId(),
                    ];
                }
                $subject->teachers()->attach($teacherData);
            }

            return $subject->load(['classes', 'teachers']);
        });

        return response()->json([
            'message' => 'Subject created successfully',
            'data' => new SubjectResource($subject)
        ], 201);
    }

    /**
     * Display the specified subject.
     *
     * @param  \App\Models\Subject  $subject
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Subject $subject)
    {
        $this->authorize('view', $subject);
        
        return response()->json([
            'data' => new SubjectResource($subject->load(['classes', 'teachers.user', 'syllabus']))
        ]);
    }

    /**
     * Update the specified subject in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Subject  $subject
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Subject $subject)
    {
        $this->authorize('update', $subject);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'code')->ignore($subject->id)
            ],
            'type' => 'sometimes|required|in:theory,practical,both',
            'short_name' => 'nullable|string|max:20',
            'credit_hours' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_elective' => 'sometimes|boolean',
            'has_lab' => 'sometimes|boolean',
            'theory_marks' => 'nullable|numeric|min:0',
            'practical_marks' => 'nullable|numeric|min:0',
            'passing_marks' => 'nullable|numeric|min:0',
            'max_class_per_week' => 'nullable|integer|min:1',
            'priority' => 'nullable|integer',
            'notes' => 'nullable|string',
            'classes' => 'sometimes|array',
            'classes.*.class_id' => 'exists:school_classes,id',
            'classes.*.is_compulsory' => 'boolean',
            'classes.*.max_weekly_classes' => 'nullable|integer|min:1',
            'teachers' => 'sometimes|array',
            'teachers.*.teacher_id' => 'exists:teachers,id',
            'teachers.*.is_primary' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $subject) {
            // Update the subject
            $subject->update(collect($validated)->except(['classes', 'teachers'])->toArray());

            // Sync classes if provided
            if (isset($validated['classes'])) {
                $classData = [];
                foreach ($validated['classes'] as $class) {
                    $classData[$class['class_id']] = [
                        'is_compulsory' => $class['is_compulsory'] ?? true,
                        'max_weekly_classes' => $class['max_weekly_classes'] ?? null,
                        'academic_session_id' => $this->getCurrentAcademicSessionId(),
                    ];
                }
                $subject->classes()->sync($classData);
            }

            // Sync teachers if provided
            if (isset($validated['teachers'])) {
                $teacherData = [];
                foreach ($validated['teachers'] as $teacher) {
                    $teacherData[$teacher['teacher_id']] = [
                        'is_primary' => $teacher['is_primary'] ?? false,
                        'academic_session_id' => $this->getCurrentAcademicSessionId(),
                    ];
                }
                $subject->teachers()->sync($teacherData);
            }
        });

        return response()->json([
            'message' => 'Subject updated successfully',
            'data' => new SubjectResource($subject->fresh()->load(['classes', 'teachers.user', 'syllabus']))
        ]);
    }

    /**
     * Remove the specified subject from storage.
     *
     * @param  \App\Models\Subject  $subject
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Subject $subject)
    {
        $this->authorize('delete', $subject);

        // Check if subject has any related records
        if ($subject->examResults()->exists()) {
            return response()->json([
                'message' => 'Cannot delete subject with exam results.'
            ], 422);
        }

        if ($subject->attendances()->exists()) {
            return response()->json([
                'message' => 'Cannot delete subject with attendance records.'
            ], 422);
        }

        // Detach relationships
        $subject->classes()->detach();
        $subject->teachers()->detach();
        
        // Soft delete the subject
        $subject->delete();

        return response()->json([
            'message' => 'Subject deleted successfully'
        ]);
    }

    /**
     * Get options for subject filters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $this->authorize('viewAny', Subject::class);

        return response()->json([
            'types' => [
                ['value' => 'theory', 'label' => 'Theory'],
                ['value' => 'practical', 'label' => 'Practical'],
                ['value' => 'both', 'label' => 'Both'],
            ],
            'statuses' => [
                ['value' => '1', 'label' => 'Active'],
                ['value' => '0', 'label' => 'Inactive'],
            ],
            'classes' => SchoolClass::active()
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get()
                ->map(function($class) {
                    return [
                        'value' => $class->id,
                        'label' => $class->name . ' (' . $class->code . ')'
                    ];
                }),
            'teachers' => Teacher::with('user:id,name')
                ->select('id', 'user_id', 'employee_id')
                ->get()
                ->map(function($teacher) {
                    return [
                        'value' => $teacher->id,
                        'label' => $teacher->user->name . ' (' . $teacher->employee_id . ')'
                    ];
                }),
        ]);
    }

    /**
     * Get subject statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics()
    {
        $this->authorize('viewAny', Subject::class);

        $stats = [
            'total_subjects' => Subject::count(),
            'active_subjects' => Subject::where('is_active', true)->count(),
            'theory_subjects' => Subject::where('type', 'theory')->count(),
            'practical_subjects' => Subject::where('type', 'practical')->count(),
            'combined_subjects' => Subject::where('type', 'both')->count(),
            'elective_subjects' => Subject::where('is_elective', true)->count(),
            'subjects_with_lab' => Subject::where('has_lab', true)->count(),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get available classes for subject assignment.
     *
     * @param  \App\Models\Subject  $subject
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableClasses(Subject $subject = null)
    {
        $this->authorize('manageClasses', $subject ?? Subject::class);

        $query = SchoolClass::query();

        if ($subject) {
            $query->whereNotIn('id', $subject->classes()->pluck('class_id'));
        }

        $classes = $query->active()
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get()
            ->map(function($class) {
                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'code' => $class->code,
                ];
            });

        return response()->json([
            'data' => $classes
        ]);
    }

    /**
     * Get available teachers for subject assignment.
     *
     * @param  \App\Models\Subject  $subject
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableTeachers(Subject $subject = null)
    {
        $this->authorize('manageTeachers', $subject ?? Subject::class);

        $query = Teacher::with('user:id,name');

        if ($subject) {
            $query->whereNotIn('id', $subject->teachers()->pluck('teacher_id'));
        }

        $teachers = $query->select('id', 'user_id', 'employee_id')
            ->get()
            ->map(function($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->user->name,
                    'employee_id' => $teacher->employee_id,
                ];
            });

        return response()->json([
            'data' => $teachers
        ]);
    }

    /**
     * Get the current academic session ID.
     * 
     * @return int
     */
    protected function getCurrentAcademicSessionId(): int
    {
        // TODO: Implement logic to get the current academic session ID
        // For now, return 1 as a fallback
        return 1;
    }
}
