<?php

namespace App\Jobs;

use App\Models\Refund;
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
    public function handle(): void
    {
        if (! $this->refund->isPending()) {
            Log::info('Refund no longer pending, skipping processing', ['refund_id' => $this->refund->id]);

            return;
        }

        $this->refund->update(['status' => 'processing']);

        try {
            // In a real application this would call the payment gateway.
            $transactionId = $this->transactionId ?? ('R-'.strtoupper(uniqid()));

            $this->refund->update([
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'processed_at' => now(),
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
