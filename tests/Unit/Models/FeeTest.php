<?php

namespace Tests\Unit\Models;

use App\Models\Fee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeeTest extends TestCase
{
    use RefreshDatabase;

    private function makeFee(array $overrides = []): Fee
    {
        return Fee::create(array_merge([
            'name' => 'Tuition Fee',
            'amount' => 1500.50,
            'fee_type' => Fee::TYPE_TUITION,
            'frequency' => Fee::FREQUENCY_MONTHLY,
            'status' => Fee::STATUS_ACTIVE,
        ], $overrides));
    }

    #[Test]
    public function formatted_amount_uses_number_format(): void
    {
        $this->assertEquals('1,500.50', $this->makeFee(['amount' => 1500.50])->formatted_amount);
    }

    #[Test]
    public function status_badge_returns_proper_color(): void
    {
        $this->assertStringContainsString('badge bg-success', $this->makeFee(['status' => Fee::STATUS_ACTIVE])->status_badge);
        $this->assertStringContainsString('badge bg-secondary', $this->makeFee(['status' => Fee::STATUS_INACTIVE])->status_badge);
        $this->assertStringContainsString('badge bg-danger', $this->makeFee(['status' => Fee::STATUS_ARCHIVED])->status_badge);
    }

    #[Test]
    public function fee_types_and_frequencies_are_complete(): void
    {
        $types = Fee::getFeeTypes();
        $this->assertEquals('Tuition Fee', $types[Fee::TYPE_TUITION]);
        $this->assertEquals('Transport Fee', $types[Fee::TYPE_TRANSPORT]);

        $frequencies = Fee::getFrequencies();
        $this->assertEquals('Monthly', $frequencies[Fee::FREQUENCY_MONTHLY]);
        $this->assertEquals('One Time', $frequencies[Fee::FREQUENCY_ONE_TIME]);
    }

    #[Test]
    public function scope_active_filters_only_active_fees(): void
    {
        $this->makeFee(['status' => Fee::STATUS_ACTIVE, 'name' => 'Active Fee']);
        $this->makeFee(['status' => Fee::STATUS_INACTIVE, 'name' => 'Inactive Fee']);

        $active = Fee::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('Active Fee', $active->first()->name);
    }

    #[Test]
    public function it_soft_deletes(): void
    {
        $fee = $this->makeFee();

        $fee->delete();

        $this->assertSoftDeleted('fees', ['id' => $fee->id]);
    }
}
