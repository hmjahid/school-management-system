<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): BookCategory
    {
        return BookCategory::create(['name' => 'Fiction '.uniqid()]);
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $book = Book::create([
            'title' => 'Laravel Deep Dive',
            'author' => 'Jane Doe',
            'isbn' => 'ISBN'.uniqid(),
            'category_id' => $this->makeCategory()->id,
            'quantity' => 10,
            'available_quantity' => 7,
            'price' => 250.50,
            'status' => true,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Laravel Deep Dive',
            'quantity' => 10,
            'available_quantity' => 7,
            'price' => 250.50,
        ]);
        $this->assertEquals(250.5, (float) $book->price);
    }

    #[Test]
    public function it_casts_purchase_date_to_date(): void
    {
        $date = now()->subMonth()->startOfDay();
        $book = Book::create(['title' => 'Dated Book', 'purchase_date' => $date]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $book->purchase_date);
        $this->assertEquals($date->toDateString(), $book->purchase_date->toDateString());
    }

    #[Test]
    public function it_exposes_cover_url_accessor(): void
    {
        $book = Book::create(['title' => 'Cover Book', 'cover_image' => 'covers/x.jpg']);

        $this->assertEquals(url('storage/covers/x.jpg'), $book->cover_url);
    }

    #[Test]
    public function cover_url_is_null_when_no_image(): void
    {
        $book = Book::create(['title' => 'No Cover']);

        $this->assertNull($book->cover_url);
    }

    #[Test]
    public function it_belongs_to_a_category(): void
    {
        $category = $this->makeCategory();
        $book = Book::create(['title' => 'Categorized', 'category_id' => $category->id]);

        $this->assertInstanceOf(BookCategory::class, $book->category);
        $this->assertEquals($category->id, $book->category->id);
    }

    #[Test]
    public function it_belongs_to_a_creator(): void
    {
        $user = User::factory()->create();
        $book = Book::create(['title' => 'Authored', 'created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $book->createdBy);
        $this->assertEquals($user->id, $book->createdBy->id);
    }

    #[Test]
    public function it_has_issues_relationship(): void
    {
        $book = Book::create(['title' => 'Issued Book']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $book->issues());
    }

    #[Test]
    public function current_issues_scope_filters_by_status(): void
    {
        $book = Book::create(['title' => 'Scoped Book']);
        \App\Models\BookIssue::create([
            'book_id' => $book->id,
            'issue_date' => now()->subDays(2),
            'due_date' => now()->addDays(5),
            'status' => \App\Models\BookIssue::STATUS_ISSUED,
        ]);
        \App\Models\BookIssue::create([
            'book_id' => $book->id,
            'issue_date' => now()->subDays(10),
            'due_date' => now()->subDays(5),
            'status' => \App\Models\BookIssue::STATUS_RETURNED,
        ]);

        $this->assertCount(1, $book->currentIssues);
    }

    #[Test]
    public function is_available_when_active_and_in_stock(): void
    {
        $book = Book::create(['title' => 'Avail', 'available_quantity' => 3, 'status' => true]);
        $this->assertTrue($book->isAvailable());

        $out = Book::create(['title' => 'Out', 'available_quantity' => 0, 'status' => true]);
        $this->assertFalse($out->isAvailable());

        $inactive = Book::create(['title' => 'Off', 'available_quantity' => 3, 'status' => false]);
        $this->assertFalse($inactive->isAvailable());
    }

    #[Test]
    public function it_soft_deletes(): void
    {
        $book = Book::create(['title' => 'Soft']);
        $id = $book->id;

        $book->delete();

        $this->assertSoftDeleted('books', ['id' => $id]);
        $this->assertNull(Book::find($id));
        $this->assertNotNull(Book::withTrashed()->find($id));
    }
}
