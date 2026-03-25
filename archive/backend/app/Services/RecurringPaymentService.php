<?php

namespace App\Services;

use App\Models\RecurringPaymentProfile;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RecurringPaymentService
{
    /**
     * The payment service instance.
     *
     * @var \App\Services\PaymentService
     */
    protected $paymentService;

    /**
     * Create a new service instance.
     *
     * @param  \App\Services\PaymentService  $paymentService
     * @return void
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Process all due recurring payments.
     *
     * @param  bool  $force  Process all due payments regardless of next billing date
     * @return array
     */
    public function processDuePayments($force = false): array
    {
        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Get all active profiles with due payments
        $query = RecurringPaymentProfile::active()
            ->where('next_billing_date', '<=', now())
            ->with(['user', 'paymentable']);

        if (!$force) {
            // Only process profiles where next billing date is today or in the past
            $query->whereDate('next_billing_date', '<=', now()->toDateString());
        }

        $profiles = $query->get();

        foreach ($profiles as $profile) {
            try {
                DB::beginTransaction();

                // Process the payment
                $result = $this->processPayment($profile);
                
                if ($result['success']) {
                    $results['succeeded']++;
                    Log::info("Processed recurring payment for profile {$profile->profile_id}: {$result['message']}");
                } else {
                    $results['failed']++;
                    $results['errors'][$profile->id] = $result['error'] ?? 'Unknown error';
                    Log::error("Failed to process recurring payment for profile {$profile->profile_id}: " . ($result['error'] ?? 'Unknown error'));
                }

                $results['processed']++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $results['failed']++;
                $results['errors'][$profile->id] = $e->getMessage();
                Log::error("Error processing recurring payment for profile {$profile->profile_id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Process a single recurring payment.
     *
     * @param  \App\Models\RecurringPaymentProfile  $profile
     * @return array
     */
    public function processPayment(RecurringPaymentProfile $profile): array
    {
        try {
            // Lock the profile for update to prevent race conditions
            $profile = RecurringPaymentProfile::lockForUpdate()->find($profile->id);
            
            if (!$profile || !$profile->isActive()) {
                return [
                    'success' => false,
                    'error' => 'Profile is not active or does not exist',
                ];
            }

            // Check if payment is already processed for this billing cycle
            $existingPayment = $profile->payments()
                ->whereDate('created_at', '>=', $profile->next_billing_date->startOfDay())
                ->whereIn('payment_status', ['completed', 'pending'])
                ->first();

            if ($existingPayment) {
                return [
                    'success' => true,
                    'message' => 'Payment already processed for this billing cycle',
                    'payment_id' => $existingPayment->id,
                    'existing' => true,
                ];
            }

            // Prepare payment data
            $paymentData = [
                'amount' => $profile->amount,
                'currency' => $profile->currency,
                'payment_method' => $profile->gateway,
                'description' => 'Recurring payment for ' . ($profile->paymentable->name ?? 'service'),
                'metadata' => array_merge($profile->metadata ?? [], [
                    'recurring_profile_id' => $profile->id,
                    'billing_period' => $profile->billing_period,
                    'billing_frequency' => $profile->billing_frequency,
                ]),
                'payment_method_details' => [
                    'token' => $profile->payment_method_token,
                    'last4' => $profile->card_last4,
                    'brand' => $profile->card_brand,
                ],
            ];

            // Process the payment
            $result = $this->paymentService->processRecurringPayment($profile->gateway, $paymentData);

            if ($result['success']) {
                // Record successful payment
                $payment = $profile->recordSuccessfulPayment([
                    'transaction_id' => $result['transaction_id'],
                    'gateway_response' => $result['gateway_response'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'payment_id' => $payment->id,
                ];
            } else {
                // Record failed payment attempt
                $profile->recordFailedPayment(
                    $result['error'] ?? 'Payment processing failed',
                    ['gateway_response' => $result['gateway_response'] ?? null]
                );

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Payment processing failed',
                    'gateway_response' => $result['gateway_response'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Record the error
            $profile->recordFailedPayment($e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Payment processing error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Retry failed payment attempts.
     *
     * @param  int  $maxAttempts  Maximum number of attempts to retry
     * @return array
     */
    public function retryFailedPayments(int $maxAttempts = 3): array
    {
        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Get profiles with failed attempts but not yet suspended
        $profiles = RecurringPaymentProfile::where('status', 'active')
            ->where('failure_count', '>', 0)
            ->where('failure_count', '<=', $maxAttempts)
            ->where('next_billing_date', '<=', now())
            ->with(['user', 'paymentable'])
            ->get();

        foreach ($profiles as $profile) {
            try {
                DB::beginTransaction();
                
                // Process the payment
                $result = $this->processPayment($profile);
                
                if ($result['success']) {
                    $results['succeeded']++;
                    Log::info("Retried and processed recurring payment for profile {$profile->profile_id}");
                } else {
                    $results['failed']++;
                    $results['errors'][$profile->id] = $result['error'] ?? 'Unknown error';
                    Log::warning("Failed to retry payment for profile {$profile->profile_id}: " . ($result['error'] ?? 'Unknown error'));
                }

                $results['processed']++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $results['failed']++;
                $results['errors'][$profile->id] = $e->getMessage();
                Log::error("Error retrying payment for profile {$profile->profile_id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get the next billing date based on the billing period and frequency.
     *
     * @param  string  $billingPeriod
     * @param  int  $frequency
     * @param  \Carbon\Carbon|null  $fromDate
     * @return \Carbon\Carbon
     */
    public static function calculateNextBillingDate(string $billingPeriod, int $frequency = 1, $fromDate = null): Carbon
    {
        $fromDate = $fromDate ?: now();
        $method = 'add' . ucfirst($billingPeriod) . 's';
        
        return $fromDate->copy()->$method($frequency);
    }

    /**
     * Get the end date for a subscription based on the number of billing cycles.
     *
     * @param  string  $billingPeriod
     * @param  int  $frequency
     * @param  int  $cycles
     * @param  \Carbon\Carbon|null  $startDate
     * @return \Carbon\Carbon
     */
    public static function calculateEndDate(string $billingPeriod, int $frequency, int $cycles, $startDate = null): Carbon
    {
        $startDate = $startDate ?: now();
        $method = 'add' . ucfirst($billingPeriod) . 's';
        
        return $startDate->copy()->$method($frequency * $cycles);
    }
}
