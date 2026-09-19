<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\BookCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_name_and_description(): void
    {
        $category = BookCategory::create([
            'name' => 'Science '.uniqid(),
            'description' => 'Science textbooks',
        ]);

        $this->assertDatabaseHas('book_categories', [
            'id' => $category->id,
            'name' => $category->name,
            'description' => 'Science textbooks',
        ]);
    }

    #[Test]
    public function description_is_optional(): void
    {
        $category = BookCategory::create(['name' => 'Math '.uniqid()]);

        $this->assertNull($category->description);
    }

    #[Test]
    public function it_has_many_books(): void
    {
        $category = BookCategory::create(['name' => 'Fiction '.uniqid()]);
        Book::create(['title' => 'Book A', 'category_id' => $category->id]);
        Book::create(['title' => 'Book B', 'category_id' => $category->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $category->books());
        $this->assertCount(2, $category->books);
    }
}
