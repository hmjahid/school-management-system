<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIssueTest extends TestCase
{
    use RefreshDatabase;

    private function makeBook(): Book
    {
        return Book::create(['title' => 'Laravel Basics', 'quantity' => 5, 'available_quantity' => 5]);
    }

    private function makeIssue(array $overrides = []): BookIssue
    {
        return BookIssue::create(array_merge([
            'book_id' => $this->makeBook()->id,
            'student_id' => Student::factory()->create()->id,
            'issue_date' => now()->subDays(10),
            'due_date' => now()->subDays(3),
            'status' => BookIssue::STATUS_ISSUED,
        ], $overrides));
    }

    /** @test */
    public function it_exposes_status_constants(): void
    {
        $this->assertEquals('issued', BookIssue::STATUS_ISSUED);
        $this->assertEquals('returned', BookIssue::STATUS_RETURNED);
        $this->assertEquals('lost', BookIssue::STATUS_LOST);
        $this->assertEquals('damaged', BookIssue::STATUS_DAMAGED);
    }

    /** @test */
    public function is_overdue_when_issued_and_due_date_past(): void
    {
        $this->assertTrue($this->makeIssue(['due_date' => now()->subDays(2)])->isOverdue());
    }

    /** @test */
    public function not_overdue_when_returned(): void
    {
        $this->assertFalse($this->makeIssue([
            'status' => BookIssue::STATUS_RETURNED,
            'due_date' => now()->subDays(2),
        ])->isOverdue());
    }

    /** @test */
    public function not_overdue_when_due_date_in_future(): void
    {
        $this->assertFalse($this->makeIssue(['due_date' => now()->addDays(5)])->isOverdue());
    }

    /** @test */
    public function calculate_late_fee_for_returned_late(): void
    {
        $issue = $this->makeIssue([
            'due_date' => now()->subDays(5),
            'return_date' => now()->subDays(2),
            'status' => BookIssue::STATUS_RETURNED,
        ]);

        // 3 days late * 10 per day = 30
        $this->assertEquals(30.0, $issue->calculateLateFee(10.0));
    }

    /** @test */
    public function calculate_late_fee_for_still_overdue(): void
    {
        $issue = $this->makeIssue(['due_date' => now()->subDays(4)]);

        // 4 days * 5 per day = 20
        $this->assertEquals(20.0, $issue->calculateLateFee(5.0));
    }

    /** @test */
    public function calculate_late_fee_zero_when_not_overdue(): void
    {
        $issue = $this->makeIssue(['due_date' => now()->addDays(5)]);

        $this->assertEquals(0.0, $issue->calculateLateFee(5.0));
    }

    /** @test */
    public function scope_overdue_filters_issued_past_due(): void
    {
        $this->makeIssue(['due_date' => now()->subDays(1)]);
        $this->makeIssue(['due_date' => now()->addDays(5), 'status' => BookIssue::STATUS_ISSUED]);

        $this->assertCount(1, BookIssue::overdue()->get());
    }
}
