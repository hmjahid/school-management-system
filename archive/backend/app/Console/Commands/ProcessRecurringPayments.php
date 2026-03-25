<?php

namespace App\Console\Commands;

use App\Services\RecurringPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRecurringPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:process-recurring 
                            {--retry-failed : Retry failed payment attempts}
                            {--max-retries=3 : Maximum number of retry attempts}
                            {--force : Process all due payments regardless of next billing date}
                            {--test : Run in test mode (no actual payments will be processed)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all due recurring payments';

    /**
     * The recurring payment service instance.
     *
     * @var \App\Services\RecurringPaymentService
     */
    protected $recurringPaymentService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\RecurringPaymentService  $recurringPaymentService
     * @return void
     */
    public function __construct(RecurringPaymentService $recurringPaymentService)
    {
        parent::__construct();
        $this->recurringPaymentService = $recurringPaymentService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting recurring payment processing...');
        
        if ($this->option('test')) {
            $this->warn('TEST MODE: No actual payments will be processed');
        }

        // Process regular due payments
        $this->processDuePayments();

        // Process failed payment retries if requested
        if ($this->option('retry-failed')) {
            $this->retryFailedPayments();
        }

        $this->info('Recurring payment processing completed.');
        return 0;
    }

    /**
     * Process due recurring payments.
     *
     * @return void
     */
    protected function processDuePayments()
    {
        $this->info('Processing due recurring payments...');
        
        try {
            $results = $this->recurringPaymentService->processDuePayments(
                $this->option('force')
            );

            $this->line(sprintf(
                'Processed %d payments: %d succeeded, %d failed, %d skipped',
                $results['processed'],
                $results['succeeded'],
                $results['failed'],
                $results['skipped'] ?? 0
            ));

            if (!empty($results['errors'])) {
                $this->error('Errors encountered:');
                foreach ($results['errors'] as $profileId => $error) {
                    $this->line("  - Profile {$profileId}: {$error}");
                }
            }

            // Log the results
            Log::info('Recurring payments processed', [
                'type' => 'due_payments',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            $this->error('Error processing recurring payments: ' . $e->getMessage());
            Log::error('Error processing recurring payments: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Retry failed payment attempts.
     *
     * @return void
     */
    protected function retryFailedPayments()
    {
        $maxRetries = (int) $this->option('max-retries');
        
        $this->info("Retrying failed payments (max {$maxRetries} attempts)...");
        
        try {
            $results = $this->recurringPaymentService->retryFailedPayments($maxRetries);

            $this->line(sprintf(
                'Retried %d failed payments: %d succeeded, %d failed',
                $results['processed'],
                $results['succeeded'],
                $results['failed']
            ));

            if (!empty($results['errors'])) {
                $this->error('Errors encountered during retry:');
                foreach ($results['errors'] as $profileId => $error) {
                    $this->line("  - Profile {$profileId}: {$error}");
                }
            }

            // Log the results
            Log::info('Failed payment retries processed', [
                'type' => 'failed_payment_retries',
                'max_retries' => $maxRetries,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            $this->error('Error retrying failed payments: ' . $e->getMessage());
            Log::error('Error retrying failed payments: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
