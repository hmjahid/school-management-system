<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\ActivityLogger;

class ActivityLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ActivityLogger::macro('authCauser', function () {
            /** @var ActivityLogger $this */
            if (Auth::check()) {
                $this->causedBy(Auth::user());
            }

            return $this;
        });
    }
}
