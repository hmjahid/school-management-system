<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\ManualGateway;
use Tests\FakeDatabase;
use Tests\TestCase;

class ManualGatewayTest extends TestCase
{
    public function test_process_marks_payment_paid(): void
    {
        $db = new FakeDatabase();
        $db->tables['payments'] = [['id' => 10, 'status' => 'pending']];

        $gateway = new ManualGateway($db);
        $result = $gateway->process(['plan' => ['id' => 1]], ['id' => 10]);

        $this->assertTrue($result['success']);
        $this->assertSame('paid', $result['status']);
        $this->assertStringStartsWith('MAN-', $result['transaction_id']);
        $this->assertSame('paid', $db->rows('payments')[0]['status']);
        $this->assertSame($result['transaction_id'], $db->rows('payments')[0]['transaction_id']);
    }

    public function test_verify_returns_paid_when_already_paid(): void
    {
        $db = new FakeDatabase();
        $db->tables['payments'] = [['id' => 11, 'status' => 'paid', 'transaction_id' => 'MAN-ABC']];

        $gateway = new ManualGateway($db);
        $result = $gateway->verify(['id' => 11, 'status' => 'paid', 'transaction_id' => 'MAN-ABC']);

        $this->assertTrue($result['success']);
        $this->assertSame('paid', $result['status']);
        $this->assertSame('MAN-ABC', $result['transaction_id']);
    }
}