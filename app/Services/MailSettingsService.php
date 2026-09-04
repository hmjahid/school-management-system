<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class MailSettingsService
{
    /**
     * Common SMTP provider presets (host, port, encryption).
     *
     * @return array<string, array{host: string, port: int, encryption: string|null}>
     */
    public function providerPresets(): array
    {
        return [
            'mailtrap' => ['host' => 'sandbox.smtp.mailtrap.io', 'port' => 2525, 'encryption' => 'tls'],
            'gmail' => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'],
            'mailgun' => ['host' => 'smtp.mailgun.org', 'port' => 587, 'encryption' => 'tls'],
            'ses' => ['host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls'],
            'postmark' => ['host' => 'smtp.postmarkapp.com', 'port' => 587, 'encryption' => 'tls'],
            'sendgrid' => ['host' => 'smtp.sendgrid.net', 'port' => 587, 'encryption' => 'tls'],
        ];
    }

    /**
     * Resolve the runtime mail settings as a single array, falling back to the
     * environment / config defaults so the app always has a usable mailer.
     *
     * @return array<string, mixed>
     */
    public function settings(?WebsiteSetting $settings = null): array
    {
        $settings = $settings ?: (Schema::hasTable('website_settings') ? WebsiteSetting::getSettings() : new WebsiteSetting);

        return [
            'enabled' => (bool) ($settings->mail_enabled ?? false),
            'driver' => $settings->mail_driver ?: 'smtp',
            'host' => $settings->mail_host ?: '127.0.0.1',
            'port' => (int) ($settings->mail_port ?: 2525),
            'username' => $settings->mail_username,
            'password' => $settings->mail_password,
            'encryption' => $settings->mail_encryption ?: null,
            'from_address' => $settings->mail_from_address ?: config('mail.from.address', 'hello@example.com'),
            'from_name' => $settings->mail_from_name ?: config('mail.from.name', 'Example'),
            'test_recipient' => $settings->mail_test_recipient,
        ];
    }

    /**
     * Apply the runtime SMTP settings onto Laravel's mail config so all
     * subsequent mail sends use the configured server.
     */
    public function apply(?WebsiteSetting $settings = null): void
    {
        $config = $this->settings($settings);

        if (! $config['enabled']) {
            // Not enabled: fall back to the environment / log driver.
            Config::set('mail.default', env('MAIL_MAILER', 'log'));

            return;
        }

        $driver = $config['driver'] ?: 'smtp';
        $supported = array_keys(config('mail.mailers', ['smtp' => true, 'log' => true]));
        if (! in_array($driver, $supported, true)) {
            $driver = 'smtp';
        }

        Config::set('mail.default', $driver);
        Config::set('mail.mailers.smtp', array_merge(config('mail.mailers.smtp', []), [
            'transport' => 'smtp',
            'host' => $config['host'],
            'port' => $config['port'],
            'username' => $config['username'],
            'password' => $config['password'],
            'encryption' => $config['encryption'],
        ]));

        Config::set('mail.from', [
            'address' => $config['from_address'],
            'name' => $config['from_name'],
        ]);
    }

    /**
     * Send a test email to the given (or saved) recipient through the currently
     * configured mailer. Throws on failure so the caller can surface the reason.
     */
    public function sendTest(string $to): void
    {
        $content = '<p>This is a test email from '.e(config('app.name', 'SchoolEase')).'. Your SMTP settings are working correctly.</p>';

        $mailable = (new \Illuminate\Mail\Mailable)
            ->subject('SMTP Test — '.config('app.name', 'SchoolEase'))
            ->html($content);

        Mail::to($to)->send($mailable);
    }
}
