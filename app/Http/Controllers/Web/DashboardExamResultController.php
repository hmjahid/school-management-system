<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $students = $studentsQuery->limit(500)->get();
        $results = ExamResult::where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $students->loadMissing(['guardian.user']);
        $smsRecipients = $students
            ->pluck('guardian')
            ->filter()
            ->pluck('phone')
            ->filter()
            ->unique()
            ->count();

        $stats = $exam->getStatistics();

        return view('dashboard.exams.results', [
            'exam' => $exam,
            'students' => $students,
            'results' => $results,
            'stats' => $stats,
            'smsRecipients' => $smsRecipients,
        ]);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('create', ExamResult::class);

        $data = $request->validate([
            'marks' => 'required|array',
            'marks.*' => 'nullable|numeric|min:0|max:'.(float) ($exam->total_marks ?: 100),
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

        $filename = 'exam-'.($exam->code ?: $exam->id).'-results.csv';

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
        $user = $request->user();

        $this->authorizeMarksheet($user, $exam, $result);

        $result->load(['student.user', 'student.class', 'student.section', 'student.batch', 'exam.subject', 'exam.batch', 'exam.academicSession']);

        $settings = \App\Models\WebsiteSetting::getSettings();

        $html = view('dashboard.exams.marksheet-pdf', [
            'exam' => $exam,
            'result' => $result,
            'settings' => $settings,
        ])->render();

        $pdf = Pdf::loadHTML($html);

        $filename = 'marksheet-'.($result->student?->admission_number ?? $result->student_id).'-'.($exam->code ?? $exam->id).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Restrict marksheet downloads: admins any, students/parents only their
     * own, teachers/staff must pass exam visibility (assigned or permitted).
     */
    protected function authorizeMarksheet(?User $user, Exam $exam, ExamResult $result): void
    {
        if (! $user || $user->hasRole('admin')) {
            return;
        }

        if ($user->hasRole('student')) {
            $own = Student::query()->where('user_id', $user->id)->value('id');

            abort_unless($own && (int) $own === (int) $result->student_id, 403);

            return;
        }

        if ($user->hasRole('parent')) {
            $guardian = Guardian::query()->where('user_id', $user->id)->first();
            $owns = $guardian && $guardian->students()->whereKey($result->student_id)->exists();

            abort_unless($owns, 403);

            return;
        }

        $this->authorize('view', $exam);
    }

    public function myResults(Request $request): View
    {
        $user = $request->user();
        $this->authorize('viewAny', ExamResult::class);

        $query = Exam::with(['subject', 'batch', 'section', 'academicSession'])
            ->withCount([
                'results',
                'results as published_results_count' => fn ($q) => $q->where('is_published', true),
            ]);

        $teacher = $user->teacher;
        if ($user->hasRole('admin')) {
            // admins see every exam
        } elseif ($teacher) {
            $query->whereHas('teachers', fn ($q) => $q->whereKey($teacher->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        $exams = $query->latest('start_date')->limit(100)->get();

        // Precompute active student counts by batch/section so we can tell
        // whether marks have been entered for every candidate.
        $countRows = Student::where('status', 'active')
            ->selectRaw('COALESCE(batch_id,0) AS batch_id, COALESCE(section_id,0) AS section_id, COUNT(*) AS c')
            ->groupByRaw('COALESCE(batch_id,0), COALESCE(section_id,0)')
            ->get();

        $byBatch = [];
        $bySection = [];
        $byPair = [];
        foreach ($countRows as $row) {
            $byBatch[$row->batch_id] = ($byBatch[$row->batch_id] ?? 0) + $row->c;
            $bySection[$row->section_id] = ($bySection[$row->section_id] ?? 0) + $row->c;
            $byPair[$row->batch_id.':'.$row->section_id] = $row->c;
        }

        $exams->each(function (Exam $exam) use ($byBatch, $bySection, $byPair) {
            $total = 0;
            if ($exam->batch_id && $exam->section_id) {
                $total = $byPair[$exam->batch_id.':'.$exam->section_id] ?? 0;
            } elseif ($exam->batch_id) {
                $total = $byBatch[$exam->batch_id] ?? 0;
            } elseif ($exam->section_id) {
                $total = $bySection[$exam->section_id] ?? 0;
            }

            $exam->total_students = $total;
        });

        $published = $exams->filter(fn (Exam $e) => $e->isFullyPublished());
        $ready = $exams->filter(fn (Exam $e) => ! $e->isFullyPublished()
            && $e->total_students > 0
            && $e->results_count >= $e->total_students);
        $pending = $exams->filter(fn (Exam $e) => ! $e->isFullyPublished()
            && ! ($e->total_students > 0 && $e->results_count >= $e->total_students));

        return view('dashboard.exams.my-results', compact('published', 'ready', 'pending'));
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
