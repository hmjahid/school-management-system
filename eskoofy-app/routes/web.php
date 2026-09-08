<?php

use App\Http\Controllers\Auth\StudentGuardianLoginController;
use App\Http\Controllers\Web\AdmissionWebController;
use App\Http\Controllers\Web\AuthSessionController;
use App\Http\Controllers\Web\FeePaymentReceiptController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Http\Controllers\Web\PaymentsWebController;
use App\Http\Controllers\Web\PortalController;
use App\Http\Controllers\Web\SiteGalleryController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\SiteNewsController;
use App\Http\Controllers\Web\SiteNoticeController;
use App\Http\Controllers\Web\SitePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/manifest.json', \App\Http\Controllers\Web\ManifestController::class)->name('site.manifest');
Route::get('/search', [\App\Http\Controllers\Web\SiteSearchController::class, 'index'])->name('site.search');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('site.sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\Web\RobotsController::class, 'index'])->name('site.robots');
Route::get('/results', [\App\Http\Controllers\Web\SiteResultController::class, 'lookup'])->name('site.results');
Route::get('/results/download', [\App\Http\Controllers\Web\SiteResultController::class, 'download'])->name('site.results.download');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/about', [SitePageController::class, 'about'])->name('site.about');
Route::get('/academics', [SitePageController::class, 'academics'])->name('site.academics');
Route::get('/admissions', [SitePageController::class, 'admissions'])->name('site.admissions');
Route::get('/students', [SitePageController::class, 'students'])->name('site.students');
Route::get('/faculty', [SitePageController::class, 'faculty'])->name('site.faculty');
Route::get('/transport', [SitePageController::class, 'transport'])->name('site.transport');
Route::get('/news', [SiteNewsController::class, 'index'])->name('site.news');
Route::get('/news/{slug}', [SiteNewsController::class, 'show'])->name('site.news.show');
Route::get('/notices', [SiteNoticeController::class, 'index'])->name('site.notices');
Route::get('/gallery', [SiteGalleryController::class, 'index'])->name('site.gallery');
Route::get('/events', [SitePageController::class, 'events'])->name('site.events');
Route::get('/committee', [SitePageController::class, 'committee'])->name('site.committee');
Route::get('/contact', [SitePageController::class, 'contact'])->name('site.contact');
Route::get('/terms', [SitePageController::class, 'terms'])->name('site.terms');
Route::get('/privacy', [SitePageController::class, 'privacy'])->name('site.privacy');

Route::get('/payments', [PaymentsWebController::class, 'index'])->name('site.payments');
Route::middleware('auth')->post('/payments/initiate', [PaymentsWebController::class, 'initiate'])->name('site.payments.initiate');
Route::middleware('auth')->get('/payments/status/{payment}', [PaymentsWebController::class, 'status'])->name('site.payments.status');
Route::middleware('auth')->get('/payments/receipts/{feePayment}', [FeePaymentReceiptController::class, 'show'])
    ->name('site.payments.receipts.show');

Route::get('/portal', [PortalController::class, 'index'])->name('portal');
Route::post('/portal/message', [PortalController::class, 'messageTeacher'])->name('portal.message');
Route::get('/portal/register', function () {
    return redirect()
        ->route('admissions.apply')
        ->with('status', __('Create an account by applying online, or log in if you already have portal access.'));
})->name('portal.register');

Route::get('/routine', [\App\Http\Controllers\Web\DashboardRoutineController::class, 'timetable'])->name('site.routine');

Route::get('/admissions/apply', [AdmissionWebController::class, 'apply'])->name('admissions.apply');
Route::get('/admissions/status', [AdmissionWebController::class, 'status'])->name('admissions.status');

Route::middleware('throttle:12,1')->group(function () {
    Route::post('/newsletter', [SitePageController::class, 'newsletterStore'])->name('site.newsletter.store');
    Route::post('/contact', [SitePageController::class, 'contactStore'])->name('site.contact.store');
    Route::post('/contact/feedback', [SitePageController::class, 'feedbackStore'])->name('site.feedback.store');
    Route::post('/contact/complaint', [SitePageController::class, 'complaintStore'])->name('site.complaint.store');
    Route::post('/admissions/scholarship', [SitePageController::class, 'scholarshipStore'])->name('admissions.scholarship.store');
    Route::post('/admissions/apply', [AdmissionWebController::class, 'applyStore'])->name('admissions.apply.store');
    Route::post('/admissions/{admission}/submit-payment', [AdmissionWebController::class, 'submitTransaction'])->name('admissions.submit-payment');
    Route::get('/admissions/{admission}/receipt', [AdmissionWebController::class, 'receipt'])->name('admissions.receipt');
    Route::get('/admissions/{admission}/approval-letter', [AdmissionWebController::class, 'approvalLetter'])->name('admissions.approval-letter');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

    Route::get('/student/login', [StudentGuardianLoginController::class, 'showLoginForm'])->name('student.login');
    Route::post('/student/login', [StudentGuardianLoginController::class, 'login'])->name('student.login.post');
    Route::get('/guardian/login', [StudentGuardianLoginController::class, 'showLoginForm'])->name('guardian.login');
    Route::post('/guardian/login', [StudentGuardianLoginController::class, 'login'])->name('guardian.login.post');
});
