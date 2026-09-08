<?php

namespace App\Providers;

use App\Contracts\PushNotificationService;
use App\Contracts\SmsService;
use App\Services\LogPushNotificationService;
use App\Services\LogSmsService;
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
        //
    }
}
