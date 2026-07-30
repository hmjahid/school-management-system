<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardExamResultController extends Controller
{
    public function index(Request $request, Exam $exam): View
    {
        $this->authorize('viewAny', ExamResult::class);
        $this->authorize('view', $exam);

        $exam->load(['subject', 'batch', 'section', 'academicSession']);

        $studentsQuery = Student::with(['user', 'class', 'section'])
            ->where('status', 'active')
            ->orderBy('class_id')
            ->orderBy('roll_number');

        if ($exam->batch_id) {
            $studentsQuery->where('batch_id', $exam->batch_id);
        }
        if ($exam->section_id) {
            $studentsQuery->where('section_id', $exam->section_id);
        }
        if ($exam->class_id) {
            $studentsQuery->where('class_id', $exam->class_id);
        }

        $students = $studentsQuery->limit(500)->get();
        $results = ExamResult::where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $stats = $exam->getStatistics();

        return view('dashboard.exams.results', [
            'exam' => $exam,
            'students' => $students,
            'results' => $results,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('create', ExamResult::class);

        $data = $request->validate([
            'marks' => 'required|array',
            'marks.*' => 'nullable|numeric|min:0|max:' . (float) ($exam->total_marks ?: 100),
        ]);

        $saved = 0;
        foreach ($data['marks'] as $studentId => $marks) {
            if ($marks === null || $marks === '') {
                continue;
            }

            $student = Student::find($studentId);
            if (! $student) {
                continue;
            }

            $calc = $exam->calculateGrade((float) $marks);
            $status = ((float) $marks) >= (float) $exam->passing_marks
                ? ExamResult::STATUS_PASSED
                : ExamResult::STATUS_FAILED;

            ExamResult::updateOrCreate(
                ['exam_id' => $exam->id, 'student_id' => $studentId],
                [
                    'obtained_marks' => (float) $marks,
                    'grade' => $calc['grade'],
                    'grade_point' => $calc['points'],
                    'remarks' => $calc['remark'] ?? null,
                    'status' => $status,
                    'submitted_by' => $request->user()?->id,
                    'submitted_at' => now(),
                ]
            );
            $saved++;
        }

        return redirect()
            ->route('dashboard.exams.results', $exam)
            ->with('status', __('Saved :n result(s).', ['n' => $saved]));
    }

    public function publish(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('publish', ExamResult::class);

        $updated = ExamResult::where('exam_id', $exam->id)
            ->update([
                'is_published' => true,
                'published_at' => now(),
                'published_by' => $request->user()?->id,
            ]);

        return redirect()
            ->route('dashboard.exams.results', $exam)
            ->with('status', __('Published :n result(s).', ['n' => $updated]));
    }

    public function unpublish(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('unpublish', ExamResult::class);

        ExamResult::where('exam_id', $exam->id)->update([
            'is_published' => false,
            'published_at' => null,
            'published_by' => null,
        ]);

        return redirect()
            ->route('dashboard.exams.results', $exam)
            ->with('status', __('Results unpublished.'));
    }

    public function export(Request $request, Exam $exam): StreamedResponse
    {
        $this->authorize('viewAny', ExamResult::class);

        $filename = 'exam-' . ($exam->code ?: $exam->id) . '-results.csv';

        $results = ExamResult::with(['student.user', 'student.class', 'student.section'])
            ->where('exam_id', $exam->id)
            ->get()
            ->sortBy('student.roll_number');

        return response()->streamDownload(function () use ($results, $exam) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'admission_number', 'name', 'class', 'section', 'roll',
                'obtained_marks', 'total_marks', 'grade', 'grade_point', 'status', 'is_published',
            ]);

            foreach ($results as $r) {
                fputcsv($out, [
                    $r->student?->admission_number,
                    $r->student?->user?->name,
                    $r->student?->class?->name,
                    $r->student?->section?->name,
                    $r->student?->roll_number,
                    $r->obtained_marks,
                    $exam->total_marks,
                    $r->grade,
                    $r->grade_point,
                    $r->status,
                    $r->is_published ? '1' : '0',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadMarksheet(Request $request, Exam $exam, ExamResult $result): \Illuminate\Http\Response
    {
        $this->authorize('view', $exam);

        $result->load(['student.user', 'student.class', 'student.section', 'exam.subject', 'exam.batch', 'exam.academicSession']);

        $settings = \App\Models\WebsiteSetting::getSettings();

        $html = view('dashboard.exams.marksheet-pdf', [
            'exam' => $exam,
            'result' => $result,
            'settings' => $settings,
        ])->render();

        $pdf = Pdf::loadHTML($html);

        $filename = 'marksheet-' . ($result->student?->admission_number ?? $result->student_id) . '-' . ($exam->code ?? $exam->id) . '.pdf';

        return $pdf->download($filename);
    }

    public function studentResults(Request $request, Student $student): View
    {
        $this->authorize('viewResults', $student);
        $student->load(['user', 'class', 'section', 'batch']);

        $results = ExamResult::with(['exam.subject', 'exam.batch', 'exam.section'])
            ->where('student_id', $student->id)
            ->where('is_published', true)
            ->latest('published_at')
            ->paginate(25);

        $summary = [
            'count' => $results->total(),
            'avg_grade_point' => ExamResult::where('student_id', $student->id)
                ->where('is_published', true)
                ->avg('grade_point'),
            'latest_grade' => optional($results->first())->grade,
        ];

        return view('dashboard.students.results', [
            'student' => $student,
            'results' => $results,
            'summary' => $summary,
        ]);
    }
}
