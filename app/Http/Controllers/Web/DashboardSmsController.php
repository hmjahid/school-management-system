<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendBulkSmsJob;
use App\Models\SmsCampaign;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardSmsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('send_bulk_sms'), 403);

        $rows = SmsCampaign::withCount('recipients')->with('creator')->orderByDesc('id')->paginate(20);

        return view('dashboard.sms.index', compact('rows'));
    }

    public function compose(Request $request): View
    {
        abort_unless($request->user()?->can('send_bulk_sms'), 403);

        return view('dashboard.sms.compose', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()?->can('send_bulk_sms'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'audience_type' => ['required', 'string', 'in:all,class,section,staff'],
            'school_class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $recipients = $this->resolveRecipients($data);

        if ($recipients->isEmpty()) {
            return back()->withInput()->withErrors(['message' => __('No recipients match the selected audience.')]);
        }

        return view('dashboard.sms.preview', [
            'data' => $data,
            'recipients' => $recipients->take(50),
            'total' => $recipients->count(),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('send_bulk_sms'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'audience_type' => ['required', 'string', 'in:all,class,section,staff'],
            'school_class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $recipients = $this->resolveRecipients($data);
        if ($recipients->isEmpty()) {
            return back()->withInput()->withErrors(['message' => __('No recipients.')]);
        }

        $campaign = SmsCampaign::create([
            'name' => $data['name'],
            'audience_type' => $data['audience_type'],
            'school_class_id' => $data['school_class_id'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'message' => $data['message'],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => SmsCampaign::STATUS_DRAFT,
            'created_by' => $request->user()->id,
        ]);

        foreach ($recipients as $r) {
            $campaign->recipients()->create([
                'phone' => $r['phone'],
                'user_type' => $r['user_type'],
                'user_id' => $r['user_id'] ?? null,
            ]);
        }

        // Dispatch immediately (ignore scheduled_at for v1 simplicity)
        SendBulkSmsJob::dispatch($campaign->id);

        return redirect()->route('dashboard.sms.index')->with('status', __('Campaign queued for :count recipients.', ['count' => $recipients->count()]));
    }

    public function templates(Request $request): View
    {
        abort_unless($request->user()?->can('manage_sms_templates'), 403);

        $templates = \App\Models\NotificationTemplate::where('channel', 'sms')->orderBy('name')->get();

        return view('dashboard.sms.templates', compact('templates'));
    }

    protected function resolveRecipients(array $data): \Illuminate\Support\Collection
    {
        $recipients = collect();

        switch ($data['audience_type']) {
            case 'all':
                Student::with('user')->whereNotNull('phone_1')->orderBy('id')->chunk(200, function ($students) use (&$recipients) {
                    foreach ($students as $s) {
                        if ($phone = $s->phone_1 ?: $s->father_phone ?: $s->mother_phone) {
                            $recipients->push(['phone' => $phone, 'user_type' => 'student', 'user_id' => $s->id]);
                        }
                    }
                });
                User::role(['teacher','staff','admin'])->whereNotNull('phone')->orderBy('id')->chunk(200, function ($users) use (&$recipients) {
                    foreach ($users as $u) {
                        if ($u->phone) {
                            $recipients->push(['phone' => $u->phone, 'user_type' => 'staff', 'user_id' => $u->id]);
                        }
                    }
                });
                break;

            case 'class':
                $query = Student::query();
                if (!empty($data['school_class_id'])) {
                    $query->where('class_id', $data['school_class_id']);
                }
                $query->whereNotNull('phone_1')->chunk(200, function ($students) use (&$recipients) {
                    foreach ($students as $s) {
                        if ($phone = $s->phone_1 ?: $s->father_phone ?: $s->mother_phone) {
                            $recipients->push(['phone' => $phone, 'user_type' => 'student', 'user_id' => $s->id]);
                        }
                    }
                });
                break;

            case 'section':
                $query = Student::query();
                if (!empty($data['section_id'])) {
                    $query->where('section_id', $data['section_id']);
                }
                $query->whereNotNull('phone_1')->chunk(200, function ($students) use (&$recipients) {
                    foreach ($students as $s) {
                        if ($phone = $s->phone_1 ?: $s->father_phone ?: $s->mother_phone) {
                            $recipients->push(['phone' => $phone, 'user_type' => 'student', 'user_id' => $s->id]);
                        }
                    }
                });
                break;

            case 'staff':
                User::role(['teacher','staff','admin'])->whereNotNull('phone')->orderBy('id')->chunk(200, function ($users) use (&$recipients) {
                    foreach ($users as $u) {
                        if ($u->phone) {
                            $recipients->push(['phone' => $u->phone, 'user_type' => 'staff', 'user_id' => $u->id]);
                        }
                    }
                });
                break;
        }

        return $recipients->unique('phone')->values();
    }
}