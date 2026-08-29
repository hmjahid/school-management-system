<?php

namespace Tests\Unit\Models;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubject(array $overrides = []): Subject
    {
        return Subject::create(array_merge([
            'name' => 'Subject '.uniqid(),
            'code' => 'SUB'.uniqid(),
            'type' => 'theory',
            'is_active' => true,
        ], $overrides));
    }

    /** @test */
    public function it_persists_fillable_columns(): void
    {
        $code = 'SUB'.uniqid();
        $subject = $this->makeSubject([
            'code' => $code,
            'type' => 'practical',
            'short_name' => 'Math',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('subjects', [
            'code' => $code,
            'type' => 'practical',
            'short_name' => 'Math',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_casts_boolean_and_credit_hours(): void
    {
        $subject = $this->makeSubject(['is_active' => 1, 'credit_hours' => 4.00]);

        $this->assertIsBool($subject->is_active);
        $this->assertTrue($subject->is_active);
        $this->assertIsFloat($subject->credit_hours);
    }

    /** @test */
    public function it_has_classes_and_exams_relationships(): void
    {
        $subject = $this->makeSubject();

        $this->assertInstanceOf(BelongsToMany::class, $subject->classes());
        $this->assertInstanceOf(HasMany::class, $subject->exams());
    }

    /** @test */
    public function it_computes_status_badge(): void
    {
        $subject = $this->makeSubject(['is_active' => true]);

        $this->assertStringContainsString('badge', $subject->status_badge);
        $this->assertStringContainsString('Active', $subject->status_badge);
    }

    /** @test */
    public function it_computes_class_count(): void
    {
        $subject = $this->makeSubject();

        $this->assertSame(0, $subject->class_count);
    }

    /** @test */
    public function it_scopes_active_and_of_type(): void
    {
        $this->makeSubject(['is_active' => true, 'type' => 'theory']);
        $this->makeSubject(['is_active' => false, 'type' => 'theory']);
        $this->makeSubject(['is_active' => true, 'type' => 'practical']);

        $this->assertEquals(2, Subject::active()->count());
        $this->assertEquals(1, Subject::ofType('practical')->count());
    }
}
