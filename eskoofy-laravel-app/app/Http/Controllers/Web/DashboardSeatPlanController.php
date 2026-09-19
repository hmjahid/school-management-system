<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Student;
use App\Models\WebsiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DashboardSeatPlanController extends Controller
{
    public function index(Request $request): View
    {
        $query = Exam::with(['batch', 'section'])->orderByDesc('id');

        if ($request->filled('published')) {
            $query->where('is_published', $request->boolean('published'));
        }

        $exams = $query->paginate(20)->withQueryString();

        return view('dashboard.seat-plans.index', compact('exams'));
    }

    public function generate(Request $request, Exam $exam): View|Response
    {
        $perRoom = (int) $request->input('per_room', 30);
        if ($perRoom < 1) {
            $perRoom = 30;
        }

        $students = Student::with('user')
            ->where('batch_id', $exam->batch_id)
            ->when($exam->section_id, fn ($q) => $q->where('section_id', $exam->section_id))
            ->orderBy('roll_number')
            ->get();

        $rooms = [];
        $roomNumber = 1;
        foreach ($students->chunk($perRoom) as $chunk) {
            $rooms['Room-'.$roomNumber] = $chunk;
            $roomNumber++;
        }

        $settings = WebsiteSetting::getSettings();

        $date = $exam->start_date?->format('d M Y') ?? 'N/A';

        $preview = $request->boolean('view');

        $view = view('dashboard.seat-plans.show', [
            'exam' => $exam,
            'rooms' => $rooms,
            'perRoom' => $perRoom,
            'settings' => $settings,
            'date' => $date,
            'preview' => $preview,
        ]);

        if ($request->boolean('view')) {
            return $view;
        }

        $html = $view->render();

        $pdf = Pdf::loadHTML($html);

        $filename = 'seat-plan-'.($exam->code ?? $exam->id).'.pdf';

        return $pdf->download($filename);
    }
}
