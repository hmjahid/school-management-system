<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ContactSubmission;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardModulesController extends Controller
{
    public function students(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $query = Student::with(['user', 'class', 'section', 'batch', 'guardian.user']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $students = $query->latest()->paginate(15)->withQueryString();

        return view('dashboard.modules.students', compact('students'));
    }

    public function teachers(Request $request): View
    {
        $this->authorize('viewAny', Teacher::class);

        $query = Teacher::with(['user']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $teachers = $query->latest()->paginate(15)->withQueryString();

        return view('dashboard.modules.teachers', compact('teachers'));
    }

    public function parents(Request $request): View
    {
        $this->authorize('viewAny', Guardian::class);

        $query = Guardian::with(['user', 'students.user']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $guardians = $query->latest('id')->paginate(15)->withQueryString();

        return view('dashboard.modules.parents', ['guardians' => $guardians]);
    }

    public function classes(Request $request): View
    {
        $this->authorize('viewAny', SchoolClass::class);

        $query = SchoolClass::with(['classTeacher.user', 'academicSession']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%");
            });
        }

        $classes = $query->orderBy('grade_level')->paginate(15)->withQueryString();

        return view('dashboard.modules.classes', compact('classes'));
    }

    public function attendance(Request $request): View
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::with([
            'student.user',
            'subject',
            'teacher.user',
        ])->latest('date')->latest('id');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $records = $query->paginate(20)->withQueryString();

        return view('dashboard.modules.attendance', compact('records'));
    }

    public function exams(Request $request): View
    {
        $this->authorize('viewAny', Exam::class);

        $query = Exam::with(['subject', 'batch', 'section', 'academicSession']);

        if ($status = $request->string('status')->toString()) {
            if ($status === 'upcoming') {
                $query->upcoming();
            } elseif ($status === 'ongoing') {
                $query->ongoing();
            } elseif ($status === 'completed') {
                $query->completed();
            } else {
                $query->where('status', $status);
            }
        }

        $exams = $query->latest('start_date')->paginate(15)->withQueryString();

        return view('dashboard.modules.exams', compact('exams'));
    }

    public function fees(Request $request): View
    {
        $user = $request->user();
        $allowed = $user->hasAnyRole(['admin', 'accountant'])
            || $user->hasAnyPermission(['collect_fees', 'view_financial_reports', 'manage_fee_categories', 'manage_fee_types']);

        abort_unless($allowed, 403);

        $query = Fee::with(['schoolClass', 'section', 'student.user']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $fees = $query->latest()->paginate(15)->withQueryString();

        return view('dashboard.modules.fees', compact('fees'));
    }

    public function settings(): View
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $settings = WebsiteSetting::first() ?? new WebsiteSetting;

        return view('dashboard.modules.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $data = $request->validate([
            'school_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $settings = WebsiteSetting::firstOrNew([]);
        $settings->fill($data);
        $settings->save();

        return redirect()->route('dashboard.settings')->with('status', __('Settings saved.'));
    }

    public function contactSubmissions(Request $request): View
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $query = ContactSubmission::query()->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        $rows = $query->paginate(25)->withQueryString();

        return view('dashboard.modules.contact-submissions', compact('rows'));
    }
}
