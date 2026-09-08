<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\WebsiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardProgressReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::with(['user', 'class', 'section'])
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->input('class_id')))
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->input('section_id')))
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->input('batch_id')))
            ->orderBy('id');

        $students = $query->paginate(20)->withQueryString();

        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);
        $sections = Section::orderBy('name')->get(['id', 'name']);
        $batches = Batch::orderBy('name')->get(['id', 'name']);

        return view('dashboard.progress-reports.index', compact('students', 'classes', 'sections', 'batches'));
    }

    public function generate(Request $request, Student $student)
    {
        $this->authorize('viewResults', $student);

        $student->load(['user', 'class', 'section', 'batch']);

        $results = ExamResult::with(['exam.subject', 'exam.batch', 'exam.section'])
            ->where('student_id', $student->id)
            ->get();

        $rows = [];
        $totalObtained = 0;
        $totalPossible = 0;

        foreach ($results as $result) {
            $exam = $result->exam;

            if (! $exam) {
                continue;
            }

            $obtained = (float) $result->obtained_marks;
            $total = (float) ($exam->total_marks ?? 0);
            $percentage = $total > 0 ? round(($obtained / $total) * 100, 2) : 0;
            $gradeInfo = $exam->calculateGrade($percentage);

            $totalObtained += $obtained;
            $totalPossible += $total;

            $rows[] = [
                'exam_name' => $exam->name,
                'subject' => $exam->subject?->name ?? 'N/A',
                'obtained' => $obtained,
                'total' => $total,
                'percentage' => $percentage,
                'grade' => $gradeInfo['grade'],
                'points' => $gradeInfo['points'],
                'remark' => $gradeInfo['remark'] ?? '',
                'status' => $result->status,
            ];
        }

        $overallPercentage = $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 2) : 0;
        $overall = (new Exam)->calculateGrade($overallPercentage);

        $submissions = AssignmentSubmission::with(['assignment.subject'])
            ->where('student_id', $student->id)
            ->whereNotNull('marks')
            ->get();

        $assignmentRows = [];
        $assignmentTotalPercentage = 0;
        $assignmentCount = 0;

        foreach ($submissions as $sub) {
            $assignment = $sub->assignment;
            $marks = (float) $sub->marks;
            $total = (float) ($assignment?->total_marks ?? 0);
            $pct = $total > 0 ? round(($marks / $total) * 100, 2) : 0;

            $assignmentTotalPercentage += $pct;
            $assignmentCount++;

            $assignmentRows[] = [
                'title' => $assignment?->title ?? 'Assignment',
                'subject' => $assignment?->subject?->name ?? 'N/A',
                'marks' => $marks,
                'total' => $total,
                'percentage' => $pct,
            ];
        }

        $assignmentAverage = $assignmentCount > 0 ? round($assignmentTotalPercentage / $assignmentCount, 2) : null;

        $settings = WebsiteSetting::getSettings();
        $generatedAt = now();

        $html = view('dashboard.progress-reports.show', compact(
            'student',
            'rows',
            'overall',
            'overallPercentage',
            'assignmentRows',
            'assignmentAverage',
            'settings',
            'generatedAt',
        ))->render();

        if ($request->query('view') == '1') {
            return response()->make($html);
        }

        $filename = 'progress-report-'.($student->admission_number ?? $student->admission_no ?? $student->id).'.pdf';

        return Pdf::loadHTML($html)->download($filename);
    }
}
