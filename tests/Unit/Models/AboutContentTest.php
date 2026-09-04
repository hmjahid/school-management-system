<?php

namespace Tests\Unit\Models;

use App\Models\AboutContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AboutContentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_required_columns(): void
    {
        $about = AboutContent::create([
            'school_name' => 'Demo School',
            'established_year' => 2000,
            'about_summary' => 'A great school.',
            'address' => '1 School Road',
            'phone' => '01733333333',
            'email' => 'info@example.com',
        ]);

        $this->assertDatabaseHas('about_contents', [
            'id' => $about->id,
            'school_name' => 'Demo School',
            'established_year' => 2000,
        ]);
        $this->assertEquals('Demo School', $about->school_name);
        $this->assertEquals(2000, $about->established_year);
    }

    #[Test]
    public function it_casts_json_columns_to_arrays(): void
    {
        $about = AboutContent::create([
            'school_name' => 'Demo School',
            'established_year' => 2005,
            'about_summary' => 'Summary',
            'address' => 'Addr',
            'phone' => '01733333333',
            'email' => 'info@example.com',
            'core_values' => ['Integrity', 'Excellence'],
            'contact_info' => ['facebook' => 'fb.com'],
            'social_links' => ['twitter' => 'tw.com'],
        ]);

        $this->assertIsArray($about->core_values);
        $this->assertSame(['Integrity', 'Excellence'], $about->core_values);
        $this->assertSame(['facebook' => 'fb.com'], $about->contact_info);
        $this->assertSame(['twitter' => 'tw.com'], $about->social_links);
    }

    #[Test]
    public function it_exposes_logo_url_accessor(): void
    {
        $about = AboutContent::create([
            'school_name' => 'Demo School',
            'established_year' => 2005,
            'about_summary' => 'Summary',
            'address' => 'Addr',
            'phone' => '01733333333',
            'email' => 'info@example.com',
            'logo_path' => 'logos/logo.png',
        ]);

        $this->assertStringContainsString('storage/logos/logo.png', $about->logo_url);
    }

    #[Test]
    public function logo_url_is_null_without_path(): void
    {
        $about = AboutContent::create([
            'school_name' => 'Demo School',
            'established_year' => 2005,
            'about_summary' => 'Summary',
            'address' => 'Addr',
            'phone' => '01733333333',
            'email' => 'info@example.com',
        ]);

        $this->assertNull($about->logo_url);
        $this->assertNull($about->favicon_url);
    }

    #[Test]
    public function get_content_returns_existing_record(): void
    {
        $about = AboutContent::create([
            'school_name' => 'Stored School',
            'established_year' => 1990,
            'about_summary' => 'Summary',
            'address' => 'Addr',
            'phone' => '01733333333',
            'email' => 'info@example.com',
        ]);

        $fetched = AboutContent::getContent();

        $this->assertInstanceOf(AboutContent::class, $fetched);
        $this->assertEquals($about->id, $fetched->id);
        $this->assertEquals('Stored School', $fetched->school_name);
    }

    #[Test]
    public function get_content_returns_new_instance_when_empty(): void
    {
        $fetched = AboutContent::getContent();

        $this->assertInstanceOf(AboutContent::class, $fetched);
        $this->assertNull($fetched->id);
    }
}
