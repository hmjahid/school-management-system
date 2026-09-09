<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Testimonial extends Model
{
    protected static string $table = 'testimonials';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'student_id', 'testimonial_type', 'testimonial_number',
        'issue_date', 'status', 'body', 'generated_by', 'author_name',
        'author_designation', 'content', 'rating', 'photo', 'is_visible',
        'sort_order', 'details',
    ];

    protected array $casts = [
        'is_visible' => 'boolean',
        'rating' => 'integer',
        'issue_date' => 'date',
        'body' => 'json',
        'details' => 'json',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
