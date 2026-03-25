<?php

namespace App\Providers;

use App\Contracts\PushNotificationService;
use App\Contracts\SmsService;
use App\Models\WebsiteSetting;
use App\Services\LogPushNotificationService;
use App\Services\LogSmsService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsService::class, LogSmsService::class);
        $this->app->bind(PushNotificationService::class, LogPushNotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        View::composer('*', function ($view) {
            $settings = null;
            if (Schema::hasTable('website_settings')) {
                $settings = WebsiteSetting::first();
            }
            $view->with('siteSettings', $settings);
            $view->with('siteUi', \App\Support\SiteFrontend::merged());
        });
    }
}
