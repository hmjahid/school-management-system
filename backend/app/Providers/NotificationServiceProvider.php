<?php

namespace App\Providers;

use App\Services\NotificationDeliveryService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Events\NotificationFailed;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(NotificationDeliveryService::class, function ($app) {
            return new NotificationDeliveryService(
                $app->make('sms'),
                $app->make('push')
            );
        });

        // Register the notification facade
        $this->app->alias(NotificationDeliveryService::class, 'notification');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register notification events
        $this->registerNotificationEvents();
        
        // Publish configuration files
        $this->publishConfigs();
    }

    /**
     * Register notification events.
     *
     * @return void
     */
    protected function registerNotificationEvents(): void
    {
        // Log notification sending event
        Event::listen(NotificationSending::class, function (NotificationSending $event) {
            \Illuminate\Support\Facades\Log::info('Notification sending', [
                'notifiable' => $event->notifiable->id ?? null,
                'notification' => get_class($event->notification),
                'channel' => $event->channel,
            ]);
        });

        // Log successful notification delivery
        Event::listen(NotificationSent::class, function (NotificationSent $event) {
            \Illuminate\Support\Facades\Log::info('Notification sent', [
                'notifiable' => $event->notifiable->id ?? null,
                'notification' => get_class($event->notification),
                'channel' => $event->channel,
                'response' => $event->response ?? null,
            ]);
        });

        // Log failed notification delivery
        Event::listen(NotificationFailed::class, function (NotificationFailed $event) {
            \Illuminate\Support\Facades\Log::error('Notification failed', [
                'notifiable' => $event->notifiable->id ?? null,
                'notification' => get_class($event->notification),
                'channel' => $event->channel,
                'error' => $event->data['error'] ?? $event->data['exception'] ?? 'Unknown error',
            ]);
        });
    }

    /**
     * Publish configuration files.
     *
     * @return void
     */
    protected function publishConfigs(): void
    {
        $this->publishes([
            __DIR__.'/../../config/notifications.php' => config_path('notifications.php'),
        ], 'config');
    }
}
