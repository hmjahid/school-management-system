<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class BookCategory extends Model
{
    protected static string $table = 'book_categories';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'description',
    ];

    public function books()
    {
        return $this->hasMany(Book::class, 'category_id');
    }
}
