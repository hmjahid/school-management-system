<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Exam;
use Illuminate\Support\Facades\Log;

class TeacherController extends Controller
{
    /**
     * Get all classes for the authenticated teacher with pagination and filtering
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeacherClasses(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'sort_by' => 'sometimes|string|in:name,created_at,academic_year',
            'sort_order' => 'sometimes|string|in:asc,desc',
            'academic_year' => 'sometimes|string',
            'search' => 'sometimes|string|max:255',
            'subject_id' => 'sometimes|exists:subjects,id',
            'section_id' => 'sometimes|exists:sections,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        // Generate cache key based on request parameters
        $cacheKey = 'teacher_classes_' . auth()->id() . '_' . md5(json_encode($request->all()));

        // Use cache with 1 hour TTL
        return Cache::remember($cacheKey, now()->addHour(), function() use ($request) {
            $query = ClassModel::where('teacher_id', auth()->id())
                ->with(['subject', 'section'])
                ->withCount('students');

            // Apply search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('subject', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Apply filters
            if ($request->has('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }

            if ($request->has('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            if ($request->has('section_id')) {
                $query->where('section_id', $request->section_id);
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = $request->input('per_page', 15);
            $classes = $query->paginate($perPage);

            return $this->paginatedResponse(
                $classes,
                'Classes retrieved successfully'
            );
        });
    }

    /**
     * Get students in a specific class with pagination and search
     *
     * @param  int  $classId
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassStudents($classId, Request $request)
    {
        // Validate request
        $validator = Validator::make(
            array_merge($request->all(), ['class_id' => $classId]),
            [
                'class_id' => 'required|exists:classes,id,teacher_id,' . auth()->id(),
                'per_page' => 'sometimes|integer|min:1|max:100',
                'search' => 'sometimes|string|max:255',
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $cacheKey = "class_{$classId}_students_" . md5(json_encode($request->all()));

        return Cache::remember($cacheKey, now()->addMinutes(30), function() use ($classId, $request) {
            $query = Student::where('class_id', $classId)
                ->with(['user', 'class']);

            // Apply search
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Paginate results
            $perPage = $request->input('per_page', 15);
            $students = $query->paginate($perPage);

            return $this->paginatedResponse(
                $students,
                'Students retrieved successfully'
            );
        });
    }

    /**
     * Get grades for students in a specific class with filtering
     *
     * @param  int  $classId
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassGrades($classId, Request $request)
    {
        // Validate request
        $validator = Validator::make(
            array_merge($request->all(), ['class_id' => $classId]),
            [
                'class_id' => 'required|exists:classes,id,teacher_id,' . auth()->id(),
                'exam_id' => 'sometimes|exists:exams,id',
                'subject_id' => 'sometimes|exists:subjects,id',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $cacheKey = "class_{$classId}_grades_" . md5(json_encode($request->all()));

        return Cache::remember($cacheKey, now()->addMinutes(30), function() use ($classId, $request) {
            $query = Grade::where('class_id', $classId)
                ->with(['student.user', 'subject', 'exam']);

            // Apply filters
            if ($request->has('exam_id')) {
                $query->where('exam_id', $request->exam_id);
            }

            if ($request->has('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            // Group by student and calculate overall performance
            $grades = $query->get()
                ->groupBy('student_id')
                ->map(function($studentGrades) {
                    $firstGrade = $studentGrades->first();
                    $totalMarks = $studentGrades->sum('marks_obtained');
                    $maxMarks = $studentGrades->sum('total_marks');
                    $percentage = $maxMarks > 0 ? round(($totalMarks / $maxMarks) * 100, 2) : 0;
                    
                    return [
                        'student_id' => $firstGrade->student_id,
                        'student_name' => $firstGrade->student->user->name,
                        'roll_number' => $firstGrade->student->roll_number,
                        'subjects' => $studentGrades->map(function($grade) {
                            return [
                                'id' => $grade->subject_id,
                                'name' => $grade->subject->name,
                                'marks_obtained' => $grade->marks_obtained,
                                'total_marks' => $grade->total_marks,
                                'grade' => $grade->grade,
                                'exam' => $grade->exam ? $grade->exam->name : 'N/A',
                                'exam_date' => $grade->exam ? $grade->exam->exam_date : null,
                            ];
                        }),
                        'overall_percentage' => $percentage,
                        'overall_grade' => $this->calculateGrade($percentage),
                        'total_marks_obtained' => $totalMarks,
                        'total_max_marks' => $maxMarks,
                    ];
                })->values();

            // Apply pagination
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
            $paginated = $this->paginateCollection($grades, $perPage, $page);

            return $this->paginatedResponse(
                $paginated,
                'Grades retrieved successfully',
                $grades->count(),
                $perPage,
                $page
            );
        });
    }

    /**
     * Helper method to calculate grade based on percentage
     *
     * @param  float  $percentage
     * @return string
     */
    private function calculateGrade($percentage)
    {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        return 'F';
    }

    /**
     * Helper method to paginate a collection
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  int  $perPage
     * @param  int  $page
     * @return \Illuminate\Support\Collection
     */
    private function paginateCollection($collection, $perPage, $page)
    {
        $offset = ($page - 1) * $perPage;
        return $collection->slice($offset, $perPage)->values();
    }

    /**
     * Format a paginated response
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $total
     * @param  int  $perPage
     * @param  int  $currentPage
     * @return \Illuminate\Http\JsonResponse
     */
    private function paginatedResponse($data, $message, $total = null, $perPage = null, $currentPage = null)
    {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];

        if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $response['meta'] = [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ];
            $response['links'] = [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ];
        } elseif (is_array($data) || $data instanceof \Illuminate\Support\Collection) {
            $response['meta'] = [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => ceil($total / $perPage),
                'from' => ($currentPage - 1) * $perPage + 1,
                'to' => min($total, $currentPage * $perPage),
            ];
        }

        return response()->json($response);
    }

    /**
     * Format an error response
     *
     * @param  string  $message
     * @param  array  $errors
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    private function errorResponse($message, $errors = [], $statusCode = 400)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
