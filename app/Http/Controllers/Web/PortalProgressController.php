<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalProgressController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $studentIds = collect();
        if ($user->hasRole('student')) {
            $id = Student::query()->where('user_id', $user->id)->value('id');
            $studentIds = $id ? collect([(int) $id]) : collect();
        } elseif ($user->hasRole('parent')) {
            $guardian = Guardian::query()->where('user_id', $user->id)->first();
            $studentIds = $guardian ? $guardian->students->pluck('id')->map(fn ($v) => (int) $v) : collect();
        }

        abort_unless($studentIds->isNotEmpty(), 403);

        $selectedStudentId = (int) ($request->integer('student_id') ?: $studentIds->first());
        abort_unless($studentIds->contains($selectedStudentId), 403);

        $selectedSessionId = $request->integer('academic_session_id') ?: null;
        $selectedExamType = $request->string('exam_type')->toString() ?: null;

        $sessions = AcademicSession::query()->orderByDesc('start_date')->limit(10)->get();
        $examTypes = [
            Exam::TYPE_QUIZ,
            Exam::TYPE_MID_TERM,
            Exam::TYPE_FINAL,
            Exam::TYPE_ASSIGNMENT,
            Exam::TYPE_PROJECT,
            Exam::TYPE_PRACTICAL,
            Exam::TYPE_ORAL,
            Exam::TYPE_OTHER,
        ];

        $resultsQuery = ExamResult::query()
            ->with(['exam'])
            ->where('student_id', $selectedStudentId)
            ->where('is_published', true)
            ->orderByDesc('id');

        if ($selectedSessionId) {
            $resultsQuery->whereHas('exam', function ($q) use ($selectedSessionId) {
                $q->where('academic_session_id', $selectedSessionId);
            });
        }

        if ($selectedExamType) {
            $resultsQuery->whereHas('exam', function ($q) use ($selectedExamType) {
                $q->where('type', $selectedExamType);
            });
        }

        $results = $resultsQuery->limit(200)->get();

        $byExam = $results
            ->filter(fn ($r) => $r->exam)
            ->groupBy(fn ($r) => $r->exam->id);

        $examSummaries = $byExam->map(function ($rows) {
            $exam = $rows->first()->exam;
            $avgGp = $rows->pluck('grade_point')->filter(fn ($v) => is_numeric($v))->avg();
            $avgMarks = $rows->pluck('obtained_marks')->filter(fn ($v) => is_numeric($v))->avg();

            return [
                'exam' => $exam,
                'count' => $rows->count(),
                'avg_grade_point' => $avgGp,
                'avg_marks' => $avgMarks,
                'rows' => $rows,
            ];
        })->sortByDesc(fn ($x) => $x['exam']?->start_date ?? $x['exam']?->id);

        $student = Student::query()->with(['class', 'section', 'batch', 'user'])->find($selectedStudentId);

        return view('site.portal-progress', [
            'studentIds' => $studentIds,
            'student' => $student,
            'selectedStudentId' => $selectedStudentId,
            'selectedSessionId' => $selectedSessionId,
            'selectedExamType' => $selectedExamType,
            'sessions' => $sessions,
            'examTypes' => $examTypes,
            'examSummaries' => $examSummaries,
        ]);
    }
}
