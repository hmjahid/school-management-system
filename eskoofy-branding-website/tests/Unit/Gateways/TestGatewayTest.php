<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\GatewayFactory;
use App\Gateways\GenericHostedGateway;
use App\Gateways\TestGateway;
use Tests\FakeDatabase;
use Tests\TestCase;

class TestGatewayTest extends TestCase
{
    public function test_make_resolves_test_gateway(): void
    {
        $gateway = GatewayFactory::make('test_gateway', ['default' => 'manual', 'gateways' => []], new FakeDatabase());

        $this->assertInstanceOf(TestGateway::class, $gateway);
        $this->assertSame('test_gateway', $gateway->id());
        $this->assertSame('Test / Sandbox', $gateway->name());
        $this->assertTrue($gateway->isConfigured());
    }

    public function test_process_redirects_to_local_sandbox(): void
    {
        $db = new FakeDatabase();
        $db->tables['payments'] = [['id' => 10, 'status' => 'pending', 'reference' => 'ORD-TEST', 'amount' => 100, 'currency' => 'USD']];

        $gateway = new TestGateway($db);
        $result = $gateway->process(['amount' => 100, 'currency' => 'USD'], ['id' => 10, 'reference' => 'ORD-TEST']);

        $this->assertTrue($result['success']);
        $this->assertSame('/checkout/sandbox/ORD-TEST', $result['redirect_url']);
        $this->assertSame('TEST-ORD-TEST', $result['transaction_id']);
        $this->assertSame('TEST-ORD-TEST', $db->rows('payments')[0]['transaction_id']);
    }

    public function test_verify_success_marks_paid(): void
    {
        $db = new FakeDatabase();
        $db->tables['payments'] = [['id' => 11, 'status' => 'pending', 'reference' => 'ORD-S', 'amount' => 50, 'currency' => 'USD']];

        $gateway = new TestGateway($db);
        $result = $gateway->verify(['id' => 11, 'status' => 'pending', 'reference' => 'ORD-S'], ['simulate' => 'success']);

        $this->assertTrue($result['success']);
        $this->assertSame('paid', $result['status']);
        $this->assertSame('TEST-ORD-S', $result['transaction_id']);
        $this->assertSame('paid', $db->rows('payments')[0]['status']);
    }

    public function test_verify_failure_and_cancel_set_terminal_statuses(): void
    {
        $db = new FakeDatabase();
        $db->tables['payments'] = [
            ['id' => 12, 'status' => 'pending', 'reference' => 'ORD-F'],
            ['id' => 13, 'status' => 'pending', 'reference' => 'ORD-C'],
        ];

        $gateway = new TestGateway($db);
        $failed = $gateway->verify(['id' => 12, 'status' => 'pending', 'reference' => 'ORD-F'], ['simulate' => 'failure']);
        $cancelled = $gateway->verify(['id' => 13, 'status' => 'pending', 'reference' => 'ORD-C'], ['simulate' => 'cancel']);

        $this->assertFalse($failed['success']);
        $this->assertSame('failed', $failed['status']);
        $this->assertSame('failed', $db->rows('payments')[0]['status']);
        $this->assertFalse($cancelled['success']);
        $this->assertSame('cancelled', $cancelled['status']);
        $this->assertSame('cancelled', $db->rows('payments')[1]['status']);
    }

    public function test_verify_is_idempotent_for_paid_payment(): void
    {
        $db = new FakeDatabase();
        $db->tables['payments'] = [['id' => 14, 'status' => 'paid', 'reference' => 'ORD-P', 'transaction_id' => 'TEST-ORD-P']];

        $gateway = new TestGateway($db);
        $result = $gateway->verify(['id' => 14, 'status' => 'paid', 'reference' => 'ORD-P', 'transaction_id' => 'TEST-ORD-P'], ['simulate' => 'failure']);

        $this->assertTrue($result['success']);
        $this->assertSame('paid', $result['status']);
        $this->assertSame('TEST-ORD-P', $result['transaction_id']);
    }

    public function test_verify_without_simulation_stays_pending(): void
    {
        $db = new FakeDatabase();
        $db->tables['payments'] = [['id' => 15, 'status' => 'pending', 'reference' => 'ORD-N']];

        $gateway = new TestGateway($db);
        $result = $gateway->verify(['id' => 15, 'status' => 'pending', 'reference' => 'ORD-N']);

        $this->assertFalse($result['success']);
        $this->assertSame('pending', $result['status']);
        $this->assertSame('pending', $db->rows('payments')[0]['status']);
    }

    public function test_extended_bd_gateways_resolve_generic_hosted(): void
    {
        $db = new FakeDatabase();
        $codes = ['shurjopay', 'portwallet', 'cellfin', 'purse', 'cashby', 'upay', 'mycash', 'payer'];

        foreach ($codes as $code) {
            $gateway = GatewayFactory::make($code, null, $db);
            $this->assertInstanceOf(GenericHostedGateway::class, $gateway);
            $this->assertSame($code, $gateway->id());
            $this->assertFalse($gateway->isConfigured(), $code . ' should be unconfigured without credentials');
            $this->assertContains($code, GatewayFactory::bdGateways());
        }
    }

    public function test_test_gateway_is_not_offered_at_checkout_by_default(): void
    {
        $this->assertNotContains('test_gateway', GatewayFactory::gatewaysForCountry('US'));
    }

    public function test_unknown_gateway_still_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        GatewayFactory::make('bitcoin', ['default' => 'manual', 'gateways' => []], new FakeDatabase());
    }
}
