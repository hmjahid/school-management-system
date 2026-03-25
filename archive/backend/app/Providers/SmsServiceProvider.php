<?php

namespace App\Providers;

use App\Contracts\SmsService;
use App\Services\Sms\TwilioSmsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SmsService::class, function ($app) {
            $driver = Config::get('sms.default', 'log');
            $config = Config::get("sms.drivers.{$driver}", []);
            
            return $this->createSmsService($driver, $config);
        });
        
        // Register the SMS facade
        $this->app->alias(SmsService::class, 'sms');
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
            __DIR__.'/../../config/sms.php' => config_path('sms.php'),
        ], 'config');
    }

    /**
     * Create an instance of the SMS service.
     *
     * @param  string  $driver
     * @param  array  $config
     * @return \App\Contracts\SmsService
     * 
     * @throws \InvalidArgumentException
     */
    protected function createSmsService(string $driver, array $config = []): SmsService
    {
        switch ($driver) {
            case 'twilio':
                return new TwilioSmsService($config);
                
            case 'log':
                return new \App\Services\Sms\LogSmsService($config);
                
            // Add more drivers here as needed
            
            default:
                throw new \InvalidArgumentException("Unsupported SMS driver: {$driver}");
        }
    }
}
