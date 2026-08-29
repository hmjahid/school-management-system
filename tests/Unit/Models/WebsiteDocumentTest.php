<?php

namespace Tests\Unit\Models;

use App\Models\WebsiteDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteDocumentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_required_columns(): void
    {
        $doc = WebsiteDocument::create([
            'title' => 'Admission Policy',
            'category' => 'Policies',
            'file_path' => '/docs/policy.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('website_documents', [
            'title' => 'Admission Policy',
            'category' => 'Policies',
            'file_path' => '/docs/policy.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'is_published' => true,
        ]);
    }

    /** @test */
    public function it_casts_is_published_and_file_size(): void
    {
        $doc = WebsiteDocument::create([
            'title' => 'Newsletter',
            'file_path' => '/docs/news.pdf',
            'is_published' => 1,
            'file_size' => '4096',
        ]);

        $this->assertTrue($doc->is_published);
        $this->assertSame(4096, $doc->file_size);
    }

    /** @test */
    public function it_defaults_is_published_to_true(): void
    {
        $doc = WebsiteDocument::create([
            'title' => 'Forms',
            'file_path' => '/docs/form.pdf',
        ]);

        $this->assertDatabaseHas('website_documents', [
            'id' => $doc->id,
            'is_published' => true,
        ]);
    }
}
