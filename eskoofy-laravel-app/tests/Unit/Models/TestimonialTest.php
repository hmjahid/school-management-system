<?php

namespace Tests\Unit\Models;

use App\Models\Student;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    private function makeTestimonial(array $attributes = []): Testimonial
    {
        return Testimonial::create(array_merge([
            'author_name' => 'Jane Doe',
            'content' => 'Great student.',
            'status' => Testimonial::STATUS_DRAFT,
            'is_visible' => true,
        ], $attributes));
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $t = $this->makeTestimonial([
            'name' => 'Award',
            'testimonial_type' => 'behavior',
            'testimonial_number' => 'TEST-'.uniqid(),
            'issue_date' => now()->toDateString(),
            'rating' => 5,
            'sort_order' => 2,
            'body' => ['note' => 'ok'],
        ]);

        $this->assertDatabaseHas('testimonials', [
            'id' => $t->id,
            'author_name' => 'Jane Doe',
            'status' => Testimonial::STATUS_DRAFT,
            'is_visible' => true,
            'rating' => 5,
        ]);
        $this->assertSame(['note' => 'ok'], $t->body);
    }

    #[Test]
    public function it_has_status_constants(): void
    {
        $this->assertSame('draft', Testimonial::STATUS_DRAFT);
        $this->assertSame('issued', Testimonial::STATUS_ISSUED);
        $this->assertSame('revoked', Testimonial::STATUS_REVOKED);
    }

    #[Test]
    public function it_has_type_constants(): void
    {
        $this->assertContains('behavior', Testimonial::TYPES);
        $this->assertContains('academic_excellence', Testimonial::TYPES);
        $this->assertContains('overall', Testimonial::TYPES);
    }

    #[Test]
    public function it_generates_a_number(): void
    {
        $number = Testimonial::generateNumber();

        $this->assertStringStartsWith('TEST-'.now()->year.'-', $number);
    }

    #[Test]
    public function scope_visible_returns_only_visible(): void
    {
        $this->makeTestimonial(['is_visible' => true]);
        $this->makeTestimonial(['is_visible' => false]);

        $visible = Testimonial::visible()->get();

        $this->assertCount(1, $visible);
        $this->assertTrue($visible->first()->is_visible);
    }

    #[Test]
    public function scope_ordered_sorts_by_sort_order(): void
    {
        $this->makeTestimonial(['sort_order' => 5]);
        $this->makeTestimonial(['sort_order' => 1]);

        $ordered = Testimonial::ordered()->get();

        $this->assertSame(1, $ordered->first()->sort_order);
        $this->assertSame(5, $ordered->last()->sort_order);
    }

    #[Test]
    public function it_belongs_to_a_student(): void
    {
        $student = Student::factory()->create();
        $t = $this->makeTestimonial(['student_id' => $student->id]);

        $this->assertInstanceOf(Student::class, $t->student);
        $this->assertSame($student->id, $t->student->id);
    }

    #[Test]
    public function it_belongs_to_a_generator(): void
    {
        $user = User::factory()->create();
        $t = $this->makeTestimonial(['generated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $t->generatedBy);
        $this->assertSame($user->id, $t->generatedBy->id);
    }
}
