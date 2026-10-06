<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Zero-credential test/sandbox gateway.
 *
 * Never contacts a provider: it redirects to a local sandbox page where an
 * operator simulates success, failure or cancellation so the full payment
 * pipeline (init → callback → verify → license issue) can be exercised for
 * free. Enable it with TEST_GATEWAY_ENABLED=true.
 */
class TestGateway extends AbstractGateway
{
    public function id(): string
    {
        return 'test_gateway';
    }

    public function name(): string
    {
        return 'Test / Sandbox';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function process(array $order, array $payment): array
    {
        $reference = (string) ($payment['reference'] ?? '');
        if ($reference === '') {
            return ['success' => false, 'transaction_id' => null, 'status' => 'failed', 'message' => 'Missing payment reference.', 'raw' => null];
        }

        $transaction = 'TEST-' . $reference;
        $this->db->update('payments', [
            'transaction_id' => $transaction,
            'status'         => 'pending',
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $payment['id']]);

        return [
            'success'        => true,
            'transaction_id' => $transaction,
            'status'         => 'pending',
            'message'        => 'Redirecting to the test sandbox.',
            'redirect_url'   => '/checkout/sandbox/' . rawurlencode($reference),
            'raw'            => null,
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        if (($payment['status'] ?? '') === 'paid') {
            return ['success' => true, 'transaction_id' => $payment['transaction_id'] ?? null, 'status' => 'paid', 'message' => 'Payment already verified.', 'raw' => null];
        }

        $reference = (string) ($payment['reference'] ?? '');
        $simulate  = strtolower((string) ($data['simulate'] ?? ''));

        switch ($simulate) {
            case 'success':
                $transaction = 'TEST-' . $reference;
                $this->markPaid($payment, $transaction);
                $this->logProcessed($payment, $transaction);

                return ['success' => true, 'transaction_id' => $transaction, 'status' => 'paid', 'message' => 'Test payment simulated as successful.', 'raw' => null];

            case 'failure':
                $this->db->update('payments', ['status' => 'failed', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $payment['id']]);

                return ['success' => false, 'transaction_id' => null, 'status' => 'failed', 'message' => 'Test payment simulated as failed.', 'raw' => null];

            case 'cancel':
                $this->db->update('payments', ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $payment['id']]);

                return ['success' => false, 'transaction_id' => null, 'status' => 'cancelled', 'message' => 'Test payment cancelled.', 'raw' => null];
        }

        return ['success' => false, 'transaction_id' => null, 'status' => 'pending', 'message' => 'Awaiting a sandbox simulation.', 'raw' => null];
    }
}
