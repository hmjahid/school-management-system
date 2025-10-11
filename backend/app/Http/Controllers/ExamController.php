<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\Batch;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    /**
     * Display a listing of the exams.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Exam::class);

        $query = Exam::with([
            'academicSession',
            'batch',
            'section',
            'subject',
            'createdBy',
            'updatedBy',
            'teachers',
        ]);

        // Apply filters
        if ($request->has('status')) {
            if ($request->status === 'upcoming') {
                $query->upcoming();
            } elseif ($request->status === 'ongoing') {
                $query->ongoing();
            } elseif ($request->status === 'completed') {
                $query->completed();
            } elseif ($request->status === 'published') {
                $query->published();
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_published')) {
            $query->where('is_published', filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('academic_session_id')) {
            $query->where('academic_session_id', $request->academic_session_id);
        }

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('teacher_id')) {
            $query->whereHas('teachers', function($q) use ($request) {
                $q->where('teacher_id', $request->teacher_id);
            });
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_date', [
                $request->start_date,
                $request->end_date,
            ]);
        } elseif ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        } elseif ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        // Apply search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('batch', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('section', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('subject', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'start_date');
        $sortOrder = $request->input('sort_order', 'desc');
        
        if (in_array($sortField, ['name', 'code', 'type', 'status', 'start_date', 'end_date', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        } elseif ($sortField === 'batch') {
            $query->join('batches', 'exams.batch_id', '=', 'batches.id')
                  ->orderBy('batches.name', $sortOrder)
                  ->select('exams.*');
        } elseif ($sortField === 'section') {
            $query->join('sections', 'exams.section_id', '=', 'sections.id')
                  ->orderBy('sections.name', $sortOrder)
                  ->select('exams.*');
        } elseif ($sortField === 'subject') {
            $query->join('subjects', 'exams.subject_id', '=', 'subjects.id')
                  ->orderBy('subjects.name', $sortOrder)
                  ->select('exams.*');
        } elseif ($sortField === 'created_by') {
            $query->join('users', 'exams.created_by', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder)
                  ->select('exams.*');
        }

        $perPage = $request->per_page ?? 20;
        $exams = $query->paginate($perPage);

        return response()->json([
            'data' => ExamResource::collection($exams),
            'meta' => [
                'total' => $exams->total(),
                'per_page' => $exams->perPage(),
                'current_page' => $exams->currentPage(),
                'last_page' => $exams->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created exam in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Exam::class);

        $validated = $this->validateExamData($request);

        // Set default grading scale if not provided
        if (!isset($validated['grading_scale']) || empty($validated['grading_scale'])) {
            $validated['grading_scale'] = Exam::getDefaultGradingScale();
        }

        // Set created_by and updated_by
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        // Handle exam creation in transaction
        $exam = DB::transaction(function () use ($validated, $request) {
            $exam = Exam::create($validated);

            // Attach teachers if provided
            if (isset($validated['teachers'])) {
                $this->syncTeachers($exam, $validated['teachers']);
            }

            return $exam->load([
                'academicSession',
                'batch',
                'section',
                'subject',
                'createdBy',
                'updatedBy',
                'teachers',
            ]);
        });

        return response()->json([
            'message' => 'Exam created successfully',
            'data' => new ExamResource($exam)
        ], 201);
    }

    /**
     * Display the specified exam.
     *
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Exam $exam)
    {
        $this->authorize('view', $exam);
        
        $exam->load([
            'academicSession',
            'batch',
            'section',
            'subject',
            'createdBy',
            'updatedBy',
            'teachers.user',
            'questions',
            'schedule',
        ]);

        return response()->json([
            'data' => new ExamResource($exam)
        ]);
    }

    /**
     * Update the specified exam in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Exam $exam)
    {
        $this->authorize('update', $exam);

        // Prevent updating if exam is published
        if ($exam->is_published) {
            return response()->json([
                'message' => 'Cannot update a published exam.',
            ], 403);
        }

        $validated = $this->validateExamData($request, $exam->id);

        // Update exam in transaction
        $exam = DB::transaction(function () use ($exam, $validated) {
            $exam->update($validated);

            // Sync teachers if provided
            if (isset($validated['teachers'])) {
                $this->syncTeachers($exam, $validated['teachers']);
            }

            return $exam->load([
                'academicSession',
                'batch',
                'section',
                'subject',
                'createdBy',
                'updatedBy',
                'teachers',
            ]);
        });

        return response()->json([
            'message' => 'Exam updated successfully',
            'data' => new ExamResource($exam)
        ]);
    }

    /**
     * Remove the specified exam from storage.
     *
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Exam $exam)
    {
        $this->authorize('delete', $exam);

        // Prevent deleting if exam has results
        if ($exam->results()->exists()) {
            return response()->json([
                'message' => 'Cannot delete exam with existing results.',
            ], 422);
        }

        // Delete related records in transaction
        DB::transaction(function () use ($exam) {
            // Delete exam questions
            $exam->questions()->delete();
            
            // Delete exam schedule if exists
            if ($exam->schedule) {
                $exam->schedule()->delete();
            }
            
            // Detach teachers
            $exam->teachers()->detach();
            
            // Delete the exam
            $exam->delete();
        });

        return response()->json([
            'message' => 'Exam deleted successfully',
        ]);
    }

    /**
     * Publish the specified exam.
     *
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Http\JsonResponse
     */
    public function publish(Exam $exam)
    {
        $this->authorize('publish', $exam);

        if ($exam->is_published) {
            return response()->json([
                'message' => 'Exam is already published.',
            ], 422);
        }

        // Validate if exam can be published
        if ($exam->status !== Exam::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Only completed exams can be published.',
            ], 422);
        }

        // Update exam status and publish date
        $exam->update([
            'status' => Exam::STATUS_PUBLISHED,
            'is_published' => true,
            'publish_date' => now(),
            'publish_remarks' => 'Published by ' . auth()->user()->name,
            'updated_by' => auth()->id(),
        ]);

        // Notify students and parents about the published results
        $this->notifyPublishedResults($exam);

        return response()->json([
            'message' => 'Exam results published successfully',
            'data' => new ExamResource($exam->load(['academicSession', 'batch', 'section', 'subject'])),
        ]);
    }

    /**
     * Unpublish the specified exam.
     *
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Http\JsonResponse
     */
    public function unpublish(Exam $exam)
    {
        $this->authorize('unpublish', $exam);

        if (!$exam->is_published) {
            return response()->json([
                'message' => 'Exam is not published.',
            ], 422);
        }

        // Update exam status and unpublish
        $exam->update([
            'status' => Exam::STATUS_COMPLETED,
            'is_published' => false,
            'publish_remarks' => 'Unpublished by ' . auth()->user()->name,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Exam results unpublished successfully',
            'data' => new ExamResource($exam->load(['academicSession', 'batch', 'section', 'subject'])),
        ]);
    }

    /**
     * Get exam statistics.
     *
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Exam $exam)
    {
        $this->authorize('viewStatistics', $exam);

        $statistics = $exam->getStatistics();

        return response()->json([
            'data' => $statistics,
            'exam' => new ExamResource($exam->load(['academicSession', 'batch', 'section', 'subject'])),
        ]);
    }

    /**
     * Get filter options for exams.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $this->authorize('viewAny', Exam::class);

        $batches = Batch::active()
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get()
            ->map(function($batch) {
                return [
                    'value' => $batch->id,
                    'label' => $batch->name . ' (' . $batch->code . ')'
                ];
            });

        $sections = Section::active()
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get()
            ->map(function($section) {
                return [
                    'value' => $section->id,
                    'label' => $section->name . ' (' . $section->code . ')'
                ];
            });

        $subjects = Subject::active()
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get()
            ->map(function($subject) {
                return [
                    'value' => $subject->id,
                    'label' => $subject->name . ' (' . $subject->code . ')'
                ];
            });

        $teachers = Teacher::with('user')
            ->select('id', 'user_id', 'employee_id')
            ->get()
            ->map(function($teacher) {
                return [
                    'value' => $teacher->id,
                    'label' => $teacher->user->name . ' (' . $teacher->employee_id . ')'
                ];
            });

        $academicSessions = [
            ['value' => 1, 'label' => '2023-2024'],
            ['value' => 2, 'label' => '2024-2025'],
        ];

        return response()->json([
            'statuses' => [
                ['value' => 'all', 'label' => 'All Statuses'],
                ['value' => 'upcoming', 'label' => 'Upcoming'],
                ['value' => 'ongoing', 'label' => 'Ongoing'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'published', 'label' => 'Published'],
                ...collect(Exam::getStatuses())->map(function($label, $value) {
                    return ['value' => $value, 'label' => $label];
                })->values()->toArray(),
            ],
            'types' => collect(Exam::getTypes())->map(function($label, $value) {
                return [
                    'value' => $value,
                    'label' => $label,
                ];
            })->values(),
            'grading_types' => collect(Exam::getGradingTypes())->map(function($label, $value) {
                return [
                    'value' => $value,
                    'label' => $label,
                ];
            })->values(),
            'batches' => $batches,
            'sections' => $sections,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'academic_sessions' => $academicSessions,
            'date_ranges' => [
                ['value' => 'today', 'label' => 'Today'],
                ['value' => 'yesterday', 'label' => 'Yesterday'],
                ['value' => 'this_week', 'label' => 'This Week'],
                ['value' => 'last_week', 'label' => 'Last Week'],
                ['value' => 'this_month', 'label' => 'This Month'],
                ['value' => 'last_month', 'label' => 'Last Month'],
                ['value' => 'this_year', 'label' => 'This Year'],
                ['value' => 'last_year', 'label' => 'Last Year'],
            ],
        ]);
    }

    /**
     * Validate exam data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $examId
     * @return array
     */
    protected function validateExamData(Request $request, $examId = null)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('exams', 'code')->ignore($examId),
            ],
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(array_keys(Exam::getTypes()))],
            'status' => ['required', Rule::in(array_keys(Exam::getStatuses()))],
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration' => 'required|integer|min:1',
            'total_marks' => 'required|numeric|min:0',
            'passing_marks' => 'required|numeric|min:0|lte:total_marks',
            'grading_type' => ['required', Rule::in(array_keys(Exam::getGradingTypes()))],
            'grading_scale' => 'nullable|array',
            'weightage' => 'required|numeric|min:0|max:100',
            'is_published' => 'boolean',
            'publish_date' => 'nullable|date',
            'publish_remarks' => 'nullable|string|max:500',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'batch_id' => 'required_without:section_id|exists:batches,id',
            'section_id' => 'required_without:batch_id|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teachers' => 'nullable|array',
            'teachers.*.id' => 'required|exists:teachers,id',
            'teachers.*.is_chief_examiner' => 'boolean',
            'teachers.*.is_observer' => 'boolean',
            'teachers.*.responsibilities' => 'nullable|string|max:500',
        ];

        return $request->validate($rules);
    }

    /**
     * Sync teachers for the exam.
     *
     * @param  \App\Models\Exam  $exam
     * @param  array  $teachers
     * @return void
     */
    protected function syncTeachers(Exam $exam, array $teachers)
    {
        $teacherData = [];
        
        foreach ($teachers as $teacher) {
            $teacherData[$teacher['id']] = [
                'is_chief_examiner' => $teacher['is_chief_examiner'] ?? false,
                'is_observer' => $teacher['is_observer'] ?? false,
                'responsibilities' => $teacher['responsibilities'] ?? null,
            ];
        }
        
        $exam->teachers()->sync($teacherData);
    }

    /**
     * Notify students and parents about published results.
     *
     * @param  \App\Models\Exam  $exam
     * @return void
     */
    protected function notifyPublishedResults(Exam $exam)
    {
        // Get all students who took the exam
        $students = $exam->results()
            ->with(['student.user', 'student.guardians.user'])
            ->get()
            ->map(function($result) {
                return [
                    'student' => $result->student,
                    'guardians' => $result->student->guardians,
                    'result' => $result,
                ];
            });

        // Send notifications to students and parents
        foreach ($students as $data) {
            $student = $data['student'];
            $result = $data['result'];
            
            // Notify student
            if ($student->user) {
                // Example: Send notification to student
                // $student->user->notify(new ExamResultPublished($exam, $result));
            }
            
            // Notify guardians
            foreach ($data['guardians'] as $guardian) {
                if ($guardian->user) {
                    // Example: Send notification to guardian
                    // $guardian->user->notify(new StudentExamResultPublished($student, $exam, $result));
                }
            }
        }
        
        // Notify teachers
        foreach ($exam->teachers as $teacher) {
            if ($teacher->user) {
                // Example: Send notification to teacher
                // $teacher->user->notify(new ExamResultsPublished($exam));
            }
        }
    }
}
