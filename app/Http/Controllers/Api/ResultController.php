<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'academic_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'roll' => ['required', 'string', 'max:32'],
        ]);

        $student = Student::with('user')
            ->where('class_id', $request->integer('class_id'))
            ->where(function ($q) use ($request) {
                $roll = $request->string('roll')->toString();
                $q->where('roll_no', $roll)
                  ->orWhere('roll_number', $roll);
            })
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $exams = Exam::where('is_published_to_public', true)
            ->where('academic_session_id', $request->integer('academic_session_id'))
            ->where('batch_id', $student->batch_id)
            ->get(['id', 'name', 'type', 'total_marks']);

        $results = ExamResult::with('subject')
            ->whereIn('exam_id', $exams->pluck('id'))
            ->where('student_id', $student->id)
            ->where('is_published', true)
            ->get(['id', 'exam_id', 'subject_id', 'obtained_marks', 'grade', 'remarks', 'status']);

        $grouped = $results->groupBy(fn($r) => $r->exam_id);

        $data = [
            'student' => [
                'id' => $student->id,
                'name' => $student->user?->name ?? ($student->first_name . ' ' . $student->last_name),
                'roll_no' => $student->roll_no,
                'class' => $student->class?->name,
            ],
            'exams' => $exams->map(function ($exam) use ($grouped) {
                $examResults = $grouped->get($exam->id, collect());
                return [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'type' => $exam->type,
                    'total_marks' => $exam->total_marks,
                    'results' => $examResults->map(fn($r) => [
                        'id' => $r->id,
                        'subject' => $r->subject?->name,
                        'obtained_marks' => $r->obtained_marks,
                        'grade' => $r->grade,
                        'remarks' => $r->remarks,
                        'status' => $r->status,
                    ]),
                ];
            }),
        ];

        return response()->json($data);
    }

    public function filters(): JsonResponse
    {
        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);
        $sessions = AcademicSession::orderByDesc('name')->get(['id', 'name']);

        return response()->json([
            'classes' => $classes,
            'sessions' => $sessions,
        ]);
    }
}
