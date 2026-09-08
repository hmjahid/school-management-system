<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\JobApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CareerApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function makeCareer(): Career
    {
        return Career::create([
            'title' => 'Science Teacher',
            'description' => 'Teach science.',
            'requirements' => 'Degree required.',
            'type' => 'full-time',
            'location' => 'Dhaka',
            'deadline' => now()->addDays(30)->toDateString(),
            'is_published' => true,
        ]);
    }

    #[Test]
    public function apply_persists_job_application(): void
    {
        $career = $this->makeCareer();

        $response = $this->postJson('/api/v1/careers/apply', [
            'career_id' => $career->id,
            'name' => 'Ayesha Rahman',
            'email' => 'ayesha@example.com',
            'phone' => '01700000000',
            'resume' => UploadedFile::fake()->create('resume.pdf', 1024, 'application/pdf'),
            'cover_letter' => 'I am interested in this position.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('job_applications', [
            'career_id' => $career->id,
            'name' => 'Ayesha Rahman',
            'email' => 'ayesha@example.com',
            'phone' => '01700000000',
            'status' => 'pending',
        ]);

        $application = JobApplication::first();
        $this->assertNotNull($application);
        $this->assertNotNull($application->resume_path);
        Storage::disk('public')->assertExists($application->resume_path);
    }

    #[Test]
    public function apply_validates_required_fields(): void
    {
        $this->postJson('/api/v1/careers/apply', [])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    #[Test]
    public function apply_rejects_invalid_career(): void
    {
        $this->postJson('/api/v1/careers/apply', [
            'career_id' => 99999,
            'name' => 'Test',
            'email' => 'test@example.com',
            'phone' => '01700000000',
            'resume' => UploadedFile::fake()->create('resume.pdf', 1024, 'application/pdf'),
        ])->assertStatus(422);
    }
}
