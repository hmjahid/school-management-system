<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\WebsiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteResultController extends Controller
{
    public function lookup(Request $request): View
    {
        $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'academic_session_id' => ['nullable', 'integer', 'exists:academic_sessions,id'],
            'roll' => ['nullable', 'string', 'max:32'],
        ]);

        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);
        $sessions = AcademicSession::orderByDesc('name')->get(['id', 'name']);

        $result = collect();
        $student = null;

        if ($request->filled(['class_id', 'academic_session_id', 'roll'])) {
            $student = Student::where('class_id', $request->integer('class_id'))
                ->where(function ($q) use ($request) {
                    $roll = $request->string('roll')->toString();
                    $q->where('roll_no', $roll)
                        ->orWhere('roll_number', $roll);
                })
                ->first();

            if ($student) {
                $exams = Exam::where('is_published_to_public', true)
                    ->where('academic_session_id', $request->integer('academic_session_id'))
                    ->where('batch_id', $student->batch_id)
                    ->pluck('id');

                if ($exams->isNotEmpty()) {
                    $result = ExamResult::with(['student.user', 'exam', 'subject'])
                        ->whereIn('exam_id', $exams)
                        ->where('student_id', $student->id)
                        ->where('is_published', true)
                        ->get();
                }
            }
        }

        return view('site.results', compact('classes', 'sessions', 'result', 'student'));
    }

    public function download(Request $request)
    {
        $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'academic_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'roll' => ['required', 'string', 'max:32'],
        ]);

        $student = Student::where('class_id', $request->integer('class_id'))
            ->where(function ($q) use ($request) {
                $roll = $request->string('roll')->toString();
                $q->where('roll_no', $roll)
                    ->orWhere('roll_number', $roll);
            })
            ->firstOrFail();

        $exams = Exam::where('is_published_to_public', true)
            ->where('academic_session_id', $request->integer('academic_session_id'))
            ->where('batch_id', $student->batch_id)
            ->pluck('id');

        $result = ExamResult::with(['student.user', 'exam', 'subject'])
            ->whereIn('exam_id', $exams)
            ->where('student_id', $student->id)
            ->where('is_published', true)
            ->get();

        $settings = WebsiteSetting::getSettings();

        $html = view('site.results-pdf', compact('student', 'result', 'settings'))->render();

        $pdf = Pdf::loadHTML($html);

        $filename = 'marksheet-'.($student->roll_number ?? $student->roll_no ?? 'student').'-'.$request->integer('academic_session_id').'.pdf';

        return $pdf->download($filename);
    }
}
