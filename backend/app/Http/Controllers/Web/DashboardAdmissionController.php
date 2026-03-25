<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Notifications\AdmissionStatusChangedNotification;
use App\Notifications\AdmissionTestScheduledNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class DashboardAdmissionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('view_admissions'), 403);

        $query = Admission::query()
            ->with(['academicSession', 'batch', 'latestTest'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($b) use ($q) {
                $b->where('application_number', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('dashboard.admissions.index', compact('rows'));
    }

    public function show(Admission $admission, Request $request): View
    {
        abort_unless($request->user()?->can('view_admissions'), 403);

        $admission->load(['documents', 'academicSession', 'batch', 'tests' => fn ($q) => $q->orderByDesc('scheduled_at')]);

        return view('dashboard.admissions.show', compact('admission'));
    }

    public function scheduleTest(Request $request, Admission $admission): RedirectResponse
    {
        abort_unless($request->user()?->can('edit_admissions'), 403);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $test = $admission->tests()->create([
            'scheduled_at' => $data['scheduled_at'],
            'venue' => $data['venue'] ?? null,
            'status' => $data['status'] ?? 'scheduled',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        Notification::route('mail', $admission->email)
            ->notify(new AdmissionTestScheduledNotification($admission, $test));

        return back()->with('status', __('Test scheduled.'));
    }

    public function updateStatus(Request $request, Admission $admission): RedirectResponse
    {
        abort_unless($request->user()?->can('edit_admissions'), 403);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', [
                Admission::STATUS_UNDER_REVIEW,
                Admission::STATUS_APPROVED,
                Admission::STATUS_REJECTED,
                Admission::STATUS_WAITLISTED,
                Admission::STATUS_CANCELLED,
            ])],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'admission_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = (string) $admission->status;
        $new = $data['status'];

        $ok = match ($new) {
            Admission::STATUS_APPROVED => $admission->approve($data['admission_notes'] ?? null),
            Admission::STATUS_REJECTED => $admission->reject($data['rejection_reason'] ?? __('Not specified')),
            default => tap($admission, function (Admission $a) use ($new) {
                $a->status = $new;
                if ($new === Admission::STATUS_CANCELLED) {
                    $a->cancelled_at = now();
                }
                $a->save();
            }) ? true : false,
        };

        if ($ok) {
            $admission->refresh();
            Notification::route('mail', $admission->email)
                ->notify(new AdmissionStatusChangedNotification($admission, $old, $new));

            return back()->with('status', __('Status updated.'));
        }

        return back()->withErrors(['status' => __('Unable to update status from current state.')]);
    }

    public function updateTest(Request $request, Admission $admission, AdmissionTest $test): RedirectResponse
    {
        abort_unless($request->user()?->can('edit_admissions'), 403);
        abort_unless($test->admission_id === $admission->id, 404);

        $data = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $test->fill($data);
        $test->updated_by = $request->user()->id;
        $test->save();

        return back()->with('status', __('Test updated.'));
    }

    public function deleteTest(Request $request, Admission $admission, AdmissionTest $test): RedirectResponse
    {
        abort_unless($request->user()?->can('edit_admissions'), 403);
        abort_unless($test->admission_id === $admission->id, 404);

        $test->delete();

        return back()->with('status', __('Test removed.'));
    }
}
