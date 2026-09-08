<?php

namespace Tests\Unit\Models;

use App\Models\WebsiteMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsiteMediaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_required_columns(): void
    {
        $media = WebsiteMedia::create([
            'title' => 'Campus Photo',
            'category' => 'gallery',
            'file_path' => '/media/campus.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 10240,
        ]);

        $this->assertDatabaseHas('website_media', [
            'title' => 'Campus Photo',
            'category' => 'gallery',
            'file_path' => '/media/campus.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 10240,
        ]);
    }

    #[Test]
    public function its_url_accessor_returns_storage_url(): void
    {
        $media = new WebsiteMedia(['file_path' => '/media/campus.jpg']);

        $this->assertEquals(url('storage/media/campus.jpg'), $media->url());
    }

    #[Test]
    public function its_url_accessor_returns_null_without_path(): void
    {
        $media = new WebsiteMedia(['file_path' => null]);

        $this->assertNull($media->url());
    }

    #[Test]
    public function its_is_image_method_detects_image_mime_type(): void
    {
        $media = new WebsiteMedia([
            'file_path' => '/media/photo.png',
            'mime_type' => 'image/png',
        ]);

        $this->assertTrue($media->isImage());
    }

    #[Test]
    public function its_is_image_method_detects_image_extension(): void
    {
        $media = new WebsiteMedia([
            'file_path' => '/media/photo.webp',
            'mime_type' => 'application/octet-stream',
        ]);

        $this->assertTrue($media->isImage());
    }

    #[Test]
    public function its_is_image_method_returns_false_for_non_image(): void
    {
        $media = new WebsiteMedia([
            'file_path' => '/media/doc.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->assertFalse($media->isImage());
    }

    #[Test]
    public function it_casts_file_size_to_integer(): void
    {
        $media = WebsiteMedia::create([
            'title' => 'File',
            'file_path' => '/media/file.pdf',
            'file_size' => '8192',
        ]);

        $this->assertSame(8192, $media->file_size);
    }
}
