<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\Batch;
use App\Models\WebsiteContent;
use App\Services\AdmissionSubmitter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionWebController extends Controller
{
    public function apply(): View
    {
        $content = WebsiteContent::getContent('admissions');

        $sessions = AcademicSession::query()
            ->active()
            ->orderByDesc('start_date')
            ->get();

        if ($sessions->isEmpty()) {
            $sessions = AcademicSession::query()->orderByDesc('start_date')->limit(10)->get();
        }

        $batches = Batch::query()
            ->active()
            ->orderBy('name')
            ->get();

        if ($batches->isEmpty()) {
            $batches = Batch::query()->orderBy('name')->limit(30)->get();
        }

        return view('site.admissions-apply', compact('content', 'sessions', 'batches'));
    }

    public function applyStore(Request $request, AdmissionSubmitter $submitter): RedirectResponse
    {
        $admission = $submitter->submitPublicApplication($request);

        return redirect()
            ->route('admissions.status', ['application_number' => $admission->application_number])
            ->with('status', __('Application submitted successfully. Save your application number for tracking.'));
    }

    public function status(Request $request): View
    {
        $applicationNumber = $request->query('application_number');
        $applicationNumber = $applicationNumber ? strtoupper(trim((string) $applicationNumber)) : null;

        $admission = null;
        if ($applicationNumber) {
            $admission = Admission::query()
                ->with('latestTest')
                ->whereRaw('UPPER(application_number) = ?', [$applicationNumber])
                ->first();
        }

        return view('site.admission-status', [
            'admission' => $admission,
            'applicationNumber' => $applicationNumber,
        ]);
    }
}
