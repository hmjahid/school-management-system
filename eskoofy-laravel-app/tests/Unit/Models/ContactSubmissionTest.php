<?php

namespace Tests\Unit\Models;

use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactSubmissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_key_columns(): void
    {
        $submission = ContactSubmission::create([
            'type' => ContactSubmission::TYPE_CONTACT,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello there',
        ]);

        $this->assertDatabaseHas('contact_submissions', [
            'id' => $submission->id,
            'type' => 'contact',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function it_defaults_type_to_contact(): void
    {
        $submission = ContactSubmission::create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'message' => 'Hi',
        ]);

        $this->assertSame('contact', $submission->fresh()->type);
    }

    #[Test]
    public function it_exposes_type_constants(): void
    {
        $this->assertSame('contact', ContactSubmission::TYPE_CONTACT);
        $this->assertSame('feedback', ContactSubmission::TYPE_FEEDBACK);
        $this->assertSame('complaint', ContactSubmission::TYPE_COMPLAINT);
        $this->assertSame('scholarship', ContactSubmission::TYPE_SCHOLARSHIP);
        $this->assertSame('newsletter', ContactSubmission::TYPE_NEWSLETTER);
    }

    #[Test]
    public function it_casts_meta_to_array(): void
    {
        $submission = ContactSubmission::create([
            'name' => 'Meta',
            'email' => 'meta@example.com',
            'message' => 'Msg',
            'meta' => ['source' => 'web', 'ref' => 42],
        ]);

        $this->assertIsArray($submission->meta);
        $this->assertSame('web', $submission->meta['source']);
    }
}
