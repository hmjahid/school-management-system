<?php

namespace Tests\Feature;

use App\Models\PaymentGateway;
use App\Models\User;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardPaymentGatewayControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo(Permission::findOrCreate('manage_school_settings', 'web'));

        return $user;
    }

    #[Test]
    public function it_renders_the_create_and_edit_forms(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('dashboard.payment-gateways.create'))
            ->assertOk()
            ->assertSee('Add payment gateway');

        $gateway = PaymentGateway::create([
            'name' => 'Editable',
            'code' => 'editable',
            'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
            'is_online' => true,
            'api_key' => 'key-123',
            'extra_attributes' => ['verify_url' => 'https://api.example.com/verify'],
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.payment-gateways.edit', $gateway))
            ->assertOk()
            ->assertSee('Editable')
            ->assertSee('verify_url');
    }

    #[Test]
    public function the_payment_settings_tab_links_to_the_gateway_screen(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard.settings.general', ['tab' => 'payment']))
            ->assertOk()
            ->assertSee(route('dashboard.payment-gateways.index'));
    }

    #[Test]
    public function it_lists_gateways_for_a_manager(): void
    {
        $this->seed(PaymentGatewaySeeder::class);

        $this->actingAs($this->admin())
            ->get(route('dashboard.payment-gateways.index'))
            ->assertOk()
            ->assertSee('Google Pay')
            ->assertSee('Skrill');
    }

    #[Test]
    public function it_forbids_users_without_the_settings_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.payment-gateways.index'))
            ->assertForbidden();
    }

    #[Test]
    public function it_creates_a_gateway_with_extra_attributes_and_currencies(): void
    {
        $this->actingAs($this->admin())
            ->post(route('dashboard.payment-gateways.store'), [
                'name' => 'My Gateway',
                'code' => 'my_gateway',
                'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
                'is_active' => '1',
                'is_online' => '1',
                'has_api' => '1',
                'test_mode' => '1',
                'live_url' => 'https://checkout.example.com',
                'api_key' => 'pk_live_123',
                'currency' => 'USD',
                'supported_currencies' => 'USD, EUR, GBP',
                'extra_keys' => ['checkout_url_template', 'refund_url'],
                'extra_values' => ['https://checkout.example.com?amt={amount}', 'https://checkout.example.com/refund'],
            ])
            ->assertRedirect(route('dashboard.payment-gateways.index'));

        $gateway = PaymentGateway::where('code', 'my_gateway')->firstOrFail();

        $this->assertTrue($gateway->is_active);
        $this->assertSame('pk_live_123', $gateway->api_key);
        $this->assertSame(['USD', 'EUR', 'GBP'], $gateway->supported_currencies);
        $this->assertSame(
            'https://checkout.example.com/refund',
            $gateway->extra_attributes['refund_url']
        );
        $this->assertTrue($gateway->is_configured);
    }

    #[Test]
    public function it_updates_a_gateway_and_keeps_secrets_when_left_blank(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'Existing',
            'code' => 'existing',
            'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
            'is_online' => true,
            'api_key' => 'keep-me',
            'api_secret' => 'keep-secret',
            'live_url' => 'https://checkout.example.com',
        ]);

        $this->actingAs($this->admin())
            ->put(route('dashboard.payment-gateways.update', $gateway), [
                'name' => 'Renamed',
                'code' => 'existing',
                'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
                'is_online' => '1',
                'live_url' => 'https://checkout.example.com',
                'api_key' => '',
                'api_secret' => '',
                'currency' => 'USD',
            ])
            ->assertRedirect(route('dashboard.payment-gateways.index'));

        $gateway->refresh();
        $this->assertSame('Renamed', $gateway->name);
        $this->assertSame('keep-me', $gateway->api_key);
        $this->assertSame('keep-secret', $gateway->api_secret);
    }

    #[Test]
    public function it_deletes_a_gateway(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'Temporary',
            'code' => 'temporary',
            'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('dashboard.payment-gateways.destroy', $gateway))
            ->assertRedirect(route('dashboard.payment-gateways.index'));

        $this->assertSoftDeleted('payment_gateways', ['id' => $gateway->id]);
    }

    #[Test]
    public function an_enabled_gateway_appears_on_the_public_payments_page_and_a_disabled_one_does_not(): void
    {
        $this->seed(PaymentGatewaySeeder::class);

        $this->get(route('site.payments'))
            ->assertOk()
            ->assertDontSee('Google Pay');

        PaymentGateway::where('code', 'gpay')->update(['is_active' => true]);

        $this->get(route('site.payments'))
            ->assertOk()
            ->assertSee('Google Pay');
    }

    #[Test]
    public function the_seeder_creates_the_international_gateways_disabled(): void
    {
        $this->seed(PaymentGatewaySeeder::class);

        foreach (PaymentGateway::CONFIG_DRIVEN_GATEWAYS as $code) {
            $this->assertDatabaseHas('payment_gateways', [
                'code' => $code,
                'is_active' => false,
            ]);
        }

        // Only enabled rows are offered on the public payments page.
        $this->assertNotContains(
            'gpay',
            PaymentGateway::query()->where('is_active', true)->pluck('code')->all()
        );
    }
}
