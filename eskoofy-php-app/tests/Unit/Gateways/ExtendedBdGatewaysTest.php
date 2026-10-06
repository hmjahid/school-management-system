<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Controllers\Dashboard\PaymentGatewayController;
use App\Core\Database;
use App\Gateways\GatewayFactory;
use App\Gateways\GenericHostedGateway;
use App\Models\PaymentGateway;
use Tests\Fakes\FakeDatabase;
use Tests\TestCase;

class ExtendedBdGatewaysTest extends TestCase
{
    private const CODES = [
        'shurjopay', 'portwallet', 'cellfin', 'purse',
        'cashby', 'upay', 'mycash', 'payer',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Database::setInstance(new FakeDatabase());
    }

    protected function tearDown(): void
    {
        Database::setInstance(null);
        parent::tearDown();
    }

    public function test_extended_bd_gateways_resolve_to_generic_hosted(): void
    {
        foreach (self::CODES as $code) {
            $this->assertSame(
                GenericHostedGateway::class,
                GatewayFactory::driverFor($code),
                $code
            );
            $this->assertInstanceOf(GenericHostedGateway::class, GatewayFactory::make($code), $code);
        }
    }

    public function test_extended_bd_gateways_are_listed(): void
    {
        $all = GatewayFactory::all();

        foreach (self::CODES as $code) {
            $this->assertArrayHasKey($code, $all, $code);
        }
    }

    public function test_shurjopay_checkout_request_uses_the_sandbox_url(): void
    {
        $result = GatewayFactory::make('shurjopay')->initialize([
            'amount' => 500,
            'invoice_id' => 'INV-SH-1',
            'payment_id' => 7,
            'currency' => 'BDT',
        ]);

        $this->assertTrue($result['success']);
        $url = $result['data']['payment_url'];
        $this->assertStringStartsWith('https://sandbox.shurjopay.io/?amount=500.00', $url);
        $this->assertStringContainsString('reference=INV-SH-1', $url);
        $this->assertStringContainsString('currency=BDT', $url);
        $this->assertStringContainsString('callback=', $url);
    }

    public function test_seeder_registers_the_extended_gateways_inactive(): void
    {
        $controller = new PaymentGatewayController();
        $method = new \ReflectionMethod(PaymentGatewayController::class, 'ensureGateways');
        $method->setAccessible(true);
        $method->invoke($controller);

        $db = Database::getInstance();
        $this->assertInstanceOf(FakeDatabase::class, $db);

        $rows = [];
        foreach ($db->tables['payment_gateways'] as $row) {
            $rows[$row['code']] = $row;
        }

        foreach (self::CODES as $code) {
            $this->assertArrayHasKey($code, $rows, $code);
            $this->assertSame(0, (int) $rows[$code]['is_active'], $code);
            $this->assertSame(1, (int) $rows[$code]['test_mode'], $code);
            $this->assertNotSame('', (string) $rows[$code]['sandbox_url'], $code);

            $extra = json_decode((string) $rows[$code]['extra_attributes'], true);
            $this->assertArrayHasKey('checkout_url_template', $extra, $code);
            $this->assertArrayHasKey('verify_url', $extra, $code);
            $this->assertArrayHasKey('checkout_method', $extra, $code);
            $this->assertArrayHasKey('signature_header', $extra, $code);
            $this->assertSame('COMPLETED', $extra['verify_success_value'], $code);
        }

        $this->assertSame(1, (int) $rows['test_gateway']['is_active']);
        $this->assertSame('local://payments/sandbox', $rows['test_gateway']['sandbox_url']);

        $hydrated = PaymentGateway::hydrate([$rows['test_gateway']])[0];
        $this->assertTrue($hydrated->is_configured);
    }
}
