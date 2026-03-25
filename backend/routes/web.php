<?php

use App\Http\Controllers\Web\AdmissionWebController;
use App\Http\Controllers\Web\AuthSessionController;
use App\Http\Controllers\Web\CmsWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DashboardModulesController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\PaymentsWebController;
use App\Http\Controllers\Web\PortalController;
use App\Http\Controllers\Web\SiteGalleryController;
use App\Http\Controllers\Web\SiteNewsController;
use App\Http\Controllers\Web\SitePageController;
use App\Http\Controllers\Web\DashboardStudentController;
use App\Http\Controllers\Web\DashboardTeacherController;
use App\Http\Controllers\Web\DashboardGuardianController;
use App\Http\Controllers\Web\DashboardSchoolClassController;
use App\Http\Controllers\Web\DashboardAttendanceController;
use App\Http\Controllers\Web\DashboardExamController;
use App\Http\Controllers\Web\DashboardFeeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/about', [SitePageController::class, 'about'])->name('site.about');
Route::get('/academics', [SitePageController::class, 'academics'])->name('site.academics');
Route::get('/admissions', [SitePageController::class, 'admissions'])->name('site.admissions');
Route::get('/students', [SitePageController::class, 'students'])->name('site.students');
Route::get('/faculty', [SitePageController::class, 'faculty'])->name('site.faculty');
Route::get('/news', [SiteNewsController::class, 'index'])->name('site.news');
Route::get('/news/{slug}', [SiteNewsController::class, 'show'])->name('site.news.show');
Route::get('/gallery', [SiteGalleryController::class, 'index'])->name('site.gallery');
Route::get('/contact', [SitePageController::class, 'contact'])->name('site.contact');

Route::get('/payments', [PaymentsWebController::class, 'index'])->name('site.payments');

Route::get('/portal', [PortalController::class, 'index'])->name('portal');
Route::get('/portal/register', function () {
    return redirect()
        ->route('admissions.apply')
        ->with('status', __('Create an account by applying online, or log in if you already have portal access.'));
})->name('portal.register');

Route::get('/admissions/apply', [AdmissionWebController::class, 'apply'])->name('admissions.apply');
Route::get('/admissions/status', [AdmissionWebController::class, 'status'])->name('admissions.status');

Route::middleware('throttle:12,1')->group(function () {
    Route::post('/newsletter', [SitePageController::class, 'newsletterStore'])->name('site.newsletter.store');
    Route::post('/contact', [SitePageController::class, 'contactStore'])->name('site.contact.store');
    Route::post('/contact/feedback', [SitePageController::class, 'feedbackStore'])->name('site.feedback.store');
    Route::post('/contact/complaint', [SitePageController::class, 'complaintStore'])->name('site.complaint.store');
    Route::post('/admissions/scholarship', [SitePageController::class, 'scholarshipStore'])->name('admissions.scholarship.store');
    Route::post('/admissions/apply', [AdmissionWebController::class, 'applyStore'])->name('admissions.apply.store');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/dashboard/students/create', [DashboardStudentController::class, 'create'])->name('dashboard.students.create');
    Route::post('/dashboard/students', [DashboardStudentController::class, 'store'])->name('dashboard.students.store');
    Route::get('/dashboard/students/{student}/edit', [DashboardStudentController::class, 'edit'])->name('dashboard.students.edit');
    Route::put('/dashboard/students/{student}', [DashboardStudentController::class, 'update'])->name('dashboard.students.update');
    Route::delete('/dashboard/students/{student}', [DashboardStudentController::class, 'destroy'])->name('dashboard.students.destroy');
    Route::get('/dashboard/students/{student}', [DashboardStudentController::class, 'show'])->name('dashboard.students.show');
    Route::get('/dashboard/students', [DashboardModulesController::class, 'students'])->name('dashboard.students');

    Route::get('/dashboard/teachers/create', [DashboardTeacherController::class, 'create'])->name('dashboard.teachers.create');
    Route::post('/dashboard/teachers', [DashboardTeacherController::class, 'store'])->name('dashboard.teachers.store');
    Route::get('/dashboard/teachers/{teacher}/edit', [DashboardTeacherController::class, 'edit'])->name('dashboard.teachers.edit');
    Route::put('/dashboard/teachers/{teacher}', [DashboardTeacherController::class, 'update'])->name('dashboard.teachers.update');
    Route::delete('/dashboard/teachers/{teacher}', [DashboardTeacherController::class, 'destroy'])->name('dashboard.teachers.destroy');
    Route::get('/dashboard/teachers/{teacher}', [DashboardTeacherController::class, 'show'])->name('dashboard.teachers.show');
    Route::get('/dashboard/teachers', [DashboardModulesController::class, 'teachers'])->name('dashboard.teachers');

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
    Route::get('/dashboard/attendance', [DashboardModulesController::class, 'attendance'])->name('dashboard.attendance');

    Route::get('/dashboard/exams/create', [DashboardExamController::class, 'create'])->name('dashboard.exams.create');
    Route::post('/dashboard/exams', [DashboardExamController::class, 'store'])->name('dashboard.exams.store');
    Route::get('/dashboard/exams', [DashboardModulesController::class, 'exams'])->name('dashboard.exams');

    Route::get('/dashboard/fees/create', [DashboardFeeController::class, 'create'])->name('dashboard.fees.create');
    Route::post('/dashboard/fees', [DashboardFeeController::class, 'store'])->name('dashboard.fees.store');
    Route::get('/dashboard/fees/{fee}/edit', [DashboardFeeController::class, 'edit'])->name('dashboard.fees.edit');
    Route::put('/dashboard/fees/{fee}', [DashboardFeeController::class, 'update'])->name('dashboard.fees.update');
    Route::delete('/dashboard/fees/{fee}', [DashboardFeeController::class, 'destroy'])->name('dashboard.fees.destroy');
    Route::get('/dashboard/fees', [DashboardModulesController::class, 'fees'])->name('dashboard.fees');

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/dashboard/settings', [DashboardModulesController::class, 'settings'])->name('dashboard.settings');
        Route::post('/dashboard/settings', [DashboardModulesController::class, 'updateSettings'])->name('dashboard.settings.update');

        Route::get('/dashboard/cms/pages', [CmsWebController::class, 'pages'])->name('dashboard.cms.pages');
        Route::get('/dashboard/cms/edit/{page}', [CmsWebController::class, 'edit'])->name('dashboard.cms.edit');
        Route::put('/dashboard/cms/edit/{page}', [CmsWebController::class, 'update'])->name('dashboard.cms.update');

        Route::get('/dashboard/contact-submissions', [DashboardModulesController::class, 'contactSubmissions'])->name('dashboard.contact-submissions');
    });
});
