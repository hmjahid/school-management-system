<?php

namespace App\Http\Controllers;

use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    /**
     * Display a listing of the batches.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Batch::class);

        $query = Batch::with(['course', 'teacher.user', 'assistantTeacher.user'])
            ->withCount('students');

        // Apply filters
        if ($request->has('status')) {
            $status = $request->status;
            if (in_array($status, [
                Batch::STATUS_UPCOMING, 
                Batch::STATUS_ONGOING, 
                Batch::STATUS_COMPLETED, 
                Batch::STATUS_CANCELLED
            ])) {
                $query->where('status', $status);
            }
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->has('teacher_id')) {
            $query->where(function($q) use ($request) {
                $q->where('teacher_id', $request->teacher_id)
                  ->orWhere('assistant_teacher_id', $request->teacher_id);
            });
        }

        if ($request->has('has_available_seats')) {
            if (filter_var($request->has_available_seats, FILTER_VALIDATE_BOOLEAN)) {
                $query->where(function($q) {
                    $q->whereNull('max_students')
                      ->orWhereRaw('max_students > (SELECT COUNT(*) FROM batch_student WHERE batch_student.batch_id = batches.id)');
                });
            } else {
                $query->whereRaw('max_students <= (SELECT COUNT(*) FROM batch_student WHERE batch_student.batch_id = batches.id)');
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('course', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'start_date');
        $sortOrder = $request->input('sort_order', 'desc');
        
        if (in_array($sortField, ['name', 'code', 'start_date', 'end_date', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        } elseif ($sortField === 'course') {
            $query->join('courses', 'batches.course_id', '=', 'courses.id')
                  ->orderBy('courses.name', $sortOrder)
                  ->select('batches.*');
        } elseif ($sortField === 'teacher') {
            $query->join('teachers', 'batches.teacher_id', '=', 'teachers.id')
                  ->join('users', 'teachers.user_id', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder)
                  ->select('batches.*');
        } elseif ($sortField === 'student_count') {
            $query->orderBy('students_count', $sortOrder);
        }

        $batches = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => BatchResource::collection($batches),
            'meta' => [
                'total' => $batches->total(),
                'per_page' => $batches->perPage(),
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created batch in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Batch::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:batches,code',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'course_id' => 'required|exists:courses,id',
            'max_students' => 'nullable|integer|min:1',
            'fee_amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'registration_starts_at' => 'nullable|date|before_or_equal:registration_ends_at',
            'registration_ends_at' => 'nullable|date|after_or_equal:registration_starts_at',
            'class_days' => 'required|array|min:1',
            'class_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'class_timing' => 'required|array',
            'class_timing.start_time' => 'required|date_format:H:i',
            'class_timing.end_time' => 'required|date_format:H:i|after:class_timing.start_time',
            'venue' => 'nullable|string|max:255',
            'teacher_id' => 'required|exists:teachers,id',
            'assistant_teacher_id' => 'nullable|exists:teachers,id|different:teacher_id',
            'status' => 'in:upcoming,ongoing,completed,cancelled',
            'notes' => 'nullable|string',
            'subjects' => 'sometimes|array',
            'subjects.*.subject_id' => 'exists:subjects,id',
            'subjects.*.teacher_id' => 'required_with:subjects.*.subject_id|exists:teachers,id',
        ]);

        $batch = DB::transaction(function () use ($validated) {
            // Create the batch
            $batch = Batch::create(collect($validated)->except('subjects')->toArray());

            // Attach subjects if provided
            if (!empty($validated['subjects'])) {
                $subjectData = [];
                foreach ($validated['subjects'] as $subject) {
                    $subjectData[$subject['subject_id']] = [
                        'teacher_id' => $subject['teacher_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $batch->subjects()->attach($subjectData);
            }

            return $batch->load(['course', 'teacher.user', 'assistantTeacher.user', 'subjects']);
        });

        return response()->json([
            'message' => 'Batch created successfully',
            'data' => new BatchResource($batch)
        ], 201);
    }

    /**
     * Display the specified batch.
     *
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Batch $batch)
    {
        $this->authorize('view', $batch);
        
        return response()->json([
            'data' => new BatchResource($batch->load([
                'course', 
                'teacher.user', 
                'assistantTeacher.user', 
                'academicSession',
                'subjects',
                'students.user'
            ]))
        ]);
    }

    /**
     * Update the specified batch in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Batch $batch)
    {
        $this->authorize('update', $batch);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('batches', 'code')->ignore($batch->id)
            ],
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'academic_session_id' => 'sometimes|required|exists:academic_sessions,id',
            'course_id' => 'sometimes|required|exists:courses,id',
            'max_students' => 'nullable|integer|min:1',
            'fee_amount' => 'sometimes|required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'registration_starts_at' => 'nullable|date|before_or_equal:registration_ends_at',
            'registration_ends_at' => 'nullable|date|after_or_equal:registration_starts_at',
            'class_days' => 'sometimes|array|min:1',
            'class_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'class_timing' => 'sometimes|array',
            'class_timing.start_time' => 'required_with:class_timing|date_format:H:i',
            'class_timing.end_time' => 'required_with:class_timing|date_format:H:i|after:class_timing.start_time',
            'venue' => 'nullable|string|max:255',
            'teacher_id' => 'sometimes|required|exists:teachers,id',
            'assistant_teacher_id' => 'nullable|exists:teachers,id|different:teacher_id',
            'status' => 'sometimes|in:upcoming,ongoing,completed,cancelled',
            'notes' => 'nullable|string',
            'subjects' => 'sometimes|array',
            'subjects.*.subject_id' => 'exists:subjects,id',
            'subjects.*.teacher_id' => 'required_with:subjects.*.subject_id|exists:teachers,id',
        ]);

        DB::transaction(function () use ($validated, $batch) {
            // Update the batch
            $batch->update(collect($validated)->except('subjects')->toArray());

            // Sync subjects if provided
            if (isset($validated['subjects'])) {
                $subjectData = [];
                foreach ($validated['subjects'] as $subject) {
                    $subjectData[$subject['subject_id']] = [
                        'teacher_id' => $subject['teacher_id'],
                        'updated_at' => now(),
                    ];
                }
                $batch->subjects()->sync($subjectData);
            }
        });

        return response()->json([
            'message' => 'Batch updated successfully',
            'data' => new BatchResource($batch->fresh()->load([
                'course', 
                'teacher.user', 
                'assistantTeacher.user', 
                'academicSession',
                'subjects',
                'students.user'
            ]))
        ]);
    }

    /**
     * Remove the specified batch from storage.
     *
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Batch $batch)
    {
        $this->authorize('delete', $batch);

        // Check if batch has any students
        if ($batch->students()->exists()) {
            return response()->json([
                'message' => 'Cannot delete batch with enrolled students. Please remove students first.'
            ], 422);
        }

        // Check if batch has any attendances
        if ($batch->attendances()->exists()) {
            return response()->json([
                'message' => 'Cannot delete batch with attendance records.'
            ], 422);
        }

        // Check if batch has any exams
        if ($batch->exams()->exists()) {
            return response()->json([
                'message' => 'Cannot delete batch with exam records.'
            ], 422);
        }

        // Detach relationships
        $batch->subjects()->detach();
        
        // Soft delete the batch
        $batch->delete();

        return response()->json([
            'message' => 'Batch deleted successfully'
        ]);
    }

    /**
     * Get options for batch filters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $this->authorize('viewAny', Batch::class);

        return response()->json([
            'statuses' => [
                ['value' => Batch::STATUS_UPCOMING, 'label' => 'Upcoming'],
                ['value' => Batch::STATUS_ONGOING, 'label' => 'Ongoing'],
                ['value' => Batch::STATUS_COMPLETED, 'label' => 'Completed'],
                ['value' => Batch::STATUS_CANCELLED, 'label' => 'Cancelled'],
            ],
            'statuses_with_all' => array_merge(
                [['value' => '', 'label' => 'All Statuses']],
                [
                    ['value' => Batch::STATUS_UPCOMING, 'label' => 'Upcoming'],
                    ['value' => Batch::STATUS_ONGOING, 'label' => 'Ongoing'],
                    ['value' => Batch::STATUS_COMPLETED, 'label' => 'Completed'],
                    ['value' => Batch::STATUS_CANCELLED, 'label' => 'Cancelled'],
                ]
            ),
            'boolean_options' => [
                ['value' => '1', 'label' => 'Yes'],
                ['value' => '0', 'label' => 'No'],
            ],
            'has_available_seats' => [
                ['value' => '1', 'label' => 'With Available Seats'],
                ['value' => '0', 'label' => 'Full'],
            ],
            'courses' => Course::active()
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get()
                ->map(function($course) {
                    return [
                        'value' => $course->id,
                        'label' => $course->name . ' (' . $course->code . ')'
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
     * Get batch statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics()
    {
        $this->authorize('viewAny', Batch::class);

        $stats = [
            'total_batches' => Batch::count(),
            'active_batches' => Batch::where('is_active', true)->count(),
            'upcoming_batches' => Batch::where('status', Batch::STATUS_UPCOMING)->count(),
            'ongoing_batches' => Batch::where('status', Batch::STATUS_ONGOING)->count(),
            'completed_batches' => Batch::where('status', Batch::STATUS_COMPLETED)->count(),
            'cancelled_batches' => Batch::where('status', Batch::STATUS_CANCELLED)->count(),
            'featured_batches' => Batch::where('is_featured', true)->count(),
            'batches_with_available_seats' => DB::table('batches')
                ->select(DB::raw('COUNT(*) as count'))
                ->where(function($q) {
                    $q->whereNull('max_students')
                      ->orWhereRaw('max_students > (SELECT COUNT(*) FROM batch_student WHERE batch_student.batch_id = batches.id)');
                })
                ->where('is_active', true)
                ->first()->count,
            'batches_by_status' => [
                ['status' => 'Upcoming', 'count' => Batch::where('status', Batch::STATUS_UPCOMING)->count()],
                ['status' => 'Ongoing', 'count' => Batch::where('status', Batch::STATUS_ONGOING)->count()],
                ['status' => 'Completed', 'count' => Batch::where('status', Batch::STATUS_COMPLETED)->count()],
                ['status' => 'Cancelled', 'count' => Batch::where('status', Batch::STATUS_CANCELLED)->count()],
            ],
            'batches_by_course' => Course::withCount('batches')
                ->get()
                ->map(function($course) {
                    return [
                        'course' => $course->name,
                        'batches_count' => $course->batches_count
                    ];
                }),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get available subjects for batch assignment.
     *
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableSubjects(Batch $batch = null)
    {
        $this->authorize('manageSubjects', $batch ?? Batch::class);

        $query = Subject::query();

        if ($batch) {
            // Get subjects that are not already assigned to the batch
            $assignedSubjectIds = $batch->subjects->pluck('id');
            $query->whereNotIn('id', $assignedSubjectIds);
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
     * Get available teachers for batch assignment.
     *
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableTeachers(Batch $batch = null)
    {
        $this->authorize('manageTeachers', $batch ?? Batch::class);

        $query = Teacher::with('user:id,name');

        if ($batch) {
            // Get teachers who are not already assigned as main or assistant teacher
            $query->where('id', '!=', $batch->teacher_id)
                  ->where('id', '!=', $batch->assistant_teacher_id);
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
     * Enroll a student in the batch.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\JsonResponse
     */
    public function enrollStudent(Request $request, Batch $batch)
    {
        $this->authorize('enrollStudents', $batch);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'enrollment_date' => 'required|date|before_or_equal:today',
            'fee_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0|max:' . $request->fee_amount,
            'notes' => 'nullable|string',
        ]);

        // Check if batch has available seats
        if ($batch->max_students !== null && $batch->students_count >= $batch->max_students) {
            return response()->json([
                'message' => 'Batch is full. No available seats.'
            ], 422);
        }

        // Check if student is already enrolled
        if ($batch->students()->where('student_id', $validated['student_id'])->exists()) {
            return response()->json([
                'message' => 'Student is already enrolled in this batch.'
            ], 422);
        }

        // Calculate final fee amount
        $finalFeeAmount = $validated['fee_amount'] - ($validated['discount_amount'] ?? 0);

        // Enroll the student
        $batch->students()->attach($validated['student_id'], [
            'enrollment_date' => $validated['enrollment_date'],
            'status' => 'enrolled',
            'fee_amount' => $validated['fee_amount'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'final_fee_amount' => $finalFeeAmount,
            'payment_status' => 'unpaid',
            'created_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Student enrolled successfully',
            'data' => [
                'batch_id' => $batch->id,
                'student_id' => $validated['student_id'],
                'enrollment_date' => $validated['enrollment_date'],
                'fee_amount' => $validated['fee_amount'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'final_fee_amount' => $finalFeeAmount,
            ]
        ], 201);
    }

    /**
     * Remove a student from the batch.
     *
     * @param  \App\Models\Batch  $batch
     * @param  int  $studentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeStudent(Batch $batch, $studentId)
    {
        $this->authorize('manageStudents', $batch);

        $student = Student::findOrFail($studentId);

        // Check if student is enrolled in the batch
        if (!$batch->students()->where('student_id', $studentId)->exists()) {
            return response()->json([
                'message' => 'Student is not enrolled in this batch.'
            ], 422);
        }

        // Check if student has any payments or attendances
        if ($batch->payments()->where('student_id', $studentId)->exists()) {
            return response()->json([
                'message' => 'Cannot remove student with payment records. Please delete payments first.'
            ], 422);
        }

        if ($batch->attendances()->where('student_id', $studentId)->exists()) {
            return response()->json([
                'message' => 'Cannot remove student with attendance records.'
            ], 422);
        }

        // Remove the student from the batch
        $batch->students()->detach($studentId);

        return response()->json([
            'message' => 'Student removed from batch successfully'
        ]);
    }

    /**
     * Get students in the batch.
     *
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudents(Batch $batch)
    {
        $this->authorize('viewStudents', $batch);

        $students = $batch->students()
            ->with(['user', 'guardians.user'])
            ->orderBy('batch_student.enrollment_date', 'desc')
            ->get()
            ->map(function($student) use ($batch) {
                $pivot = $student->pivot;
                return [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'admission_number' => $student->admission_number,
                    'enrollment_date' => $pivot->enrollment_date,
                    'status' => $pivot->status,
                    'fee_amount' => (float) $pivot->fee_amount,
                    'discount_amount' => (float) $pivot->discount_amount,
                    'final_fee_amount' => (float) $pivot->final_fee_amount,
                    'payment_status' => $pivot->payment_status,
                    'attendance_percentage' => (float) $pivot->attendance_percentage,
                    'guardians' => $student->guardians->map(function($guardian) {
                        return [
                            'id' => $guardian->id,
                            'name' => $guardian->user->name,
                            'phone' => $guardian->phone,
                            'relationship' => $guardian->pivot->relationship,
                        ];
                    }),
                    'payments' => [
                        'total_paid' => $batch->payments()
                            ->where('student_id', $student->id)
                            ->sum('amount'),
                        'total_due' => $pivot->final_fee_amount - 
                            $batch->payments()
                                ->where('student_id', $student->id)
                                ->sum('amount'),
                    ],
                ];
            });

        return response()->json([
            'data' => $students
        ]);
    }

    /**
     * Get academic sessions.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getAcademicSessions()
    {
        // TODO: Replace with actual academic session model query
        return collect([
            ['id' => 1, 'name' => '2023-2024', 'is_current' => true],
            ['id' => 2, 'name' => '2024-2025', 'is_current' => false],
        ]);
    }
}
