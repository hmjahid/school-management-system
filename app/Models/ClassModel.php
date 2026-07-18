<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassModel extends Model
{
    use SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'teacher_id',
        'subject_id',
        'section_id',
        'academic_year',
        'schedule',
        'room_number',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the teacher that teaches the class.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the subject that the class is for.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the section that the class belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the students enrolled in the class.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Get the grades for the class.
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'class_id');
    }
}
