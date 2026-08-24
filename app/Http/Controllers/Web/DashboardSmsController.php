<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendBulkSmsJob;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\SmsCampaign;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

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

        $roles = Role::orderBy('name')->get();
        $users = User::with('roles')->orderBy('name')->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'role_names' => $u->roles->pluck('name')->join(', '),
            ]);
        $students = Student::with('user')
            ->where(function ($q) {
                $q->whereNotNull('phone_1')
                    ->orWhereNotNull('father_phone')
                    ->orWhereNotNull('mother_phone');
            })
            ->orderBy('id')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'user_id' => $s->user_id,
                'name' => $s->user?->name ?? "Student #{$s->id}",
                'phone' => $s->phone_1 ?: $s->father_phone ?: $s->mother_phone,
            ])
            ->filter(fn ($s) => ! empty($s['phone']));

        return view('dashboard.sms.compose', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'shifts' => SchoolClass::getShifts(),
            'roles' => $roles,
            'users' => $users,
            'students' => $students,
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()?->can('send_bulk_sms'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'audience_type' => ['required', 'string', 'in:all_users,students_class,students_section,students_individual,staff_role,staff_individual,students_shift'],
            'school_class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'shift' => ['nullable', 'string', 'in:morning,day,evening'],
            'role_name' => ['nullable', 'string', 'exists:roles,name'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
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
            'audience_type' => ['required', 'string', 'in:all_users,students_class,students_section,students_individual,staff_role,staff_individual,students_shift'],
            'school_class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'shift' => ['nullable', 'string', 'in:morning,day,evening'],
            'role_name' => ['nullable', 'string', 'exists:roles,name'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
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

        activity('sms')
            ->causedBy($request->user())
            ->performedOn($campaign)
            ->withProperties(['recipients_count' => $recipients->count(), 'audience' => $data['audience_type']])
            ->log('Sent SMS campaign');

        return redirect()->route('dashboard.sms.index')->with('status', __('Campaign queued for :count recipients.', ['count' => $recipients->count()]));
    }

    public function templates(Request $request): View
    {
        abort_unless($request->user()?->can('manage_sms_templates'), 403);

        $templates = \App\Models\NotificationTemplate::where('channel', 'sms')->orderBy('name')->get();

        return view('dashboard.sms.templates', compact('templates'));
    }

    public function dueReminder(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()?->can('send_bulk_sms'), 403);

        $recipients = $this->dueFeeRecipients();

        if ($request->isMethod('post')) {
            if ($recipients->isEmpty()) {
                return back()->withInput()->withErrors(['message' => __('No students with outstanding dues to notify.')]);
            }

            $data = $request->validate([
                'message' => ['required', 'string', 'max:1000'],
            ]);

            $campaign = SmsCampaign::create([
                'name' => 'Due Fee Reminder '.now()->format('Y-m-d'),
                'audience_type' => 'due_reminder',
                'message' => $data['message'],
                'status' => SmsCampaign::STATUS_DRAFT,
                'created_by' => $request->user()->id,
            ]);

            foreach ($recipients as $r) {
                $campaign->recipients()->create([
                    'phone' => $r['phone'],
                    'user_type' => 'student',
                    'user_id' => $r['student_id'],
                ]);
            }

            SendBulkSmsJob::dispatch($campaign->id);

            activity('sms')
                ->causedBy($request->user())
                ->performedOn($campaign)
                ->withProperties(['recipients_count' => $recipients->count(), 'audience' => 'due_reminder'])
                ->log('Sent due fee reminder SMS');

            return redirect()->route('dashboard.sms.index')
                ->with('status', __('Due reminder campaign queued for :count recipients.', ['count' => $recipients->count()]));
        }

        $defaultMessage = 'Dear parent, your child has an outstanding fee balance of {{amount}}. Please clear the dues at your earliest convenience. - School Administration';

        return view('dashboard.sms.due-reminder', [
            'recipients' => $recipients->take(50),
            'total' => $recipients->count(),
            'defaultMessage' => $defaultMessage,
        ]);
    }

    protected function dueFeeRecipients(): \Illuminate\Support\Collection
    {
        $duePayments = FeePayment::with('student.user')
            ->where('balance', '>', 0)
            ->whereNotIn('status', [
                FeePayment::STATUS_PAID,
                FeePayment::STATUS_CANCELLED,
                FeePayment::STATUS_REFUNDED,
            ])
            ->get();

        return $duePayments
            ->groupBy('student_id')
            ->map(function ($items) {
                $student = $items->first()->student;
                if (! $student) {
                    return null;
                }
                $phone = $student->phone_1 ?: $student->father_phone ?: $student->mother_phone;
                if (empty($phone)) {
                    return null;
                }

                return [
                    'student_id' => $student->id,
                    'name' => $student->user?->name ?? "Student #{$student->id}",
                    'phone' => $phone,
                    'due' => (float) $items->sum('balance'),
                ];
            })
            ->filter()
            ->values();
    }

    protected function resolveRecipients(array $data): \Illuminate\Support\Collection
    {
        $recipients = collect();

        switch ($data['audience_type']) {
            case 'all_users':
                Student::with('user')->whereNotNull('phone_1')->orderBy('id')->chunk(200, function ($students) use (&$recipients) {
                    foreach ($students as $s) {
                        if ($phone = $s->phone_1 ?: $s->father_phone ?: $s->mother_phone) {
                            $recipients->push(['phone' => $phone, 'user_type' => 'student', 'user_id' => $s->id]);
                        }
                    }
                });
                User::role(['teacher', 'staff', 'admin'])->whereNotNull('phone')->orderBy('id')->chunk(200, function ($users) use (&$recipients) {
                    foreach ($users as $u) {
                        if ($u->phone) {
                            $recipients->push(['phone' => $u->phone, 'user_type' => 'staff', 'user_id' => $u->id]);
                        }
                    }
                });
                break;

            case 'students_class':
                $query = Student::query();
                if (! empty($data['school_class_id'])) {
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

            case 'students_section':
                $query = Student::query();
                if (! empty($data['section_id'])) {
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

            case 'students_shift':
                $shift = $data['shift'] ?? null;
                $query = Student::query();
                if (! empty($shift)) {
                    $query->whereHas('class', fn ($q) => $q->where('shift', $shift));
                }
                $query->whereNotNull('phone_1')->chunk(200, function ($students) use (&$recipients) {
                    foreach ($students as $s) {
                        if ($phone = $s->phone_1 ?: $s->father_phone ?: $s->mother_phone) {
                            $recipients->push(['phone' => $phone, 'user_type' => 'student', 'user_id' => $s->id]);
                        }
                    }
                });
                break;

            case 'students_individual':
                if (! empty($data['user_ids'])) {
                    Student::whereIn('user_id', $data['user_ids'])->whereNotNull('phone_1')->chunk(200, function ($students) use (&$recipients) {
                        foreach ($students as $s) {
                            if ($phone = $s->phone_1 ?: $s->father_phone ?: $s->mother_phone) {
                                $recipients->push(['phone' => $phone, 'user_type' => 'student', 'user_id' => $s->id]);
                            }
                        }
                    });
                }
                break;

            case 'staff_role':
                $roleNames = ! empty($data['role_name']) ? [$data['role_name']] : ['teacher', 'staff', 'admin'];
                User::role($roleNames)->whereNotNull('phone')->orderBy('id')->chunk(200, function ($users) use (&$recipients) {
                    foreach ($users as $u) {
                        if ($u->phone) {
                            $recipients->push(['phone' => $u->phone, 'user_type' => 'staff', 'user_id' => $u->id]);
                        }
                    }
                });
                break;

            case 'staff_individual':
                if (! empty($data['user_ids'])) {
                    User::whereIn('id', $data['user_ids'])->whereNotNull('phone')->chunk(200, function ($users) use (&$recipients) {
                        foreach ($users as $u) {
                            if ($u->phone) {
                                $recipients->push(['phone' => $u->phone, 'user_type' => 'staff', 'user_id' => $u->id]);
                            }
                        }
                    });
                }
                break;
        }

        return $recipients->unique('phone')->values();
    }
}
