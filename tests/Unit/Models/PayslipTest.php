<?php

namespace Tests\Unit\Models;

use App\Models\Payslip;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacher(): Teacher
    {
        return Teacher::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP'.uniqid(),
            'joining_date' => now()->subYear(),
        ]);
    }

    private function makePayslip(array $overrides = []): Payslip
    {
        return Payslip::create(array_merge([
            'teacher_id' => $this->makeTeacher()->id,
            'month' => 3,
            'year' => 2026,
            'basic' => 20000,
            'total_allowances' => 2000,
            'total_deductions' => 1500,
            'net_salary' => 20500,
            'status' => Payslip::STATUS_DRAFT,
        ], $overrides));
    }

    /** @test */
    public function it_exposes_status_constants(): void
    {
        $this->assertEquals('draft', Payslip::STATUS_DRAFT);
        $this->assertEquals('paid', Payslip::STATUS_PAID);
    }

    /** @test */
    public function it_persists_key_columns_and_defaults_status_to_draft(): void
    {
        $payslip = $this->makePayslip();

        $this->assertDatabaseHas('payslips', [
            'id' => $payslip->id,
            'month' => 3,
            'year' => 2026,
            'net_salary' => 20500,
        ]);
        $this->assertEquals(Payslip::STATUS_DRAFT, $payslip->status);
        $this->assertEquals(20500.0, (float) $payslip->net_salary);
    }

    /** @test */
    public function it_belongs_to_a_teacher(): void
    {
        $teacher = $this->makeTeacher();
        $payslip = $this->makePayslip(['teacher_id' => $teacher->id]);

        $this->assertInstanceOf(Teacher::class, $payslip->teacher);
        $this->assertEquals($teacher->id, $payslip->teacher->id);
    }

    /** @test */
    public function month_name_returns_month_label(): void
    {
        $payslip = $this->makePayslip(['month' => 1]);

        $this->assertEquals('January', $payslip->monthName());
    }

    /** @test */
    public function mark_paid_sets_status_and_paid_at(): void
    {
        $payslip = $this->makePayslip();

        $this->assertTrue($payslip->markPaid());

        $fresh = $payslip->fresh();
        $this->assertEquals(Payslip::STATUS_PAID, $fresh->status);
        $this->assertNotNull($fresh->paid_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->paid_at);
    }
}
