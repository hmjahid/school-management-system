<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Services\ActivityLog;

/**
 * Working provider that records payments as paid immediately.
 * Paddle (online) will be added later as a drop-in gateway.
 */
class ManualGateway implements PaymentGatewayInterface
{
    private DatabaseInterface $db;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function id(): string
    {
        return 'manual';
    }

    public function name(): string
    {
        return 'Manual / Bank Transfer';
    }

    public function process(array $order, array $payment): array
    {
        $transaction = 'MAN-' . strtoupper(bin2hex(random_bytes(6)));

        $this->db->update(
            'payments',
            [
                'transaction_id' => $transaction,
                'status'         => 'paid',
                'paid_at'        => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ],
            'id = ?',
            [(int) $payment['id']]
        );

        ActivityLog::log('payment.processed', 'system', null, [
            'payment_id'    => (int) $payment['id'],
            'gateway'       => 'manual',
            'transaction_id' => $transaction,
        ]);

        return [
            'success'        => true,
            'transaction_id' => $transaction,
            'status'         => 'paid',
            'message'        => 'Payment recorded.',
            'raw'            => null,
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        if (($payment['status'] ?? '') === 'paid') {
            return [
                'success'        => true,
                'transaction_id' => $payment['transaction_id'] ?? null,
                'status'         => 'paid',
                'message'        => 'Payment already verified.',
                'raw'            => null,
            ];
        }

        return $this->process([], $payment);
    }
}