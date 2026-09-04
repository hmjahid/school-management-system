<?php

namespace Tests\Unit\Models;

use App\Models\Career;
use App\Models\JobApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCareer(): Career
    {
        return Career::create([
            'title' => 'Teacher',
            'description' => 'Teach.',
            'requirements' => 'Degree.',
            'type' => 'full-time',
            'location' => 'Dhaka',
            'deadline' => now()->addDays(10)->toDateString(),
        ]);
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $career = $this->makeCareer();

        $application = JobApplication::create([
            'career_id' => $career->id,
            'name' => 'Applicant One',
            'email' => 'applicant@example.com',
            'phone' => '01700000000',
            'resume_path' => '/resumes/applicant.pdf',
            'status' => 'reviewed',
        ]);

        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'career_id' => $career->id,
            'email' => 'applicant@example.com',
            'status' => 'reviewed',
        ]);

        $this->assertSame('reviewed', $application->status);
    }

    #[Test]
    public function it_defaults_status_to_pending(): void
    {
        $career = $this->makeCareer();

        $application = JobApplication::create([
            'career_id' => $career->id,
            'name' => 'Pending Applicant',
            'email' => 'pending@example.com',
            'phone' => '01700000001',
            'resume_path' => '/resumes/pending.pdf',
        ]);

        $this->assertSame('pending', $application->fresh()->status);
    }

    #[Test]
    public function it_belongs_to_a_career(): void
    {
        $career = $this->makeCareer();

        $application = JobApplication::create([
            'career_id' => $career->id,
            'name' => 'Rel',
            'email' => 'rel@example.com',
            'phone' => '01700000002',
            'resume_path' => '/resumes/rel.pdf',
        ]);

        $this->assertInstanceOf(Career::class, $application->career);
        $this->assertSame($career->id, $application->career->id);
    }
}
