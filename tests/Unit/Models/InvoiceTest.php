<?php

namespace Tests\Unit\Models;

use App\Models\Fee;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'student_id' => Student::factory()->create()->id,
            'fee_id' => Fee::create([
                'name' => 'Tuition',
                'code' => 'FEE'.uniqid(),
                'amount' => 1000.00,
                'fee_type' => 'tuition',
                'status' => 'active',
            ])->id,
            'amount' => 1000.00,
            'due_date' => now()->addDays(5),
            'status' => Invoice::STATUS_UNPAID,
        ], $overrides));
    }

    /** @test */
    public function it_exposes_status_constants(): void
    {
        $this->assertEquals('paid', Invoice::STATUS_PAID);
        $this->assertEquals('unpaid', Invoice::STATUS_UNPAID);
        $this->assertEquals('overdue', Invoice::STATUS_OVERDUE);
    }

    /** @test */
    public function is_paid_true_only_when_status_paid(): void
    {
        $this->assertTrue($this->makeInvoice(['status' => Invoice::STATUS_PAID])->is_paid);
        $this->assertFalse($this->makeInvoice(['status' => Invoice::STATUS_UNPAID])->is_paid);
    }

    /** @test */
    public function is_overdue_when_status_overdue(): void
    {
        $this->assertTrue($this->makeInvoice(['status' => Invoice::STATUS_OVERDUE])->is_overdue);
    }

    /** @test */
    public function is_overdue_when_unpaid_and_due_date_past(): void
    {
        $invoice = $this->makeInvoice([
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->subDays(3),
        ]);

        $this->assertTrue($invoice->is_overdue);
    }

    /** @test */
    public function not_overdue_when_paid_even_if_due_date_past(): void
    {
        $invoice = $this->makeInvoice([
            'status' => Invoice::STATUS_PAID,
            'due_date' => now()->subDays(3),
        ]);

        $this->assertFalse($invoice->is_overdue);
    }

    /** @test */
    public function not_overdue_when_unpaid_and_due_date_future(): void
    {
        $invoice = $this->makeInvoice([
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(3),
        ]);

        $this->assertFalse($invoice->is_overdue);
    }
}
