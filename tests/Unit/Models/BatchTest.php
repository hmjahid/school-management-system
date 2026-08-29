<?php

namespace Tests\Unit\Models;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Course;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(): AcademicSession
    {
        return AcademicSession::create([
            'name' => 'Session '.uniqid(),
            'code' => 'CODE'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);
    }

    private function makeCourse(): Course
    {
        return Course::create([
            'name' => 'Course '.uniqid(),
            'code' => 'CRS'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function makeBatch(array $overrides = []): Batch
    {
        return Batch::create(array_merge([
            'name' => 'Batch '.uniqid(),
            'code' => 'BAT'.uniqid(),
            'description' => 'Desc',
            'start_date' => '2024-01-01',
            'end_date' => '2024-04-30',
            'academic_session_id' => $this->makeSession()->id,
            'course_id' => $this->makeCourse()->id,
            'is_active' => true,
            'status' => 'ongoing',
            'notes' => 'Note',
        ], $overrides));
    }

    /** @test */
    public function it_persists_fillable_columns(): void
    {
        $code = 'BAT'.uniqid();
        $batch = $this->makeBatch(['code' => $code, 'status' => 'upcoming']);

        $this->assertDatabaseHas('batches', [
            'code' => $code,
            'status' => 'upcoming',
            'is_active' => true,
            'name' => $batch->name,
        ]);
    }

    /** @test */
    public function it_casts_dates_and_booleans(): void
    {
        $batch = $this->makeBatch();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $batch->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $batch->end_date);
        $this->assertIsBool($batch->is_active);
    }

    /** @test */
    public function it_belongs_to_academic_session(): void
    {
        $session = $this->makeSession();
        $batch = $this->makeBatch(['academic_session_id' => $session->id]);

        $this->assertInstanceOf(BelongsTo::class, $batch->academicSession());
        $this->assertSame($session->id, $batch->academicSession->id);
    }

    /** @test */
    public function it_belongs_to_course(): void
    {
        $course = $this->makeCourse();
        $batch = $this->makeBatch(['course_id' => $course->id]);

        $this->assertInstanceOf(BelongsTo::class, $batch->course());
        $this->assertSame($course->id, $batch->course->id);
    }

    /** @test */
    public function it_computes_duration_weeks_accessor(): void
    {
        $batch = $this->makeBatch([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-29',
        ]);

        $this->assertSame(4, $batch->duration_weeks);
    }

    /** @test */
    public function it_returns_status_badge(): void
    {
        $batch = $this->makeBatch(['status' => 'ongoing']);

        $this->assertStringContainsString('badge', $batch->status_badge);
        $this->assertStringContainsString('Ongoing', $batch->status_badge);
    }

    /** @test */
    public function it_scopes_active_batches(): void
    {
        $this->makeBatch(['is_active' => true]);
        $this->makeBatch(['is_active' => false]);

        $this->assertEquals(1, Batch::active()->count());
    }

    /** @test */
    public function it_has_status_constants(): void
    {
        $this->assertSame('upcoming', Batch::STATUS_UPCOMING);
        $this->assertSame('ongoing', Batch::STATUS_ONGOING);
        $this->assertSame('completed', Batch::STATUS_COMPLETED);
        $this->assertSame('cancelled', Batch::STATUS_CANCELLED);
    }

    /** @test */
    public function it_soft_deletes(): void
    {
        $batch = $this->makeBatch();
        $batch->delete();

        $this->assertSoftDeleted('batches', ['id' => $batch->id]);
    }
}
