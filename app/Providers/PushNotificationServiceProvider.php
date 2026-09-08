<?php

namespace App\Providers;

use App\Contracts\PushNotificationService;
use App\Services\Push\FirebasePushService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class PushNotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/fcm.php', 'fcm'
        );

        $this->app->singleton(PushNotificationService::class, function ($app) {
            $driver = Config::get('fcm.driver', 'firebase');
            $config = Config::get('fcm', []);

            return $this->createPushService($driver, $config);
        });

        // Register the Push facade
        $this->app->alias(PushNotificationService::class, 'push');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__.'/../../config/fcm.php' => config_path('fcm.php'),
        ], 'config');
    }

    /**
     * Create an instance of the push notification service.
     *
     *
     * @throws \InvalidArgumentException
     */
    protected function createPushService(string $driver, array $config = []): PushNotificationService
    {
        switch (strtolower($driver)) {
            case 'firebase':
                if (! class_exists(\Kreait\Firebase\Factory::class)) {
                    throw new \RuntimeException(
                        'The kreait/firebase package is not installed. Run `composer require kreait/firebase` to use the firebase push driver.'
                    );
                }

                return new FirebasePushService($config);

            case 'log':
                return new \App\Services\Push\LogPushService($config);

                // Add more drivers here as needed

            default:
                throw new \InvalidArgumentException("Unsupported push notification driver: {$driver}");
        }
    }
}
