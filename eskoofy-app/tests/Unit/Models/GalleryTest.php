<?php

namespace Tests\Unit\Models;

use App\Models\Gallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_key_columns(): void
    {
        $item = Gallery::create([
            'title' => 'Picnic',
            'image_path' => '/storage/gallery/picnic.jpg',
            'category' => 'events',
            'is_published' => false,
        ]);

        $this->assertDatabaseHas('galleries', [
            'id' => $item->id,
            'title' => 'Picnic',
            'category' => 'events',
            'is_published' => false,
        ]);

        $this->assertFalse($item->is_published);
    }

    #[Test]
    public function it_scopes_published_gallery_items(): void
    {
        Gallery::create([
            'title' => 'P',
            'image_path' => '/p.jpg',
            'category' => 'c',
            'is_published' => true,
        ]);
        Gallery::create([
            'title' => 'U',
            'image_path' => '/u.jpg',
            'category' => 'c',
            'is_published' => false,
        ]);

        $this->assertCount(1, Gallery::published()->get());
    }
}
