<?php

namespace App\Http\Controllers;

use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    /**
     * Display a listing of the sections.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Section::class);

        $query = Section::with(['class', 'teacher.user', 'academicSession']);

        // Apply filters
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('academic_session_id')) {
            $query->where('academic_session_id', $request->academic_session_id);
        }

        if ($request->has('has_available_seats')) {
            if (filter_var($request->has_available_seats, FILTER_VALIDATE_BOOLEAN)) {
                $query->whereRaw('capacity > (SELECT COUNT(*) FROM students WHERE section_id = sections.id)');
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('room_number', 'like', "%{$search}%")
                  ->orWhereHas('class', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        
        if (in_array($sortField, ['name', 'code', 'room_number', 'capacity', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        } elseif ($sortField === 'class') {
            $query->join('school_classes', 'sections.class_id', '=', 'school_classes.id')
                  ->orderBy('school_classes.name', $sortOrder)
                  ->select('sections.*');
        } elseif ($sortField === 'teacher') {
            $query->join('teachers', 'sections.teacher_id', '=', 'teachers.id')
                  ->join('users', 'teachers.user_id', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder)
                  ->select('sections.*');
        }

        $sections = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => SectionResource::collection($sections),
            'meta' => [
                'total' => $sections->total(),
                'per_page' => $sections->perPage(),
                'current_page' => $sections->currentPage(),
                'last_page' => $sections->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created section in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Section::class);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:sections,code',
            'class_id' => 'required|exists:school_classes,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'capacity' => 'required|integer|min:1',
            'room_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'subjects' => 'sometimes|array',
            'subjects.*.subject_id' => 'exists:subjects,id',
            'subjects.*.teacher_id' => 'required_with:subjects.*.subject_id|exists:teachers,id',
        ]);

        $section = DB::transaction(function () use ($validated) {
            // Create the section
            $section = Section::create(collect($validated)->except('subjects')->toArray());

            // Attach subjects if provided
            if (!empty($validated['subjects'])) {
                $subjectData = [];
                foreach ($validated['subjects'] as $subject) {
                    $subjectData[$subject['subject_id']] = [
                        'teacher_id' => $subject['teacher_id'],
                        'academic_session_id' => $validated['academic_session_id'],
                    ];
                }
                $section->subjects()->attach($subjectData);
            }

            return $section->load(['class', 'teacher.user', 'academicSession', 'subjects']);
        });

        return response()->json([
            'message' => 'Section created successfully',
            'data' => new SectionResource($section)
        ], 201);
    }

    /**
     * Display the specified section.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Section $section)
    {
        $this->authorize('view', $section);
        
        return response()->json([
            'data' => new SectionResource($section->load([
                'class', 
                'teacher.user', 
                'academicSession', 
                'subjects',
                'teachers.user'
            ]))
        ]);
    }

    /**
     * Update the specified section in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Section $section)
    {
        $this->authorize('update', $section);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('sections', 'code')->ignore($section->id)
            ],
            'class_id' => 'sometimes|required|exists:school_classes,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'academic_session_id' => 'sometimes|required|exists:academic_sessions,id',
            'capacity' => 'sometimes|required|integer|min:1',
            'room_number' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
            'subjects' => 'sometimes|array',
            'subjects.*.subject_id' => 'exists:subjects,id',
            'subjects.*.teacher_id' => 'required_with:subjects.*.subject_id|exists:teachers,id',
        ]);

        DB::transaction(function () use ($validated, $section) {
            // Update the section
            $section->update(collect($validated)->except('subjects')->toArray());

            // Sync subjects if provided
            if (isset($validated['subjects'])) {
                $subjectData = [];
                foreach ($validated['subjects'] as $subject) {
                    $subjectData[$subject['subject_id']] = [
                        'teacher_id' => $subject['teacher_id'],
                        'academic_session_id' => $validated['academic_session_id'] ?? $section->academic_session_id,
                    ];
                }
                $section->subjects()->sync($subjectData);
            }
        });

        return response()->json([
            'message' => 'Section updated successfully',
            'data' => new SectionResource($section->fresh()->load([
                'class', 
                'teacher.user', 
                'academicSession', 
                'subjects',
                'teachers.user'
            ]))
        ]);
    }

    /**
     * Remove the specified section from storage.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Section $section)
    {
        $this->authorize('delete', $section);

        // Check if section has any students
        if ($section->students()->exists()) {
            return response()->json([
                'message' => 'Cannot delete section with students. Please move or delete the students first.'
            ], 422);
        }

        // Check if section has any attendances
        if ($section->attendances()->exists()) {
            return response()->json([
                'message' => 'Cannot delete section with attendance records.'
            ], 422);
        }

        // Check if section has any exam results
        if ($section->examResults()->exists()) {
            return response()->json([
                'message' => 'Cannot delete section with exam results.'
            ], 422);
        }

        // Detach relationships
        $section->subjects()->detach();
        $section->teachers()->detach();
        
        // Soft delete the section
        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully'
        ]);
    }

    /**
     * Get options for section filters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $this->authorize('viewAny', Section::class);

        return response()->json([
            'statuses' => [
                ['value' => '1', 'label' => 'Active'],
                ['value' => '0', 'label' => 'Inactive'],
            ],
            'has_available_seats' => [
                ['value' => '1', 'label' => 'With Available Seats'],
                ['value' => '0', 'label' => 'Full'],
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
            'academic_sessions' => $this->getAcademicSessions(),
        ]);
    }

    /**
     * Get section statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics()
    {
        $this->authorize('viewAny', Section::class);

        $stats = [
            'total_sections' => Section::count(),
            'active_sections' => Section::where('is_active', true)->count(),
            'sections_with_available_seats' => DB::table('sections')
                ->select(DB::raw('COUNT(*) as count'))
                ->whereRaw('capacity > (SELECT COUNT(*) FROM students WHERE section_id = sections.id)')
                ->first()->count,
            'sections_by_class' => SchoolClass::withCount('sections')
                ->get()
                ->map(function($class) {
                    return [
                        'class' => $class->name,
                        'sections_count' => $class->sections_count
                    ];
                }),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get available subjects for section assignment.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableSubjects(Section $section = null)
    {
        $this->authorize('manageSubjects', $section ?? Section::class);

        $query = Subject::query();

        if ($section) {
            // Get subjects that are in the section's class but not already assigned
            $assignedSubjectIds = $section->subjects->pluck('id');
            $query->whereHas('classes', function($q) use ($section) {
                $q->where('class_id', $section->class_id);
            })->whereNotIn('id', $assignedSubjectIds);
        }

        $subjects = $query->select('id', 'name', 'code', 'type')
            ->orderBy('name')
            ->get()
            ->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'type' => $subject->type,
                ];
            });

        return response()->json([
            'data' => $subjects
        ]);
    }

    /**
     * Get available teachers for section assignment.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableTeachers(Section $section = null)
    {
        $this->authorize('manageTeachers', $section ?? Section::class);

        $query = Teacher::with('user:id,name');

        if ($section) {
            // Get teachers who are not already assigned as class teacher
            $query->where('id', '!=', $section->teacher_id);
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
     * Get academic sessions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAcademicSessions()
    {
        // TODO: Replace with actual academic session model query
        return collect([
            ['id' => 1, 'name' => '2023-2024', 'is_current' => true],
            ['id' => 2, 'name' => '2024-2025', 'is_current' => false],
        ]);
    }

    /**
     * Get students in the section.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudents(Section $section)
    {
        $this->authorize('viewStudents', $section);

        $students = $section->students()
            ->with(['user', 'guardians.user'])
            ->orderBy('roll_number')
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'admission_number' => $student->admission_number,
                    'roll_number' => $student->roll_number,
                    'guardians' => $student->guardians->map(function($guardian) {
                        return [
                            'id' => $guardian->id,
                            'name' => $guardian->user->name,
                            'phone' => $guardian->phone,
                            'relationship' => $guardian->pivot->relationship,
                        ];
                    }),
                    'attendance_rate' => 0, // TODO: Implement attendance calculation
                ];
            });

        return response()->json([
            'data' => $students
        ]);
    }
}
