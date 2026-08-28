<?php

use App\Http\Controllers\Web\AuthSessionController;
use App\Http\Controllers\Web\CmsWebController;
use App\Http\Controllers\Web\DashboardActivityController;
use App\Http\Controllers\Web\DashboardAdmissionController;
use App\Http\Controllers\Web\DashboardAdmitCardController;
use App\Http\Controllers\Web\DashboardAnnouncementController;
use App\Http\Controllers\Web\DashboardAssignmentController;
use App\Http\Controllers\Web\DashboardAttendanceController;
use App\Http\Controllers\Web\DashboardBackupController;
use App\Http\Controllers\Web\DashboardBankReconciliationController;
use App\Http\Controllers\Web\DashboardBookCategoryController;
use App\Http\Controllers\Web\DashboardBookController;
use App\Http\Controllers\Web\DashboardBookIssueController;
use App\Http\Controllers\Web\DashboardBudgetController;
use App\Http\Controllers\Web\DashboardBulkController;
use App\Http\Controllers\Web\DashboardCertificateController;
use App\Http\Controllers\Web\DashboardCommunicationsController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DashboardDocumentController;
use App\Http\Controllers\Web\DashboardEventController;
use App\Http\Controllers\Web\DashboardExamController;
use App\Http\Controllers\Web\DashboardExamResultController;
use App\Http\Controllers\Web\DashboardExpenseCategoryController;
use App\Http\Controllers\Web\DashboardExpenseController;
use App\Http\Controllers\Web\DashboardFavoriteController;
use App\Http\Controllers\Web\DashboardFeeController;
use App\Http\Controllers\Web\DashboardFeePaymentController;
use App\Http\Controllers\Web\DashboardGalleryController;
use App\Http\Controllers\Web\DashboardGuardianController;
use App\Http\Controllers\Web\DashboardHelpController;
use App\Http\Controllers\Web\DashboardHostelController;
use App\Http\Controllers\Web\DashboardLeaveController;
use App\Http\Controllers\Web\DashboardLedgerController;
use App\Http\Controllers\Web\DashboardLibraryReportController;
use App\Http\Controllers\Web\DashboardLocaleController;
use App\Http\Controllers\Web\DashboardMediaController;
use App\Http\Controllers\Web\DashboardModulesController;
use App\Http\Controllers\Web\DashboardNewsController;
use App\Http\Controllers\Web\DashboardNoticeController;
use App\Http\Controllers\Web\DashboardNotificationPreferencesController;
use App\Http\Controllers\Web\DashboardOnboardingController;
use App\Http\Controllers\Web\DashboardPayrollController;
use App\Http\Controllers\Web\DashboardPermissionController;
use App\Http\Controllers\Web\DashboardProfileController;
use App\Http\Controllers\Web\DashboardProgressReportController;
use App\Http\Controllers\Web\DashboardReportBuilderController;
use App\Http\Controllers\Web\DashboardReportController;
use App\Http\Controllers\Web\DashboardRoleController;
use App\Http\Controllers\Web\DashboardRoutineController;
use App\Http\Controllers\Web\DashboardSchoolClassController;
use App\Http\Controllers\Web\DashboardSearchController;
use App\Http\Controllers\Web\DashboardSeatPlanController;
use App\Http\Controllers\Web\DashboardSmsController;
use App\Http\Controllers\Web\DashboardStaffAttendanceController;
use App\Http\Controllers\Web\DashboardStudentController;
use App\Http\Controllers\Web\DashboardStudentIdCardController;
use App\Http\Controllers\Web\DashboardTeacherController;
use App\Http\Controllers\Web\DashboardTestimonialController;
use App\Http\Controllers\Web\DashboardTransportController;
use App\Http\Controllers\Web\DashboardUserController;
use App\Http\Controllers\Web\DashboardVisitorLogController;
use App\Http\Controllers\Web\MessageController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PortalAdmissionController;
use App\Http\Controllers\Web\PortalProgressController;
use App\Http\Controllers\Web\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthSessionController::class, 'destroy'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/sent', [MessageController::class, 'sent'])->name('sent');
        Route::get('/create', [MessageController::class, 'create'])->name('create');
        Route::post('/', [MessageController::class, 'store'])->name('store');
        Route::get('/{id}', [MessageController::class, 'show'])->name('show');
        Route::delete('/{id}', [MessageController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('student_guardian')->group(function () {
        Route::get('/student/dashboard', function () {
            $user = auth()->user();
            $student = $user->student()->first();
            $stats = [
                'attendance_rate' => 0,
                'exam_count' => 0,
                'fee_paid' => 0,
                'certificate_count' => 0,
            ];
            if ($student) {
                $totalAtt = $student->attendances()->count();
                $presentAtt = $student->attendances()->whereIn('status', ['present', 'late', 'half_day'])->count();
                $stats['attendance_rate'] = $totalAtt > 0 ? round(100 * $presentAtt / $totalAtt) : 0;
                $stats['exam_count'] = $student->examResults()->count();
                $stats['fee_paid'] = $student->feePayments()->where('status', 'completed')->count();
                $stats['certificate_count'] = $student->certificates()->count();
            }
            $recentResults = $student ? $student->examResults()->with('exam', 'subject')->latest()->limit(5)->get() : collect();

            return view('student/dashboard', compact('user', 'stats', 'recentResults'));
        })->name('student.dashboard');

        Route::get('/guardian/dashboard', function () {
            $user = auth()->user();
            $guardian = $user->guardian()->first();
            $students = $guardian ? $guardian->students()->with(['user', 'class', 'section'])->get() : collect();
            $studentCount = $students->count();
            $pendingFees = 0;
            $noticeCount = 0;

            $assignments = collect();
            if ($guardian) {
                $studentIds = $students->pluck('id');
                $assignments = \App\Models\Assignment::with(['subject', 'submissions' => function ($q) use ($studentIds) {
                    $q->whereIn('student_id', $studentIds);
                }])
                    ->whereHas('submissions', function ($q) use ($studentIds) {
                        $q->whereIn('student_id', $studentIds);
                    })
                    ->latest()
                    ->limit(10)
                    ->get();
            }

            return view('guardian/dashboard', compact('user', 'students', 'studentCount', 'pendingFees', 'noticeCount', 'assignments'));
        })->name('guardian.dashboard');

        Route::post('/guardian/assignments/{submission}/notes', function (\Illuminate\Http\Request $request, \App\Models\AssignmentSubmission $submission) {
            $guardian = auth()->user()->guardian()->first();
            if (! $guardian) {
                return back()->with('error', __('Guardian not found.'));
            }

            $studentIds = $guardian->students()->pluck('students.id');
            if (! $studentIds->contains($submission->student_id)) {
                abort(403, __('Unauthorized.'));
            }

            $assignment = $submission->assignment;
            if (! $assignment->allow_guardian_notes) {
                return back()->with('error', __('Guardian notes are not allowed for this assignment.'));
            }

            $data = $request->validate([
                'guardian_notes' => 'required|string|max:2000',
            ]);

            $submission->update([
                'guardian_notes' => $data['guardian_notes'],
                'guardian_id' => $guardian->id,
                'guardian_notified_at' => now(),
            ]);

            return back()->with('status', __('Guardian notes saved.'));
        })->name('guardian.assignments.notes');
    });

    Route::get('/dashboard/locale/{locale}', [DashboardLocaleController::class, 'switch'])->name('dashboard.locale.switch');

    Route::get('/dashboard/search', [DashboardSearchController::class, 'search'])->name('dashboard.search');

    Route::post('/dashboard/favorites/toggle', [DashboardFavoriteController::class, 'toggle'])->name('dashboard.favorites.toggle');

    Route::get('/dashboard/profile', [DashboardProfileController::class, 'edit'])->name('dashboard.profile.edit');
    Route::put('/dashboard/profile', [DashboardProfileController::class, 'update'])->name('dashboard.profile.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/dashboard/onboarding', [DashboardOnboardingController::class, 'index'])->name('dashboard.onboarding');

    Route::get('/portal/admission', [PortalAdmissionController::class, 'show'])->name('portal.admission');
    Route::get('/portal/progress', [PortalProgressController::class, 'index'])->name('portal.progress');

    Route::get('/dashboard/students/promote', [DashboardStudentController::class, 'promoteForm'])->name('dashboard.students.promote');
    Route::post('/dashboard/students/promote', [DashboardStudentController::class, 'promote'])->name('dashboard.students.promote.store');

    Route::get('/dashboard/students/create', [DashboardStudentController::class, 'create'])->name('dashboard.students.create');
    Route::post('/dashboard/students', [DashboardStudentController::class, 'store'])->name('dashboard.students.store');
    Route::get('/dashboard/students/{student}/edit', [DashboardStudentController::class, 'edit'])->name('dashboard.students.edit');
    Route::put('/dashboard/students/{student}', [DashboardStudentController::class, 'update'])->name('dashboard.students.update');
    Route::delete('/dashboard/students/{student}', [DashboardStudentController::class, 'destroy'])->name('dashboard.students.destroy');
    Route::get('/dashboard/students/{student}', [DashboardStudentController::class, 'show'])->name('dashboard.students.show');
    Route::get('/dashboard/students/{student}/results', [DashboardExamResultController::class, 'studentResults'])->name('dashboard.students.results');
    Route::get('/dashboard/students', [DashboardModulesController::class, 'students'])->name('dashboard.students');

    Route::get('/dashboard/my-results', [DashboardExamResultController::class, 'myResults'])->name('dashboard.exams.my-results');
    Route::get('/dashboard/exams/{exam}/results', [DashboardExamResultController::class, 'index'])->name('dashboard.exams.results');
    Route::post('/dashboard/exams/{exam}/results', [DashboardExamResultController::class, 'store'])->name('dashboard.exams.results.store');
    Route::get('/dashboard/exams/{exam}/results/export', [DashboardExamResultController::class, 'export'])->name('dashboard.exams.results.export');
    Route::get('/dashboard/exams/{exam}/results/{result}/marksheet', [DashboardExamResultController::class, 'downloadMarksheet'])->name('dashboard.exams.results.marksheet');
    Route::post('/dashboard/exams/{exam}/publish', [DashboardExamResultController::class, 'publish'])->name('dashboard.exams.publish');
    Route::post('/dashboard/exams/{exam}/unpublish', [DashboardExamResultController::class, 'unpublish'])->name('dashboard.exams.unpublish');

    Route::get('/dashboard/reports', [DashboardReportController::class, 'index'])->name('dashboard.reports');
    Route::get('/dashboard/reports/fees', [DashboardReportController::class, 'fees'])->name('dashboard.reports.fees');
    Route::get('/dashboard/reports/attendance', [DashboardReportController::class, 'attendance'])->name('dashboard.reports.attendance');
    Route::get('/dashboard/reports/students', [DashboardReportController::class, 'students'])->name('dashboard.reports.students');
    Route::get('/dashboard/reports/export/{type}', [DashboardReportController::class, 'export'])->name('dashboard.reports.export');

    Route::get('/dashboard/analytics', [DashboardReportController::class, 'analytics'])->name('dashboard.analytics');
    Route::get('/dashboard/reports/builder', [DashboardReportBuilderController::class, 'index'])->name('dashboard.reports.builder');
    Route::post('/dashboard/reports/builder/export', [DashboardReportBuilderController::class, 'export'])->name('dashboard.reports.builder.export');

    Route::get('/dashboard/events', [DashboardEventController::class, 'index'])->name('dashboard.events');
    Route::get('/dashboard/events/calendar', [DashboardEventController::class, 'calendar'])->name('dashboard.events.calendar');

    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard/events/create', [DashboardEventController::class, 'create'])->name('dashboard.events.create');
        Route::post('/dashboard/events', [DashboardEventController::class, 'store'])->name('dashboard.events.store');
        Route::get('/dashboard/events/{event}/edit', [DashboardEventController::class, 'edit'])->name('dashboard.events.edit');
        Route::put('/dashboard/events/{event}', [DashboardEventController::class, 'update'])->name('dashboard.events.update');
        Route::delete('/dashboard/events/{event}', [DashboardEventController::class, 'destroy'])->name('dashboard.events.destroy');
    });

    Route::get('/dashboard/help', [DashboardHelpController::class, 'index'])->name('dashboard.help');
    Route::get('/dashboard/about', function () {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        return view('dashboard.software');
    })->name('dashboard.about');

    Route::get('/dashboard/bulk', [DashboardBulkController::class, 'index'])->name('dashboard.bulk');
    Route::get('/dashboard/bulk/export/{resource}', [DashboardBulkController::class, 'export'])->name('dashboard.bulk.export');
    Route::get('/dashboard/bulk/import/{resource}', [DashboardBulkController::class, 'import'])->name('dashboard.bulk.import');
    Route::post('/dashboard/bulk/import/{resource}', [DashboardBulkController::class, 'importStore'])->name('dashboard.bulk.import.store');

    Route::get('/dashboard/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/dashboard/notifications/list', [NotificationController::class, 'list'])->name('notifications.list');
    Route::get('/dashboard/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/dashboard/notifications/mark-all', [NotificationController::class, 'markAllRead'])->name('notifications.markAll');
    Route::get('/dashboard/notifications/preferences', [DashboardNotificationPreferencesController::class, 'show'])->name('notifications.preferences');
    Route::post('/dashboard/notifications/preferences', [DashboardNotificationPreferencesController::class, 'update'])->name('notifications.preferences.update');

    Route::get('/dashboard/teachers/create', [DashboardTeacherController::class, 'create'])->name('dashboard.teachers.create');
    Route::post('/dashboard/teachers', [DashboardTeacherController::class, 'store'])->name('dashboard.teachers.store');
    Route::get('/dashboard/teachers/{teacher}/edit', [DashboardTeacherController::class, 'edit'])->name('dashboard.teachers.edit');
    Route::put('/dashboard/teachers/{teacher}', [DashboardTeacherController::class, 'update'])->name('dashboard.teachers.update');
    Route::delete('/dashboard/teachers/{teacher}', [DashboardTeacherController::class, 'destroy'])->name('dashboard.teachers.destroy');
    Route::get('/dashboard/teachers/{teacher}', [DashboardTeacherController::class, 'show'])->name('dashboard.teachers.show');
    Route::get('/dashboard/teachers', [DashboardModulesController::class, 'teachers'])->name('dashboard.teachers');
    Route::get('/dashboard/staff', [DashboardModulesController::class, 'staff'])->name('dashboard.staff');

    Route::get('/dashboard/parents/create', [DashboardGuardianController::class, 'create'])->name('dashboard.parents.create');
    Route::post('/dashboard/parents', [DashboardGuardianController::class, 'store'])->name('dashboard.parents.store');
    Route::get('/dashboard/parents/{guardian}/edit', [DashboardGuardianController::class, 'edit'])->name('dashboard.parents.edit');
    Route::put('/dashboard/parents/{guardian}', [DashboardGuardianController::class, 'update'])->name('dashboard.parents.update');
    Route::delete('/dashboard/parents/{guardian}', [DashboardGuardianController::class, 'destroy'])->name('dashboard.parents.destroy');
    Route::get('/dashboard/parents/{guardian}', [DashboardGuardianController::class, 'show'])->name('dashboard.parents.show');
    Route::get('/dashboard/parents', [DashboardModulesController::class, 'parents'])->name('dashboard.parents');

    Route::get('/dashboard/classes/create', [DashboardSchoolClassController::class, 'create'])->name('dashboard.classes.create');
    Route::post('/dashboard/classes', [DashboardSchoolClassController::class, 'store'])->name('dashboard.classes.store');
    Route::get('/dashboard/classes/{class}/edit', [DashboardSchoolClassController::class, 'edit'])->name('dashboard.classes.edit');
    Route::put('/dashboard/classes/{class}', [DashboardSchoolClassController::class, 'update'])->name('dashboard.classes.update');
    Route::delete('/dashboard/classes/{class}', [DashboardSchoolClassController::class, 'destroy'])->name('dashboard.classes.destroy');
    Route::get('/dashboard/classes/{class}', [DashboardSchoolClassController::class, 'show'])->name('dashboard.classes.show');
    Route::get('/dashboard/classes', [DashboardModulesController::class, 'classes'])->name('dashboard.classes');

    Route::get('/dashboard/attendance/create', [DashboardAttendanceController::class, 'create'])->name('dashboard.attendance.create');
    Route::post('/dashboard/attendance', [DashboardAttendanceController::class, 'store'])->name('dashboard.attendance.store');
    Route::get('/dashboard/attendance/bulk', [DashboardAttendanceController::class, 'bulk'])->name('dashboard.attendance.bulk');
    Route::post('/dashboard/attendance/bulk', [DashboardAttendanceController::class, 'bulkStore'])->name('dashboard.attendance.bulk.store');
    Route::get('/dashboard/attendance', [DashboardModulesController::class, 'attendance'])->name('dashboard.attendance');

    Route::get('/dashboard/staff-attendance', [DashboardStaffAttendanceController::class, 'index'])->name('dashboard.staff-attendance.index');
    Route::post('/dashboard/staff-attendance', [DashboardStaffAttendanceController::class, 'store'])->name('dashboard.staff-attendance.store');
    Route::get('/dashboard/staff-attendance/report', [DashboardStaffAttendanceController::class, 'report'])->name('dashboard.staff-attendance.report');

    Route::prefix('dashboard/leaves')->name('dashboard.leaves.')->group(function () {
        Route::get('/', [DashboardLeaveController::class, 'index'])->name('index');
        Route::get('/create', [DashboardLeaveController::class, 'create'])->name('create');
        Route::post('/', [DashboardLeaveController::class, 'store'])->name('store');
        Route::get('/{leave}', [DashboardLeaveController::class, 'show'])->name('show');
        Route::post('/{leave}/approve', [DashboardLeaveController::class, 'approve'])->name('approve');
        Route::post('/{leave}/reject', [DashboardLeaveController::class, 'reject'])->name('reject');
        Route::post('/{leave}/cancel', [DashboardLeaveController::class, 'cancel'])->name('cancel');
    });

    Route::prefix('dashboard/payroll')->name('dashboard.payroll.')->group(function () {
        Route::get('/structures', [DashboardPayrollController::class, 'structures'])->name('structures');
        Route::post('/structures', [DashboardPayrollController::class, 'storeStructure'])->name('structures.store');
        Route::get('/generate', [DashboardPayrollController::class, 'generate'])->name('generate');
        Route::post('/generate', [DashboardPayrollController::class, 'generateStore'])->name('generate.store');
        Route::get('/payslips', [DashboardPayrollController::class, 'payslips'])->name('payslips');
        Route::get('/payslips/{payslip}', [DashboardPayrollController::class, 'showPayslip'])->name('payslips.show');
        Route::post('/payslips/{payslip}/paid', [DashboardPayrollController::class, 'markPaid'])->name('payslips.markPaid');
    });

    Route::prefix('dashboard/transport')->name('dashboard.transport.')->group(function () {
        Route::get('/vehicles', [DashboardTransportController::class, 'vehicles'])->name('vehicles.index');
        Route::get('/vehicles/create', [DashboardTransportController::class, 'vehiclesCreate'])->name('vehicles.create');
        Route::post('/vehicles', [DashboardTransportController::class, 'vehiclesStore'])->name('vehicles.store');
        Route::get('/vehicles/{vehicle}/edit', [DashboardTransportController::class, 'vehiclesEdit'])->name('vehicles.edit');
        Route::put('/vehicles/{vehicle}', [DashboardTransportController::class, 'vehiclesUpdate'])->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}', [DashboardTransportController::class, 'vehiclesDestroy'])->name('vehicles.destroy');

        Route::get('/routes', [DashboardTransportController::class, 'routes'])->name('routes.index');
        Route::get('/routes/create', [DashboardTransportController::class, 'routesCreate'])->name('routes.create');
        Route::post('/routes', [DashboardTransportController::class, 'routesStore'])->name('routes.store');
        Route::get('/routes/{route}/edit', [DashboardTransportController::class, 'routesEdit'])->name('routes.edit');
        Route::put('/routes/{route}', [DashboardTransportController::class, 'routesUpdate'])->name('routes.update');
        Route::delete('/routes/{route}', [DashboardTransportController::class, 'routesDestroy'])->name('routes.destroy');

        Route::get('/assignments', [DashboardTransportController::class, 'assignments'])->name('assignments.index');
        Route::post('/assignments', [DashboardTransportController::class, 'assignmentsStore'])->name('assignments.store');
        Route::delete('/assignments/{assignment}', [DashboardTransportController::class, 'assignmentsDestroy'])->name('assignments.destroy');
    });

    Route::get('/dashboard/exams/create', [DashboardExamController::class, 'create'])->name('dashboard.exams.create');
    Route::post('/dashboard/exams', [DashboardExamController::class, 'store'])->name('dashboard.exams.store');
    Route::post('/dashboard/exams/{exam}/visibility', [DashboardExamController::class, 'publishToggle'])->name('dashboard.exams.visibility');
    Route::get('/dashboard/exams', [DashboardModulesController::class, 'exams'])->name('dashboard.exams');

    Route::get('/dashboard/fees/create', [DashboardFeeController::class, 'create'])->name('dashboard.fees.create');
    Route::post('/dashboard/fees', [DashboardFeeController::class, 'store'])->name('dashboard.fees.store');
    Route::get('/dashboard/fees/{fee}/edit', [DashboardFeeController::class, 'edit'])->name('dashboard.fees.edit');
    Route::put('/dashboard/fees/{fee}', [DashboardFeeController::class, 'update'])->name('dashboard.fees.update');
    Route::delete('/dashboard/fees/{fee}', [DashboardFeeController::class, 'destroy'])->name('dashboard.fees.destroy');
    Route::get('/dashboard/fees', [DashboardModulesController::class, 'fees'])->name('dashboard.fees');

    Route::prefix('dashboard/fee-payments')->name('dashboard.fee-payments.')->group(function () {
        Route::get('/', [DashboardFeePaymentController::class, 'index'])->name('index');
        Route::get('/{feePayment}', [DashboardFeePaymentController::class, 'show'])->name('show');
        Route::post('/{feePayment}/approve', [DashboardFeePaymentController::class, 'approve'])->name('approve');
        Route::post('/{feePayment}/cancel', [DashboardFeePaymentController::class, 'cancel'])->name('cancel');
    });

    Route::middleware(['permission:manage_expenses'])->group(function () {
        Route::get('/dashboard/expenses', [DashboardExpenseController::class, 'index'])->name('dashboard.expenses.index');
        Route::get('/dashboard/expenses/create', [DashboardExpenseController::class, 'create'])->name('dashboard.expenses.create');
        Route::post('/dashboard/expenses', [DashboardExpenseController::class, 'store'])->name('dashboard.expenses.store');
        Route::get('/dashboard/expenses/{expense}/edit', [DashboardExpenseController::class, 'edit'])->name('dashboard.expenses.edit');
        Route::put('/dashboard/expenses/{expense}', [DashboardExpenseController::class, 'update'])->name('dashboard.expenses.update');
        Route::delete('/dashboard/expenses/{expense}', [DashboardExpenseController::class, 'destroy'])->name('dashboard.expenses.destroy');
        Route::get('/dashboard/expenses-export', [DashboardExpenseController::class, 'export'])->name('dashboard.expenses.export');
    });

    Route::middleware(['permission:manage_expenses'])->group(function () {
        Route::resource('dashboard/expense-categories', DashboardExpenseCategoryController::class)->names('dashboard.expense-categories');
        Route::resource('dashboard/budgets', DashboardBudgetController::class)->names('dashboard.budgets');
    });

    Route::middleware(['permission:manage_expenses'])->group(function () {
        Route::get('/dashboard/bank-reconciliation', [DashboardBankReconciliationController::class, 'index'])->name('dashboard.bank-reconciliation.index');
        Route::get('/dashboard/bank-reconciliation/reconcile', [DashboardBankReconciliationController::class, 'reconcile'])->name('dashboard.bank-reconciliation.reconcile');
    });

    Route::prefix('dashboard/ledger')->name('dashboard.ledger.')->group(function () {
        Route::get('/', [DashboardLedgerController::class, 'index'])->name('index');
        Route::get('/journal', [DashboardLedgerController::class, 'journalForm'])->name('journal');
        Route::post('/journal', [DashboardLedgerController::class, 'journalStore'])->name('journal.store');
        Route::get('/cashbook', [DashboardLedgerController::class, 'cashbook'])->name('cashbook');
        Route::get('/bankbook', [DashboardLedgerController::class, 'bankbook'])->name('bankbook');
    });

    Route::prefix('dashboard/reports')->name('dashboard.reports.')->group(function () {
        Route::get('/income-statement', [DashboardLedgerController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/balance-sheet', [DashboardLedgerController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/cash-flow', [DashboardLedgerController::class, 'cashFlow'])->name('cash-flow');
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::prefix('dashboard/settings')->name('dashboard.settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Web\DashboardSettingController::class, 'index'])->name('index');
            Route::get('/general', [\App\Http\Controllers\Web\DashboardSettingController::class, 'general'])->name('general');
            Route::post('/general', [\App\Http\Controllers\Web\DashboardSettingController::class, 'updateGeneral'])->name('update.general');
            Route::post('/theme', [\App\Http\Controllers\Web\DashboardSettingController::class, 'updateTheme'])->name('update.theme');
            Route::post('/localization', [\App\Http\Controllers\Web\DashboardSettingController::class, 'updateLocalization'])->name('update.localization');
            Route::post('/payment', [\App\Http\Controllers\Web\DashboardSettingController::class, 'updatePayment'])->name('update.payment');
            Route::post('/library', [\App\Http\Controllers\Web\DashboardSettingController::class, 'updateLibrary'])->name('update.library');
            Route::get('/cms', [\App\Http\Controllers\Web\DashboardSettingController::class, 'cmsSettings'])->name('cms');
            Route::post('/cms', [\App\Http\Controllers\Web\DashboardSettingController::class, 'updateGeneral'])->name('update.cms');
            Route::get('/global-labels', [\App\Http\Controllers\Web\DashboardSettingController::class, 'globalLabels'])->name('global-labels');
            Route::post('/global-labels', [\App\Http\Controllers\Web\DashboardSettingController::class, 'updateGlobalLabels'])->name('update.global-labels');
            Route::get('/about', [\App\Http\Controllers\Web\DashboardSettingController::class, 'about'])->name('about');
            Route::post('/about', [\App\Http\Controllers\Web\DashboardSettingController::class, 'updateAbout'])->name('update.about');
        });

        Route::get('/dashboard/cms/pages', [CmsWebController::class, 'pages'])->name('dashboard.cms.pages');
        Route::get('/dashboard/cms/edit/{page}', [CmsWebController::class, 'edit'])->name('dashboard.cms.edit');
        Route::put('/dashboard/cms/edit/{page}', [CmsWebController::class, 'update'])->name('dashboard.cms.update');

        Route::get('/dashboard/news', [DashboardNewsController::class, 'index'])->name('dashboard.news.index');
        Route::get('/dashboard/news/create', [DashboardNewsController::class, 'create'])->name('dashboard.news.create');
        Route::post('/dashboard/news', [DashboardNewsController::class, 'store'])->name('dashboard.news.store');
        Route::get('/dashboard/news/{news}/edit', [DashboardNewsController::class, 'edit'])->name('dashboard.news.edit');
        Route::put('/dashboard/news/{news}', [DashboardNewsController::class, 'update'])->name('dashboard.news.update');
        Route::delete('/dashboard/news/{news}', [DashboardNewsController::class, 'destroy'])->name('dashboard.news.destroy');
        Route::post('/dashboard/news/bulk', [DashboardNewsController::class, 'bulk'])->name('dashboard.news.bulk');

        Route::get('/dashboard/gallery', [DashboardGalleryController::class, 'index'])->name('dashboard.gallery.index');
        Route::get('/dashboard/gallery/create', [DashboardGalleryController::class, 'create'])->name('dashboard.gallery.create');
        Route::post('/dashboard/gallery', [DashboardGalleryController::class, 'store'])->name('dashboard.gallery.store');
        Route::get('/dashboard/gallery/{gallery}/edit', [DashboardGalleryController::class, 'edit'])->name('dashboard.gallery.edit');
        Route::put('/dashboard/gallery/{gallery}', [DashboardGalleryController::class, 'update'])->name('dashboard.gallery.update');
        Route::delete('/dashboard/gallery/{gallery}', [DashboardGalleryController::class, 'destroy'])->name('dashboard.gallery.destroy');

        Route::get('/dashboard/announcements', [DashboardAnnouncementController::class, 'index'])->name('dashboard.announcements.index');
        Route::get('/dashboard/announcements/create', [DashboardAnnouncementController::class, 'create'])->name('dashboard.announcements.create');
        Route::post('/dashboard/announcements', [DashboardAnnouncementController::class, 'store'])->name('dashboard.announcements.store');
        Route::get('/dashboard/announcements/{announcement}/edit', [DashboardAnnouncementController::class, 'edit'])->name('dashboard.announcements.edit');
        Route::put('/dashboard/announcements/{announcement}', [DashboardAnnouncementController::class, 'update'])->name('dashboard.announcements.update');
        Route::delete('/dashboard/announcements/{announcement}', [DashboardAnnouncementController::class, 'destroy'])->name('dashboard.announcements.destroy');
        Route::post('/dashboard/announcements/bulk', [DashboardAnnouncementController::class, 'bulk'])->name('dashboard.announcements.bulk');

        Route::get('/dashboard/notices', [DashboardNoticeController::class, 'index'])->name('dashboard.notices.index');
        Route::get('/dashboard/notices/create', [DashboardNoticeController::class, 'create'])->name('dashboard.notices.create');
        Route::post('/dashboard/notices', [DashboardNoticeController::class, 'store'])->name('dashboard.notices.store');
        Route::get('/dashboard/notices/{notice}/edit', [DashboardNoticeController::class, 'edit'])->name('dashboard.notices.edit');
        Route::put('/dashboard/notices/{notice}', [DashboardNoticeController::class, 'update'])->name('dashboard.notices.update');
        Route::delete('/dashboard/notices/{notice}', [DashboardNoticeController::class, 'destroy'])->name('dashboard.notices.destroy');
        Route::post('/dashboard/notices/bulk', [DashboardNoticeController::class, 'bulk'])->name('dashboard.notices.bulk');

        Route::get('/dashboard/contact-submissions', [DashboardModulesController::class, 'contactSubmissions'])->name('dashboard.contact-submissions');
        Route::get('/dashboard/contact-submissions/export', [DashboardModulesController::class, 'contactSubmissionsExport'])->name('dashboard.contact-submissions.export');

        Route::get('/dashboard/documents', [DashboardDocumentController::class, 'index'])->name('dashboard.documents.index');
        Route::get('/dashboard/documents/create', [DashboardDocumentController::class, 'create'])->name('dashboard.documents.create');
        Route::post('/dashboard/documents', [DashboardDocumentController::class, 'store'])->name('dashboard.documents.store');
        Route::get('/dashboard/documents/{document}/edit', [DashboardDocumentController::class, 'edit'])->name('dashboard.documents.edit');
        Route::put('/dashboard/documents/{document}', [DashboardDocumentController::class, 'update'])->name('dashboard.documents.update');
        Route::delete('/dashboard/documents/{document}', [DashboardDocumentController::class, 'destroy'])->name('dashboard.documents.destroy');

        Route::get('/dashboard/media', [DashboardMediaController::class, 'index'])->name('dashboard.media.index');
        Route::post('/dashboard/media', [DashboardMediaController::class, 'store'])->name('dashboard.media.store');
        Route::get('/dashboard/media/{media}/download', [DashboardMediaController::class, 'download'])->name('dashboard.media.download');
        Route::delete('/dashboard/media/{media}', [DashboardMediaController::class, 'destroy'])->name('dashboard.media.destroy');

        Route::get('/dashboard/admissions', [DashboardAdmissionController::class, 'index'])->name('dashboard.admissions.index');
        Route::post('/dashboard/admissions/toggle', [DashboardAdmissionController::class, 'toggleOpen'])->name('dashboard.admissions.toggle');
        Route::get('/dashboard/admissions/{admission}', [DashboardAdmissionController::class, 'show'])->name('dashboard.admissions.show');
        Route::post('/dashboard/admissions/{admission}/tests', [DashboardAdmissionController::class, 'scheduleTest'])->name('dashboard.admissions.tests.store');
        Route::put('/dashboard/admissions/{admission}/tests/{test}', [DashboardAdmissionController::class, 'updateTest'])->name('dashboard.admissions.tests.update');
        Route::delete('/dashboard/admissions/{admission}/tests/{test}', [DashboardAdmissionController::class, 'deleteTest'])->name('dashboard.admissions.tests.destroy');
        Route::post('/dashboard/admissions/{admission}/status', [DashboardAdmissionController::class, 'updateStatus'])->name('dashboard.admissions.status.update');
        Route::post('/dashboard/admissions/{admission}/verify-payment', [DashboardAdmissionController::class, 'verifyPayment'])->name('dashboard.admissions.verify-payment');

        Route::prefix('dashboard/backup')->name('dashboard.backup.')->group(function () {
            Route::get('/', [DashboardBackupController::class, 'index'])->name('index');
            Route::post('/create', [DashboardBackupController::class, 'create'])->name('create');
            Route::get('/download/{file}', [DashboardBackupController::class, 'download'])->name('download');
            Route::post('/restore/{file}', [DashboardBackupController::class, 'restore'])->name('restore');
            Route::delete('/{file}', [DashboardBackupController::class, 'destroy'])->name('destroy');
        });

        Route::get('/dashboard/activity', [DashboardActivityController::class, 'index'])->name('dashboard.activity.index');

        Route::get('/dashboard/visitor-logs', [DashboardVisitorLogController::class, 'index'])->name('dashboard.visitor-logs.index');

        Route::prefix('dashboard/certificates')->name('dashboard.certificates.')->group(function () {
            Route::get('/', [DashboardCertificateController::class, 'index'])->name('index');
            Route::get('/create', [DashboardCertificateController::class, 'create'])->name('create');
            Route::post('/', [DashboardCertificateController::class, 'store'])->name('store');
            Route::get('/{certificate}', [DashboardCertificateController::class, 'show'])->name('show');
            Route::get('/{certificate}/edit', [DashboardCertificateController::class, 'edit'])->name('edit');
            Route::put('/{certificate}', [DashboardCertificateController::class, 'update'])->name('update');
            Route::delete('/{certificate}', [DashboardCertificateController::class, 'destroy'])->name('destroy');
            Route::get('/{certificate}/print', [DashboardCertificateController::class, 'print'])->name('print');
        });

        Route::prefix('dashboard/progress-reports')->name('dashboard.progress-reports.')->group(function () {
            Route::get('/', [DashboardProgressReportController::class, 'index'])->name('index');
            Route::get('/{student}/generate', [DashboardProgressReportController::class, 'generate'])->name('generate');
        });

        Route::prefix('dashboard/seat-plans')->name('dashboard.seat-plans.')->group(function () {
            Route::get('/', [DashboardSeatPlanController::class, 'index'])->name('index');
            Route::get('/{exam}/generate', [DashboardSeatPlanController::class, 'generate'])->name('generate');
        });

        Route::prefix('dashboard/routines')->name('dashboard.routines.')->group(function () {
            Route::get('/', [DashboardRoutineController::class, 'index'])->name('index');
            Route::get('/create', [DashboardRoutineController::class, 'create'])->name('create');
            Route::post('/', [DashboardRoutineController::class, 'store'])->name('store');
            Route::get('/{routine}', [DashboardRoutineController::class, 'show'])->name('show');
            Route::get('/{routine}/edit', [DashboardRoutineController::class, 'edit'])->name('edit');
            Route::put('/{routine}', [DashboardRoutineController::class, 'update'])->name('update');
            Route::delete('/{routine}', [DashboardRoutineController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('dashboard/assignments')->name('dashboard.assignments.')->group(function () {
            Route::get('/', [DashboardAssignmentController::class, 'index'])->name('index');
            Route::get('/create', [DashboardAssignmentController::class, 'create'])->name('create');
            Route::post('/', [DashboardAssignmentController::class, 'store'])->name('store');
            Route::get('/{assignment}', [DashboardAssignmentController::class, 'show'])->name('show');
            Route::get('/{assignment}/edit', [DashboardAssignmentController::class, 'edit'])->name('edit');
            Route::put('/{assignment}', [DashboardAssignmentController::class, 'update'])->name('update');
            Route::delete('/{assignment}', [DashboardAssignmentController::class, 'destroy'])->name('destroy');
            Route::get('/{assignment}/submissions', [DashboardAssignmentController::class, 'submissions'])->name('submissions');
            Route::post('/submissions/{submission}/grade', [DashboardAssignmentController::class, 'grade'])->name('grade');
        });

        Route::prefix('dashboard/admit-cards')->name('dashboard.admit-cards.')->group(function () {
            Route::get('/', [DashboardAdmitCardController::class, 'index'])->name('index');
            Route::get('/create', [DashboardAdmitCardController::class, 'create'])->name('create');
            Route::post('/', [DashboardAdmitCardController::class, 'store'])->name('store');
            Route::get('/batch/create', [DashboardAdmitCardController::class, 'batchCreate'])->name('batch.create');
            Route::post('/batch', [DashboardAdmitCardController::class, 'batchStore'])->name('batch.store');
            Route::get('/{admitCard}', [DashboardAdmitCardController::class, 'show'])->name('show');
            Route::get('/{admitCard}/edit', [DashboardAdmitCardController::class, 'edit'])->name('edit');
            Route::put('/{admitCard}', [DashboardAdmitCardController::class, 'update'])->name('update');
            Route::delete('/{admitCard}', [DashboardAdmitCardController::class, 'destroy'])->name('destroy');
            Route::get('/{admitCard}/print', [DashboardAdmitCardController::class, 'print'])->name('print');
            Route::get('/{admitCard}/preview', [DashboardAdmitCardController::class, 'preview'])->name('preview');
        });

        Route::prefix('dashboard/student-id-cards')->name('dashboard.student-id-cards.')->group(function () {
            Route::get('/', [DashboardStudentIdCardController::class, 'index'])->name('index');
            Route::get('/create', [DashboardStudentIdCardController::class, 'create'])->name('create');
            Route::post('/', [DashboardStudentIdCardController::class, 'store'])->name('store');
            Route::get('/batch/create', [DashboardStudentIdCardController::class, 'batchCreate'])->name('batch.create');
            Route::post('/batch', [DashboardStudentIdCardController::class, 'batchStore'])->name('batch.store');
            Route::get('/{studentIdCard}', [DashboardStudentIdCardController::class, 'show'])->name('show');
            Route::get('/{studentIdCard}/edit', [DashboardStudentIdCardController::class, 'edit'])->name('edit');
            Route::put('/{studentIdCard}', [DashboardStudentIdCardController::class, 'update'])->name('update');
            Route::delete('/{studentIdCard}', [DashboardStudentIdCardController::class, 'destroy'])->name('destroy');
            Route::get('/{studentIdCard}/print', [DashboardStudentIdCardController::class, 'print'])->name('print');
            Route::get('/{studentIdCard}/preview', [DashboardStudentIdCardController::class, 'preview'])->name('preview');
        });

        Route::get('/dashboard/communications', [DashboardCommunicationsController::class, 'index'])->name('dashboard.communications');

        Route::prefix('dashboard/sms')->name('dashboard.sms.')->group(function () {
            Route::get('/', [DashboardSmsController::class, 'index'])->name('index');
            Route::get('/compose', [DashboardSmsController::class, 'compose'])->name('compose');
            Route::post('/preview', [DashboardSmsController::class, 'preview'])->name('preview');
            Route::post('/send', [DashboardSmsController::class, 'send'])->name('send');
            Route::get('/templates', [DashboardSmsController::class, 'templates'])->name('templates');
            Route::get('/due-reminder', [DashboardSmsController::class, 'dueReminder'])->name('dashboard.sms.due-reminder');
            Route::post('/due-reminder', [DashboardSmsController::class, 'dueReminder'])->name('dashboard.sms.due-reminder.send');
        });

        Route::prefix('dashboard/transport')->name('dashboard.transport.')->group(function () {
            Route::get('/vehicles', [DashboardTransportController::class, 'vehicles'])->name('vehicles.index');
            Route::get('/vehicles/create', [DashboardTransportController::class, 'vehiclesCreate'])->name('vehicles.create');
            Route::post('/vehicles', [DashboardTransportController::class, 'vehiclesStore'])->name('vehicles.store');
            Route::get('/vehicles/{vehicle}/edit', [DashboardTransportController::class, 'vehiclesEdit'])->name('vehicles.edit');
            Route::put('/vehicles/{vehicle}', [DashboardTransportController::class, 'vehiclesUpdate'])->name('vehicles.update');
            Route::delete('/vehicles/{vehicle}', [DashboardTransportController::class, 'vehiclesDestroy'])->name('vehicles.destroy');

            Route::get('/routes', [DashboardTransportController::class, 'routes'])->name('routes.index');
            Route::get('/routes/create', [DashboardTransportController::class, 'routesCreate'])->name('routes.create');
            Route::post('/routes', [DashboardTransportController::class, 'routesStore'])->name('routes.store');
            Route::get('/routes/{route}/edit', [DashboardTransportController::class, 'routesEdit'])->name('routes.edit');
            Route::put('/routes/{route}', [DashboardTransportController::class, 'routesUpdate'])->name('routes.update');
            Route::delete('/routes/{route}', [DashboardTransportController::class, 'routesDestroy'])->name('routes.destroy');

            Route::get('/assignments', [DashboardTransportController::class, 'assignments'])->name('assignments.index');
            Route::post('/assignments', [DashboardTransportController::class, 'assignmentsStore'])->name('assignments.store');
            Route::delete('/assignments/{assignment}', [DashboardTransportController::class, 'assignmentsDestroy'])->name('assignments.destroy');
        });

        Route::prefix('dashboard/hostels')->name('dashboard.hostels.')->group(function () {
            Route::get('/', [DashboardHostelController::class, 'index'])->name('index');
            Route::get('/create', [DashboardHostelController::class, 'create'])->name('create');
            Route::post('/', [DashboardHostelController::class, 'store'])->name('store');
            Route::get('/{hostel}', [DashboardHostelController::class, 'show'])->name('show');
            Route::get('/{hostel}/edit', [DashboardHostelController::class, 'edit'])->name('edit');
            Route::put('/{hostel}', [DashboardHostelController::class, 'update'])->name('update');
            Route::delete('/{hostel}', [DashboardHostelController::class, 'destroy'])->name('destroy');
            Route::post('/{hostel}/rooms', [DashboardHostelController::class, 'storeRoom'])->name('rooms.store');
            Route::put('/rooms/{room}', [DashboardHostelController::class, 'updateRoom'])->name('rooms.update');
            Route::delete('/rooms/{room}', [DashboardHostelController::class, 'destroyRoom'])->name('rooms.destroy');
            Route::post('/{hostel}/assignments', [DashboardHostelController::class, 'storeAssignment'])->name('assignments.store');
            Route::delete('/assignments/{assignment}', [DashboardHostelController::class, 'destroyAssignment'])->name('assignments.destroy');
        });

        Route::prefix('dashboard/testimonials')->name('dashboard.testimonials.')->group(function () {
            Route::get('/', [DashboardTestimonialController::class, 'index'])->name('index');
            Route::get('/create', [DashboardTestimonialController::class, 'create'])->name('create');
            Route::post('/', [DashboardTestimonialController::class, 'store'])->name('store');
            Route::get('/{testimonial}', [DashboardTestimonialController::class, 'show'])->name('show');
            Route::get('/{testimonial}/edit', [DashboardTestimonialController::class, 'edit'])->name('edit');
            Route::put('/{testimonial}', [DashboardTestimonialController::class, 'update'])->name('update');
            Route::delete('/{testimonial}', [DashboardTestimonialController::class, 'destroy'])->name('destroy');
            Route::get('/{testimonial}/print', [DashboardTestimonialController::class, 'print'])->name('print');
        });

        // User & Roles Management
        Route::prefix('dashboard/users')->name('dashboard.users.')->group(function () {
            Route::get('/', [DashboardUserController::class, 'index'])->name('index');
            Route::get('/create', [DashboardUserController::class, 'create'])->name('create');
            Route::post('/', [DashboardUserController::class, 'store'])->name('store');
            Route::get('/{user}', [DashboardUserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [DashboardUserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [DashboardUserController::class, 'update'])->name('update');
            Route::delete('/{user}', [DashboardUserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('dashboard/roles')->name('dashboard.roles.')->group(function () {
            Route::get('/', [DashboardRoleController::class, 'index'])->name('index');
            Route::get('/create', [DashboardRoleController::class, 'create'])->name('create');
            Route::post('/', [DashboardRoleController::class, 'store'])->name('store');
            Route::get('/{role}/edit', [DashboardRoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [DashboardRoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [DashboardRoleController::class, 'destroy'])->name('destroy');
        });

        Route::get('/dashboard/permissions', [DashboardPermissionController::class, 'index'])->name('dashboard.permissions.index');

        Route::prefix('dashboard/committee')->name('dashboard.committee.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Web\DashboardCommitteeController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Web\DashboardCommitteeController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Web\DashboardCommitteeController::class, 'store'])->name('store');
            Route::get('/{member}/edit', [\App\Http\Controllers\Web\DashboardCommitteeController::class, 'edit'])->name('edit');
            Route::put('/{member}', [\App\Http\Controllers\Web\DashboardCommitteeController::class, 'update'])->name('update');
            Route::delete('/{member}', [\App\Http\Controllers\Web\DashboardCommitteeController::class, 'destroy'])->name('destroy');
        });

        // Library Management
        Route::prefix('dashboard/library')->name('dashboard.library.')->group(function () {
            Route::resource('books', DashboardBookController::class);
            Route::resource('categories', DashboardBookCategoryController::class)->except(['create', 'show', 'edit']);
            Route::prefix('issues')->name('issues.')->group(function () {
                Route::get('/', [DashboardBookIssueController::class, 'index'])->name('index');
                Route::get('/create', [DashboardBookIssueController::class, 'create'])->name('create');
                Route::post('/', [DashboardBookIssueController::class, 'store'])->name('store');
                Route::get('/{issue}', [DashboardBookIssueController::class, 'show'])->name('show');
                Route::post('/{issue}/return', [DashboardBookIssueController::class, 'returnBook'])->name('return');
                Route::post('/{issue}/fine', [DashboardBookIssueController::class, 'collectFine'])->name('fine');
                Route::post('/{issue}/lost', [DashboardBookIssueController::class, 'markLost'])->name('lost');
                Route::delete('/{issue}', [DashboardBookIssueController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [DashboardLibraryReportController::class, 'index'])->name('index');
                Route::get('/issued', [DashboardLibraryReportController::class, 'currentlyIssued'])->name('issued');
                Route::get('/overdue', [DashboardLibraryReportController::class, 'overdue'])->name('overdue');
                Route::get('/history', [DashboardLibraryReportController::class, 'history'])->name('history');
            });
        });
    });
});
