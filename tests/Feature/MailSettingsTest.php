<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\MailSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
    }

    private function admin(): User
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => 'manage_school_settings', 'guard_name' => 'web'],
        );

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo('manage_school_settings');

        return $user;
    }

    private function seedSettings(array $overrides = []): WebsiteSetting
    {
        return WebsiteSetting::firstOrCreate([], array_merge([
            'school_name' => 'Example School',
            'established_year' => 2000,
            'address' => '1 Main St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'BD',
            'postal_code' => '1000',
            'phone' => '+8800000000',
            'email' => 'school@example.com',
        ], $overrides));
    }

    public function test_mail_tab_renders_form_and_presets(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user)
            ->get(route('dashboard.settings.general', ['tab' => 'mail']));

        $response->assertStatus(200);
        $response->assertSee('name="mail_enabled"', false);
        $response->assertSee('name="mail_host"', false);
        $response->assertSee('name="mail_port"', false);
        $response->assertSee('name="mail_driver"', false);
        $response->assertSee('smtp.mailtrap.io', false);
        $response->assertSee('smtp.gmail.com', false);
        $response->assertSee('smtp.sendgrid.net', false);
        $response->assertSee(route('dashboard.settings.test.mail'));
    }

    public function test_guests_cannot_access_mail_settings(): void
    {
        $this->get(route('dashboard.settings.general', ['tab' => 'mail']))->assertRedirect('/login');
    }

    public function test_update_mail_persists_settings_and_applies_config(): void
    {
        $user = $this->admin();
        $this->seedSettings();

        $this->actingAs($user)
            ->post(route('dashboard.settings.update.mail'), [
                'mail_enabled' => '1',
                'mail_driver' => 'smtp',
                'mail_host' => 'smtp.example.com',
                'mail_port' => '587',
                'mail_username' => 'user@example.com',
                'mail_password' => 'secret',
                'mail_encryption' => 'tls',
                'mail_from_address' => 'noreply@example.com',
                'mail_from_name' => 'Test School',
                'mail_test_recipient' => 'admin@example.com',
            ])
            ->assertRedirect(route('dashboard.settings.general', ['tab' => 'mail']));

        $settings = WebsiteSetting::getSettings();

        $this->assertTrue($settings->mail_enabled);
        $this->assertSame('smtp.example.com', $settings->mail_host);
        $this->assertSame('587', $settings->mail_port);
        $this->assertSame('user@example.com', $settings->mail_username);
        $this->assertSame('secret', $settings->mail_password);
        $this->assertSame('tls', $settings->mail_encryption);
        $this->assertSame('noreply@example.com', $settings->mail_from_address);
        $this->assertSame('Test School', $settings->mail_from_name);

        // The saved settings should have been applied to runtime config.
        $this->assertSame('smtp', Config::get('mail.default'));
        $this->assertSame('smtp.example.com', Config::get('mail.mailers.smtp.host'));
        $this->assertSame('secret', Config::get('mail.mailers.smtp.password'));
        $this->assertSame('noreply@example.com', Config::get('mail.from.address'));
    }

    public function test_update_mail_without_enable_falls_back_to_log_driver(): void
    {
        $user = $this->admin();

        // Set an enabled config first.
        $this->seedSettings([
            'mail_enabled' => true,
            'mail_host' => 'smtp.example.com',
            'mail_port' => '587',
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.settings.update.mail'), [
                // no mail_enabled => disabled
                'mail_driver' => 'smtp',
                'mail_host' => 'smtp.example.com',
            ])
            ->assertRedirect(route('dashboard.settings.general', ['tab' => 'mail']));

        $this->assertFalse(WebsiteSetting::getSettings()->mail_enabled);
        $this->assertSame(env('MAIL_MAILER', 'log'), Config::get('mail.default'));
    }

    public function test_mail_defaults_to_log_when_not_enabled(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('dashboard.settings.general', ['tab' => 'mail']))
            ->assertStatus(200);

        $this->assertSame(env('MAIL_MAILER', 'log'), Config::get('mail.default'));
    }

    public function test_test_mail_sends_email_to_recipient(): void
    {
        $user = $this->admin();

        Mail::fake();

        $this->actingAs($user)
            ->post(route('dashboard.settings.test.mail'), ['to' => 'admin@example.com'])
            ->assertRedirect(route('dashboard.settings.general', ['tab' => 'mail']));

        Mail::assertSent(\Illuminate\Mail\Mailable::class);
    }

    public function test_test_mail_requires_valid_email(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('dashboard.settings.test.mail'), ['to' => 'not-an-email'])
            ->assertSessionHasErrors('to');
    }

    public function test_service_applies_runtime_config_from_db(): void
    {
        $this->seedSettings([
            'mail_enabled' => true,
            'mail_driver' => 'smtp',
            'mail_host' => 'smtp.runtime.test',
            'mail_port' => '2525',
            'mail_username' => 'runtime-user',
            'mail_password' => 'runtime-pass',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'from@runtime.test',
            'mail_from_name' => 'Runtime',
        ]);

        Config::set('mail.default', 'log');

        app(MailSettingsService::class)->apply();

        $this->assertSame('smtp', Config::get('mail.default'));
        $this->assertSame('smtp.runtime.test', Config::get('mail.mailers.smtp.host'));
        $this->assertSame('2525', (string) Config::get('mail.mailers.smtp.port'));
        $this->assertSame('runtime-user', Config::get('mail.mailers.smtp.username'));
        $this->assertSame('from@runtime.test', Config::get('mail.from.address'));
    }

    public function test_provider_presets_include_common_services(): void
    {
        $presets = app(MailSettingsService::class)->providerPresets();

        foreach (['mailtrap', 'gmail', 'mailgun', 'ses', 'postmark', 'sendgrid'] as $provider) {
            $this->assertArrayHasKey($provider, $presets);
            $this->assertArrayHasKey('host', $presets[$provider]);
            $this->assertArrayHasKey('port', $presets[$provider]);
        }
    }
}
