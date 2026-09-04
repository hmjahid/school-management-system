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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        if (config('app.env') === 'production') {
            URL::forceHttps();
        }

        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        Relation::morphMap([
            'fee_payment' => FeePayment::class,
            'expense' => Expense::class,
        ]);

        if (Schema::hasTable('website_settings')) {
            app(\App\Services\MailSettingsService::class)->apply();
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

        View::composer(['partials.dashboard.sidebar', 'partials.dashboard.topbar'], function ($view) {
            $user = auth()->user();
            $view->with('dashboardHelpSection', dashboard_help_section_for_route(request()->route()?->getName()));
            $pending = [
                'admissions' => 0,
                'leaves' => 0,
                'unreadMessages' => 0,
                'unreadNotifications' => 0,
                'pendingFeeApprovals' => 0,
            ];

            if ($user) {
                try {
                    if (Schema::hasTable('admissions')) {
                        $pending['admissions'] = \App\Models\Admission::where('status', \App\Models\Admission::STATUS_SUBMITTED)->count();
                    }
                    if (Schema::hasTable('leave_requests')) {
                        $pending['leaves'] = \App\Models\LeaveRequest::where('status', \App\Models\LeaveRequest::STATUS_PENDING)->count();
                    }
                    if (Schema::hasTable('messages')) {
                        $pending['unreadMessages'] = \App\Models\Message::where('receiver_id', $user->id)->unread()->count();
                    }
                    $pending['unreadNotifications'] = $user->unreadNotifications()->count();
                    if (Schema::hasTable('fee_payments')) {
                        $pending['pendingFeeApprovals'] = \App\Models\FeePayment::where('status', \App\Models\FeePayment::STATUS_PENDING)->count();
                    }
                } catch (\Throwable $e) {
                    $pending = array_fill_keys(array_keys($pending), 0);
                }
            }

            $view->with('sidebarPendingCounts', $pending);

            try {
                if ($user && Schema::hasTable('dashboard_favorites')) {
                    $view->with(
                        'dashboardFavorites',
                        \App\Models\DashboardFavorite::where('user_id', $user->id)
                            ->orderByDesc('updated_at')
                            ->limit(12)
                            ->get()
                    );
                } else {
                    $view->with('dashboardFavorites', collect());
                }
            } catch (\Throwable) {
                $view->with('dashboardFavorites', collect());
            }
        });
    }
}
