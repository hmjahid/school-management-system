<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use App\Models\RecurringPaymentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringPaymentProfileTest extends TestCase
{
    use RefreshDatabase;

    private function makeProfile(array $overrides = []): RecurringPaymentProfile
    {
        $user = User::factory()->create();

        $attributes = array_merge([
            'user_id' => $user->id,
            'gateway' => 'bkash',
            'amount' => 500.00,
            'currency' => 'BDT',
            'billing_period' => 'month',
            'billing_frequency' => 1,
            'start_date' => now()->subDays(2),
            'next_billing_date' => now()->subDay(),
            'status' => 'active',
            'max_failures' => 3,
            'failure_count' => 0,
        ], $overrides);

        $profile = new RecurringPaymentProfile($attributes);
        $profile->forceFill([
            'paymentable_type' => $overrides['paymentable_type'] ?? User::class,
            'paymentable_id' => $overrides['paymentable_id'] ?? $user->id,
        ]);
        $profile->save();

        return $profile;
    }

    /** @test */
    public function generate_profile_id_has_rpp_prefix(): void
    {
        $this->assertStringStartsWith('RPP', RecurringPaymentProfile::generateProfileId());
    }

    /** @test */
    public function profile_id_is_auto_generated_on_create(): void
    {
        $profile = $this->makeProfile();

        $this->assertStringStartsWith('RPP', $profile->profile_id);
    }

    /** @test */
    public function is_active_considers_status_date_and_end_date(): void
    {
        $this->assertTrue($this->makeProfile([
            'status' => 'active',
            'next_billing_date' => now()->subDay(),
            'end_date' => null,
        ])->isActive());

        // future next billing date => not due
        $this->assertFalse($this->makeProfile([
            'status' => 'active',
            'next_billing_date' => now()->addDay(),
        ])->isActive());

        // ended profile => not active
        $this->assertFalse($this->makeProfile([
            'status' => 'active',
            'next_billing_date' => now()->subDay(),
            'end_date' => now()->subDay(),
        ])->isActive());

        // suspended status => not active
        $this->assertFalse($this->makeProfile(['status' => 'suspended'])->isActive());
    }

    /** @test */
    public function calculate_next_billing_date_advances_period(): void
    {
        $profile = $this->makeProfile([
            'billing_period' => 'month',
            'billing_frequency' => 1,
            'next_billing_date' => now()->startOfMonth(),
        ]);

        $this->assertEquals(now()->startOfMonth()->addMonth(), $profile->calculateNextBillingDate());

        $weekly = $this->makeProfile([
            'billing_period' => 'week',
            'billing_frequency' => 2,
            'next_billing_date' => now()->startOfWeek(),
        ]);
        $this->assertEquals(now()->startOfWeek()->addWeeks(2), $weekly->calculateNextBillingDate());
    }

    /** @test */
    public function record_successful_payment_creates_payment_and_resets_failures(): void
    {
        $profile = $this->makeProfile(['failure_count' => 2, 'next_billing_date' => now()->subDay()]);

        $payment = $profile->recordSuccessfulPayment(['transaction_id' => 'TXN-9']);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->payment_status);
        $this->assertEquals('TXN-9', $payment->transaction_id);

        $profile->refresh();
        $this->assertEquals(0, $profile->failure_count);
        $this->assertEquals(now()->subDay()->addMonth()->toDateString(), $profile->next_billing_date->toDateString());
    }

    /** @test */
    public function record_failed_payment_increments_and_suspends_at_limit(): void
    {
        $profile = $this->makeProfile(['max_failures' => 3, 'failure_count' => 0]);

        $profile->recordFailedPayment('Card declined');
        $this->assertEquals(1, $profile->refresh()->failure_count);
        $this->assertEquals('active', $profile->status);

        $profile->recordFailedPayment('Expired');
        $profile->recordFailedPayment('Expired');
        $profile->refresh();

        $this->assertEquals(3, $profile->failure_count);
        $this->assertEquals('suspended', $profile->status);
        $this->assertNotNull($profile->metadata['suspension_reason'] ?? null);
    }

    /** @test */
    public function billing_period_name_reflects_frequency(): void
    {
        $this->assertEquals('Monthly', $this->makeProfile(['billing_period' => 'month', 'billing_frequency' => 1])->billing_period_name);
        $this->assertEquals('Every 3 months', $this->makeProfile(['billing_period' => 'month', 'billing_frequency' => 3])->billing_period_name);
    }

    /** @test */
    public function formatted_amount_includes_currency(): void
    {
        $this->assertEquals('500.00 BDT', $this->makeProfile(['amount' => 500.00, 'currency' => 'BDT'])->formatted_amount);
    }

    /** @test */
    public function scope_active_filters_due_active_profiles(): void
    {
        $this->makeProfile(['status' => 'active', 'next_billing_date' => now()->subDay()]);
        $this->makeProfile(['status' => 'active', 'next_billing_date' => now()->addDay()]);

        $this->assertCount(1, RecurringPaymentProfile::active()->get());
    }
}
