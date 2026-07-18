<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\AdmissionSetting;
use App\Models\Batch;
use App\Models\WebsiteContent;
use App\Models\WebsiteSetting;
use App\Notifications\AdmissionTransactionSubmittedNotification;
use App\Services\AdmissionSubmitter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AdmissionWebController extends Controller
{
    public function apply(): View
    {
        $settings = AdmissionSetting::getSettings();
        if (!$settings->is_open) {
            return $this->closedView();
        }

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

        return view('site.admissions-apply', compact('content', 'sessions', 'batches', 'settings'));
    }

    public function applyStore(Request $request, AdmissionSubmitter $submitter): RedirectResponse
    {
        $settings = AdmissionSetting::getSettings();
        if (!$settings->is_open) {
            return redirect()
                ->route('admissions.apply')
                ->withErrors(['closed' => $settings->closed_message]);
        }

        $admission = $submitter->submitPublicApplication($request);

        // Stamp fee & payment number from settings onto the new application
        $admission->admission_fee = $settings->admission_fee;
        $admission->payment_number = $settings->payment_number;
        $admission->save();

        return redirect()
            ->route('admissions.status', ['application_number' => $admission->application_number])
            ->with('status', __('Application submitted successfully. Save your application number for tracking.'));
    }

    /**
     * Public applicant submits their payment transaction ID after paying.
     */
    public function submitTransaction(Request $request, Admission $admission): RedirectResponse
    {
        if ($admission->payment_status !== Admission::PAYMENT_UNPAID) {
            return back()->withErrors(['payment' => __('Payment details have already been submitted.')]);
        }

        $data = $request->validate([
            'transaction_id' => ['required', 'string', 'max:128'],
            'payment_method' => ['required', 'string', 'in:'.implode(',', Admission::PAYMENT_METHODS)],
        ]);

        $admission->fill($data);
        $admission->payment_status = Admission::PAYMENT_SUBMITTED;
        $admission->paid_at = now();
        $admission->save();

        if ($admission->email) {
            Notification::route('mail', $admission->email)
                ->notify(new AdmissionTransactionSubmittedNotification($admission));
        }

        return redirect()
            ->route('admissions.status', ['application_number' => $admission->application_number])
            ->with('status', __('Payment details submitted. Verification pending.'));
    }

    /**
     * Public download of payment receipt (printable HTML).
     */
    public function receipt(Admission $admission, Request $request): View
    {
        $site = WebsiteSetting::current();
        return view('site.admissions.admission-receipt', [
            'admission' => $admission,
            'site' => $site,
        ]);
    }

    /**
     * Public download of approval letter (printable HTML). Available only when
     * payment verified AND admission approved.
     */
    public function approvalLetter(Admission $admission, Request $request): View|RedirectResponse
    {
        if (!($admission->payment_status === Admission::PAYMENT_VERIFIED && $admission->status === Admission::STATUS_APPROVED)) {
            return redirect()
                ->route('admissions.status', ['application_number' => $admission->application_number])
                ->withErrors(['letter' => __('Approval letter is not yet available.')]);
        }

        $site = WebsiteSetting::current();
        return view('site.admissions.admission-approval-letter', [
            'admission' => $admission,
            'site' => $site,
        ]);
    }

    /**
     * Shared view used by all public admission entry points when admissions
     * are currently closed. The page is still reachable so existing
     * applicants can find status & contact links, and for SEO/back-links.
     */
    protected function closedView(): View
    {
        $content = WebsiteContent::getContent('admissions');
        return view('site.admissions', [
            'content' => $content,
            'admissionsClosed' => true,
        ]);
    }

    public function status(Request $request): View
    {
        $applicationNumber = $request->query('application_number');
        $applicationNumber = $applicationNumber ? strtoupper(trim((string) $applicationNumber)) : null;

        $admission = null;
        $settings = null;
        if ($applicationNumber) {
            $admission = Admission::query()
                ->with('latestTest')
                ->whereRaw('UPPER(application_number) = ?', [$applicationNumber])
                ->first();
            $settings = AdmissionSetting::getSettings();
        }

        return view('site.admission-status', [
            'admission' => $admission,
            'applicationNumber' => $applicationNumber,
            'settings' => $settings,
        ]);
    }
}