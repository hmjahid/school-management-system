<?php

namespace App\Providers;

use App\Contracts\PushNotificationService;
use App\Contracts\SmsService;
use App\Models\Attendance;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\WebsiteSetting;
use App\Observers\AttendanceObserver;
use App\Observers\FinanceObserver;
use App\Services\LogPushNotificationService;
use App\Services\LogSmsService;
use App\Support\SiteFrontend;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsService::class, LogSmsService::class);
        $this->app->bind(PushNotificationService::class, LogPushNotificationService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        if (session()->has('dashboard_locale')) {
            app()->setLocale(session('dashboard_locale'));
        }

        if (Schema::hasTable('attendances')) {
            Attendance::observe(AttendanceObserver::class);
        }
        if (Schema::hasTable('expenses') && Schema::hasTable('ledger_entries')) {
            $financeObserver = app(FinanceObserver::class);
            Expense::created(fn ($e) => $financeObserver->createdExpense($e));
            if (Schema::hasTable('fee_payments')) {
                FeePayment::created(fn ($p) => $financeObserver->created($p));
            }
        }

        View::composer('*', function ($view) {
            $settings = null;

            if (Schema::hasTable('website_settings')) {
                $settings = Cache::remember('website_settings.first', 3600, function () {
                    return WebsiteSetting::getSettings();
                });
                if ($settings === null || ! $settings->exists) {
                    $settings = WebsiteSetting::getSettings();
                }
            }

            $view->with('siteSettings', $settings);
            $view->with('siteUi', SiteFrontend::merged());
        });
    }
}
