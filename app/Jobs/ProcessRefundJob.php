<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Refund $refund, public ?string $transactionId = null) {}

    /**
     * Execute the job.
     */
    public function handle(RefundService $refundService): void
    {
        if (! $this->refund->isPending()) {
            Log::info('Refund no longer pending, skipping processing', ['refund_id' => $this->refund->id]);

            return;
        }

        try {
            $result = $refundService->processPendingRefund($this->refund, $this->transactionId);

            if (! $result['success']) {
                Log::warning('Refund processing failed', [
                    'refund_id' => $this->refund->id,
                    'error' => $result['message'],
                ]);

                return;
            }

            Log::info('Refund processed', [
                'refund_id' => $this->refund->id,
                'transaction_id' => $this->refund->transaction_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Refund processing failed', [
                'refund_id' => $this->refund->id,
                'error' => $e->getMessage(),
            ]);

            $this->refund->markAsFailed($e->getMessage());

            throw $e;
        }
    }
}
