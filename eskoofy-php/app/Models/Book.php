<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Book extends Model
{
    protected static string $table = 'books';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'title', 'author', 'publisher', 'isbn', 'category_id',
        'shelf_location', 'quantity', 'available_quantity', 'purchase_date',
        'price', 'description', 'cover_image', 'status', 'created_by',
    ];

    protected array $casts = [
        'purchase_date' => 'date',
        'price' => 'float',
        'quantity' => 'integer',
        'available_quantity' => 'integer',
        'status' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function issues()
    {
        return $this->hasMany(BookIssue::class);
    }
}
