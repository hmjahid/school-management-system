<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCategory extends Model
{
    protected $fillable = ['name', 'description'];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'category_id');
    }
}
