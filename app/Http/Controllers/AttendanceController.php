<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the attendance records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::with([
            'student.user',
            'batch',
            'section',
            'subject',
            'teacher.user',
            'recordedBy',
            'updatedBy',
        ]);

        // Apply filters
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
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

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('academic_session_id')) {
            $query->where('academic_session_id', $request->academic_session_id);
        }

        // Apply search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('student.user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
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
        $sortField = $request->input('sort_field', 'date');
        $sortOrder = $request->input('sort_order', 'desc');
        
        if (in_array($sortField, ['date', 'status', 'type', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        } elseif ($sortField === 'student') {
            $query->join('students', 'attendances.student_id', '=', 'students.id')
                  ->join('users', 'students.user_id', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder)
                  ->select('attendances.*');
        } elseif ($sortField === 'batch') {
            $query->join('batches', 'attendances.batch_id', '=', 'batches.id')
                  ->orderBy('batches.name', $sortOrder)
                  ->select('attendances.*');
        } elseif ($sortField === 'section') {
            $query->join('sections', 'attendances.section_id', '=', 'sections.id')
                  ->orderBy('sections.name', $sortOrder)
                  ->select('attendances.*');
        } elseif ($sortField === 'subject') {
            $query->join('subjects', 'attendances.subject_id', '=', 'subjects.id')
                  ->orderBy('subjects.name', $sortOrder)
                  ->select('attendances.*');
        } elseif ($sortField === 'teacher') {
            $query->join('teachers', 'attendances.teacher_id', '=', 'teachers.id')
                  ->join('users', 'teachers.user_id', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder)
                  ->select('attendances.*');
        }

        $perPage = $request->per_page ?? 20;
        $attendances = $query->paginate($perPage);

        return response()->json([
            'data' => AttendanceResource::collection($attendances),
            'meta' => [
                'total' => $attendances->total(),
                'per_page' => $attendances->perPage(),
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created attendance record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Attendance::class);

        $validated = $request->validate([
            'date' => 'required|date',
            'status' => ['required', Rule::in(Attendance::getStatuses())],
            'type' => ['required', Rule::in(Attendance::getTypes())],
            'batch_id' => 'required_without:section_id|exists:batches,id',
            'section_id' => 'required_without:batch_id|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teachers,id',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'period' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        // Check for duplicate attendance
        $existingAttendance = Attendance::where('date', $validated['date'])
            ->where('student_id', $validated['student_id'])
            ->when(isset($validated['batch_id']), function($q) use ($validated) {
                return $q->where('batch_id', $validated['batch_id']);
            })
            ->when(isset($validated['section_id']), function($q) use ($validated) {
                return $q->where('section_id', $validated['section_id']);
            })
            ->when(isset($validated['subject_id']), function($q) use ($validated) {
                return $q->where('subject_id', $validated['subject_id']);
            })
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Attendance already recorded for this student on the selected date.',
                'data' => new AttendanceResource($existingAttendance)
            ], 422);
        }

        // Set the recorded_by field
        $validated['recorded_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $attendance = Attendance::create($validated);

        // Update student's attendance percentage
        $this->updateStudentAttendancePercentage($validated['student_id'], $validated);

        return response()->json([
            'message' => 'Attendance recorded successfully',
            'data' => new AttendanceResource($attendance->load([
                'student.user',
                'batch',
                'section',
                'subject',
                'teacher.user',
                'recordedBy',
                'updatedBy',
            ]))
        ], 201);
    }

    /**
     * Display the specified attendance record.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Attendance $attendance)
    {
        $this->authorize('view', $attendance);
        
        return response()->json([
            'data' => new AttendanceResource($attendance->load([
                'student.user',
                'batch',
                'section',
                'subject',
                'teacher.user',
                'recordedBy',
                'updatedBy',
                'academicSession',
            ]))
        ]);
    }

    /**
     * Update the specified attendance record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Attendance $attendance)
    {
        $this->authorize('update', $attendance);

        $validated = $request->validate([
            'date' => 'sometimes|required|date',
            'status' => ['sometimes', 'required', Rule::in(Attendance::getStatuses())],
            'type' => ['sometimes', 'required', Rule::in(Attendance::getTypes())],
            'batch_id' => 'sometimes|required_without:section_id|exists:batches,id',
            'section_id' => 'sometimes|required_without:batch_id|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'student_id' => 'sometimes|required|exists:students,id',
            'teacher_id' => 'sometimes|required|exists:teachers,id',
            'academic_session_id' => 'sometimes|required|exists:academic_sessions,id',
            'period' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        // Check for duplicate attendance if date or student is being updated
        if ($request->has('date') || $request->has('student_id')) {
            $existingAttendance = Attendance::where('id', '!=', $attendance->id)
                ->where('date', $validated['date'] ?? $attendance->date)
                ->where('student_id', $validated['student_id'] ?? $attendance->student_id)
                ->when(isset($validated['batch_id']) || $attendance->batch_id, function($q) use ($validated, $attendance) {
                    return $q->where('batch_id', $validated['batch_id'] ?? $attendance->batch_id);
                })
                ->when(isset($validated['section_id']) || $attendance->section_id, function($q) use ($validated, $attendance) {
                    return $q->where('section_id', $validated['section_id'] ?? $attendance->section_id);
                })
                ->when(isset($validated['subject_id']) || $attendance->subject_id, function($q) use ($validated, $attendance) {
                    return $q->where('subject_id', $validated['subject_id'] ?? $attendance->subject_id);
                })
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'message' => 'Attendance already recorded for this student on the selected date.',
                    'data' => new AttendanceResource($existingAttendance)
                ], 422);
            }
        }

        // Set the updated_by field
        $validated['updated_by'] = auth()->id();

        $oldStudentId = $attendance->student_id;
        $oldBatchId = $attendance->batch_id;
        $oldSectionId = $attendance->section_id;

        $attendance->update($validated);

        // Update student's attendance percentage for both old and new records
        if (isset($validated['student_id']) && $validated['student_id'] != $oldStudentId) {
            $this->updateStudentAttendancePercentage($oldStudentId, [
                'batch_id' => $oldBatchId,
                'section_id' => $oldSectionId,
            ]);
        }
        $this->updateStudentAttendancePercentage(
            $validated['student_id'] ?? $attendance->student_id,
            [
                'batch_id' => $validated['batch_id'] ?? $attendance->batch_id,
                'section_id' => $validated['section_id'] ?? $attendance->section_id,
            ]
        );

        return response()->json([
            'message' => 'Attendance updated successfully',
            'data' => new AttendanceResource($attendance->fresh([
                'student.user',
                'batch',
                'section',
                'subject',
                'teacher.user',
                'recordedBy',
                'updatedBy',
            ]))
        ]);
    }

    /**
     * Remove the specified attendance record from storage.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Attendance $attendance)
    {
        $this->authorize('delete', $attendance);

        $studentId = $attendance->student_id;
        $batchId = $attendance->batch_id;
        $sectionId = $attendance->section_id;

        $attendance->delete();

        // Update student's attendance percentage
        $this->updateStudentAttendancePercentage($studentId, [
            'batch_id' => $batchId,
            'section_id' => $sectionId,
        ]);

        return response()->json([
            'message' => 'Attendance record deleted successfully'
        ]);
    }

    /**
     * Record attendance for multiple students at once.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(Request $request)
    {
        $this->authorize('create', Attendance::class);

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => ['required', Rule::in(Attendance::getTypes())],
            'batch_id' => 'required_without:section_id|exists:batches,id',
            'section_id' => 'required_without:batch_id|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'period' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:500',
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => ['required', Rule::in(Attendance::getStatuses())],
            'attendances.*.remarks' => 'nullable|string|max:500',
        ]);

        $recordedBy = auth()->id();
        $attendanceData = [];
        $studentIds = [];
        $batchId = $validated['batch_id'] ?? null;
        $sectionId = $validated['section_id'] ?? null;
        $date = $validated['date'];

        // Check for existing attendance records for the same date and students
        $existingAttendances = Attendance::where('date', $date)
            ->whereIn('student_id', collect($validated['attendances'])->pluck('student_id'))
            ->when($batchId, function($q) use ($batchId) {
                return $q->where('batch_id', $batchId);
            })
            ->when($sectionId, function($q) use ($sectionId) {
                return $q->where('section_id', $sectionId);
            })
            ->when(isset($validated['subject_id']), function($q) use ($validated) {
                return $q->where('subject_id', $validated['subject_id']);
            })
            ->pluck('student_id')
            ->toArray();

        if (!empty($existingAttendances)) {
            return response()->json([
                'message' => 'Attendance already recorded for some students on the selected date.',
                'data' => [
                    'existing_student_ids' => $existingAttendances,
                ]
            ], 422);
        }

        // Prepare attendance data for bulk insert
        foreach ($validated['attendances'] as $attendance) {
            $attendanceData[] = [
                'date' => $date,
                'status' => $attendance['status'],
                'type' => $validated['type'],
                'batch_id' => $batchId,
                'section_id' => $sectionId,
                'subject_id' => $validated['subject_id'] ?? null,
                'student_id' => $attendance['student_id'],
                'teacher_id' => $validated['teacher_id'],
                'academic_session_id' => $validated['academic_session_id'],
                'period' => $validated['period'] ?? null,
                'remarks' => $attendance['remarks'] ?? $validated['remarks'] ?? null,
                'recorded_by' => $recordedBy,
                'updated_by' => $recordedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $studentIds[] = $attendance['student_id'];
        }

        // Bulk insert attendance records
        DB::table('attendances')->insert($attendanceData);

        // Update attendance percentages for affected students
        foreach (array_unique($studentIds) as $studentId) {
            $this->updateStudentAttendancePercentage($studentId, [
                'batch_id' => $batchId,
                'section_id' => $sectionId,
            ]);
        }

        return response()->json([
            'message' => count($attendanceData) . ' attendance records created successfully',
        ], 201);
    }

    /**
     * Get attendance summary for a batch or section.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSummary(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        $validated = $request->validate([
            'batch_id' => 'required_without:section_id|exists:batches,id',
            'section_id' => 'required_without:batch_id|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'student_id' => 'nullable|exists:students,id',
        ]);

        $query = Attendance::query();

        if (isset($validated['batch_id'])) {
            $query->where('batch_id', $validated['batch_id']);
        }

        if (isset($validated['section_id'])) {
            $query->where('section_id', $validated['section_id']);
        }

        if (isset($validated['subject_id'])) {
            $query->where('subject_id', $validated['subject_id']);
        }

        if (isset($validated['student_id'])) {
            $query->where('student_id', $validated['student_id']);
        }

        $query->whereBetween('date', [$validated['start_date'], $validated['end_date']]);

        $total = $query->count();
        $present = $query->clone()->whereIn('status', [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_LATE,
            Attendance::STATUS_HALF_DAY
        ])->count();
        $absent = $query->clone()->where('status', Attendance::STATUS_ABSENT)->count();
        $late = $query->clone()->where('status', Attendance::STATUS_LATE)->count();
        $halfDay = $query->clone()->where('status', Attendance::STATUS_HALF_DAY)->count();
        $onLeave = $query->clone()->where('status', Attendance::STATUS_LEAVE)->count();
        $holiday = $query->clone()->where('status', Attendance::STATUS_HOLIDAY)->count();

        $summary = [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'half_day' => $halfDay,
            'on_leave' => $onLeave,
            'holiday' => $holiday,
            'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];

        // If student_id is provided, add detailed attendance records
        if (isset($validated['student_id'])) {
            $student = Student::with('user')->findOrFail($validated['student_id']);
            $summary['student'] = [
                'id' => $student->id,
                'name' => $student->user->name,
                'admission_number' => $student->admission_number,
                'roll_number' => $student->roll_number,
            ];

            $summary['attendance_records'] = $query->clone()
                ->with(['batch', 'section', 'subject', 'teacher.user'])
                ->orderBy('date', 'desc')
                ->get()
                ->map(function($record) {
                    return [
                        'id' => $record->id,
                        'date' => $record->date->format('Y-m-d'),
                        'status' => $record->status,
                        'status_badge' => $record->status_badge,
                        'type' => $record->type,
                        'batch' => $record->batch ? [
                            'id' => $record->batch->id,
                            'name' => $record->batch->name,
                            'code' => $record->batch->code,
                        ] : null,
                        'section' => $record->section ? [
                            'id' => $record->section->id,
                            'name' => $record->section->name,
                        ] : null,
                        'subject' => $record->subject ? [
                            'id' => $record->subject->id,
                            'name' => $record->subject->name,
                            'code' => $record->subject->code,
                        ] : null,
                        'teacher' => $record->teacher ? [
                            'id' => $record->teacher->id,
                            'name' => $record->teacher->user->name,
                        ] : null,
                        'remarks' => $record->remarks,
                        'created_at' => $record->created_at->format('Y-m-d H:i:s'),
                    ];
                });
        }

        return response()->json([
            'data' => $summary
        ]);
    }

    /**
     * Get attendance statistics for a batch or section.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        $validated = $request->validate([
            'batch_id' => 'required_without:section_id|exists:batches,id',
            'section_id' => 'required_without:batch_id|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:day,week,month,year,status,student,teacher',
        ]);

        $query = Attendance::query();

        if (isset($validated['batch_id'])) {
            $query->where('batch_id', $validated['batch_id']);
        }

        if (isset($validated['section_id'])) {
            $query->where('section_id', $validated['section_id']);
        }

        if (isset($validated['subject_id'])) {
            $query->where('subject_id', $validated['subject_id']);
        }

        $query->whereBetween('date', [$validated['start_date'], $validated['end_date']]);

        $groupBy = $validated['group_by'] ?? null;
        $statistics = [];

        if ($groupBy === 'day') {
            $statistics = $this->getStatisticsByDay($query);
        } elseif ($groupBy === 'week') {
            $statistics = $this->getStatisticsByWeek($query);
        } elseif ($groupBy === 'month') {
            $statistics = $this->getStatisticsByMonth($query);
        } elseif ($groupBy === 'year') {
            $statistics = $this->getStatisticsByYear($query);
        } elseif ($groupBy === 'status') {
            $statistics = $this->getStatisticsByStatus($query);
        } elseif ($groupBy === 'student') {
            $statistics = $this->getStatisticsByStudent($query);
        } elseif ($groupBy === 'teacher') {
            $statistics = $this->getStatisticsByTeacher($query);
        } else {
            // Default: overall summary
            $total = $query->count();
            $present = $query->clone()->whereIn('status', [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_LATE,
                Attendance::STATUS_HALF_DAY
            ])->count();

            $statistics = [
                'total' => $total,
                'present' => $present,
                'absent' => $query->clone()->where('status', Attendance::STATUS_ABSENT)->count(),
                'late' => $query->clone()->where('status', Attendance::STATUS_LATE)->count(),
                'half_day' => $query->clone()->where('status', Attendance::STATUS_HALF_DAY)->count(),
                'on_leave' => $query->clone()->where('status', Attendance::STATUS_LEAVE)->count(),
                'holiday' => $query->clone()->where('status', Attendance::STATUS_HOLIDAY)->count(),
                'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            ];
        }

        return response()->json([
            'data' => $statistics,
            'meta' => [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'group_by' => $groupBy,
                'batch_id' => $validated['batch_id'] ?? null,
                'section_id' => $validated['section_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
            ]
        ]);
    }

    /**
     * Get attendance statistics grouped by day.
     */
    protected function getStatisticsByDay($query)
    {
        return $query->select(
                DB::raw('DATE(date) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("present", "late", "half_day") THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status = "half_day" THEN 1 ELSE 0 END) as half_day'),
                DB::raw('SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as on_leave'),
                DB::raw('SUM(CASE WHEN status = "holiday" THEN 1 ELSE 0 END) as holiday')
            )
            ->groupBy(DB::raw('DATE(date)'))
            ->orderBy('date', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'total' => (int) $item->total,
                    'present' => (int) $item->present,
                    'absent' => (int) $item->absent,
                    'late' => (int) $item->late,
                    'half_day' => (int) $item->half_day,
                    'on_leave' => (int) $item->on_leave,
                    'holiday' => (int) $item->holiday,
                    'attendance_percentage' => $item->total > 0 ? round(($item->present / $item->total) * 100, 2) : 0,
                ];
            });
    }

    /**
     * Get attendance statistics grouped by week.
     */
    protected function getStatisticsByWeek($query)
    {
        return $query->select(
                DB::raw('YEAR(date) as year'),
                DB::raw('WEEK(date, 1) as week'),
                DB::raw('MIN(date) as week_start'),
                DB::raw('MAX(date) as week_end'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("present", "late", "half_day") THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status = "half_day" THEN 1 ELSE 0 END) as half_day'),
                DB::raw('SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as on_leave'),
                DB::raw('SUM(CASE WHEN status = "holiday" THEN 1 ELSE 0 END) as holiday')
            )
            ->groupBy(DB::raw('YEAR(date)'), DB::raw('WEEK(date, 1)'))
            ->orderBy('year', 'desc')
            ->orderBy('week', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'year' => (int) $item->year,
                    'week' => (int) $item->week,
                    'week_start' => $item->week_start,
                    'week_end' => $item->week_end,
                    'total' => (int) $item->total,
                    'present' => (int) $item->present,
                    'absent' => (int) $item->absent,
                    'late' => (int) $item->late,
                    'half_day' => (int) $item->half_day,
                    'on_leave' => (int) $item->on_leave,
                    'holiday' => (int) $item->holiday,
                    'attendance_percentage' => $item->total > 0 ? round(($item->present / $item->total) * 100, 2) : 0,
                ];
            });
    }

    /**
     * Get attendance statistics grouped by month.
     */
    protected function getStatisticsByMonth($query)
    {
        return $query->select(
                DB::raw('YEAR(date) as year'),
                DB::raw('MONTH(date) as month'),
                DB::raw('DATE_FORMAT(date, "%Y-%m-01") as month_start'),
                DB::raw('LAST_DAY(date) as month_end'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("present", "late", "half_day") THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status = "half_day" THEN 1 ELSE 0 END) as half_day'),
                DB::raw('SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as on_leave'),
                DB::raw('SUM(CASE WHEN status = "holiday" THEN 1 ELSE 0 END) as holiday')
            )
            ->groupBy(DB::raw('YEAR(date)'), DB::raw('MONTH(date)'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'year' => (int) $item->year,
                    'month' => (int) $item->month,
                    'month_name' => date('F', mktime(0, 0, 0, $item->month, 1)),
                    'month_start' => $item->month_start,
                    'month_end' => $item->month_end,
                    'total' => (int) $item->total,
                    'present' => (int) $item->present,
                    'absent' => (int) $item->absent,
                    'late' => (int) $item->late,
                    'half_day' => (int) $item->half_day,
                    'on_leave' => (int) $item->on_leave,
                    'holiday' => (int) $item->holiday,
                    'attendance_percentage' => $item->total > 0 ? round(($item->present / $item->total) * 100, 2) : 0,
                ];
            });
    }

    /**
     * Get attendance statistics grouped by year.
     */
    protected function getStatisticsByYear($query)
    {
        return $query->select(
                DB::raw('YEAR(date) as year'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("present", "late", "half_day") THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status = "half_day" THEN 1 ELSE 0 END) as half_day'),
                DB::raw('SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as on_leave'),
                DB::raw('SUM(CASE WHEN status = "holiday" THEN 1 ELSE 0 END) as holiday')
            )
            ->groupBy(DB::raw('YEAR(date)'))
            ->orderBy('year', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'year' => (int) $item->year,
                    'total' => (int) $item->total,
                    'present' => (int) $item->present,
                    'absent' => (int) $item->absent,
                    'late' => (int) $item->late,
                    'half_day' => (int) $item->half_day,
                    'on_leave' => (int) $item->on_leave,
                    'holiday' => (int) $item->holiday,
                    'attendance_percentage' => $item->total > 0 ? round(($item->present / $item->total) * 100, 2) : 0,
                ];
            });
    }

    /**
     * Get attendance statistics grouped by status.
     */
    protected function getStatisticsByStatus($query)
    {
        return $query->select(
                'status',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'status' => $item->status,
                    'status_label' => str_replace('_', ' ', ucfirst($item->status)),
                    'count' => (int) $item->count,
                ];
            });
    }

    /**
     * Get attendance statistics grouped by student.
     */
    protected function getStatisticsByStudent($query)
    {
        return $query->select(
                'student_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("present", "late", "half_day") THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status = "half_day" THEN 1 ELSE 0 END) as half_day'),
                DB::raw('SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as on_leave'),
                DB::raw('SUM(CASE WHEN status = "holiday" THEN 1 ELSE 0 END) as holiday')
            )
            ->with(['student.user'])
            ->groupBy('student_id')
            ->orderBy('present', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'student_id' => $item->student_id,
                    'student_name' => $item->student->user->name ?? 'Unknown',
                    'admission_number' => $item->student->admission_number ?? null,
                    'total' => (int) $item->total,
                    'present' => (int) $item->present,
                    'absent' => (int) $item->absent,
                    'late' => (int) $item->late,
                    'half_day' => (int) $item->half_day,
                    'on_leave' => (int) $item->on_leave,
                    'holiday' => (int) $item->holiday,
                    'attendance_percentage' => $item->total > 0 ? round(($item->present / $item->total) * 100, 2) : 0,
                ];
            });
    }

    /**
     * Get attendance statistics grouped by teacher.
     */
    protected function getStatisticsByTeacher($query)
    {
        return $query->select(
                'teacher_id',
                DB::raw('COUNT(DISTINCT student_id) as total_students'),
                DB::raw('COUNT(*) as total_attendance'),
                DB::raw('SUM(CASE WHEN status IN ("present", "late", "half_day") THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status = "half_day" THEN 1 ELSE 0 END) as half_day'),
                DB::raw('SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as on_leave'),
                DB::raw('SUM(CASE WHEN status = "holiday" THEN 1 ELSE 0 END) as holiday')
            )
            ->with(['teacher.user'])
            ->groupBy('teacher_id')
            ->orderBy('total_attendance', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'teacher_id' => $item->teacher_id,
                    'teacher_name' => $item->teacher->user->name ?? 'Unknown',
                    'employee_id' => $item->teacher->employee_id ?? null,
                    'total_students' => (int) $item->total_students,
                    'total_attendance' => (int) $item->total_attendance,
                    'present' => (int) $item->present,
                    'absent' => (int) $item->absent,
                    'late' => (int) $item->late,
                    'half_day' => (int) $item->half_day,
                    'on_leave' => (int) $item->on_leave,
                    'holiday' => (int) $item->holiday,
                    'attendance_percentage' => $item->total_attendance > 0 ? round(($item->present / $item->total_attendance) * 100, 2) : 0,
                ];
            });
    }

    /**
     * Get filter options for attendance.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $this->authorize('viewAny', Attendance::class);

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
            'statuses' => collect(Attendance::getStatuses())->map(function($label, $value) {
                return [
                    'value' => $value,
                    'label' => $label,
                ];
            })->values(),
            'types' => collect(Attendance::getTypes())->map(function($label, $value) {
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
            'group_by_options' => [
                ['value' => 'day', 'label' => 'Day'],
                ['value' => 'week', 'label' => 'Week'],
                ['value' => 'month', 'label' => 'Month'],
                ['value' => 'year', 'label' => 'Year'],
                ['value' => 'status', 'label' => 'Status'],
                ['value' => 'student', 'label' => 'Student'],
                ['value' => 'teacher', 'label' => 'Teacher'],
            ],
        ]);
    }

    /**
     * Update student's attendance percentage in the batch/section.
     *
     * @param int $studentId
     * @param array $params
     * @return void
     */
    protected function updateStudentAttendancePercentage($studentId, $params = [])
    {
        $query = Attendance::where('student_id', $studentId);

        if (isset($params['batch_id'])) {
            $query->where('batch_id', $params['batch_id']);
        }

        if (isset($params['section_id'])) {
            $query->where('section_id', $params['section_id']);
        }

        $total = $query->count();
        $present = $query->clone()
            ->whereIn('status', [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_LATE,
                Attendance::STATUS_HALF_DAY
            ])
            ->count();

        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

        // Update the student's attendance percentage in the batch/section
        if (isset($params['batch_id'])) {
            DB::table('batch_student')
                ->where('student_id', $studentId)
                ->where('batch_id', $params['batch_id'])
                ->update(['attendance_percentage' => $percentage]);
        }

        if (isset($params['section_id'])) {
            DB::table('section_student')
                ->where('student_id', $studentId)
                ->where('section_id', $params['section_id'])
                ->update(['attendance_percentage' => $percentage]);
        }
    }
}
